<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportTemplateRequest;
use App\Models\IncomingReportEmail;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\ReportTemplate;
use App\Services\Reports\PendingReportReprocessor;
use App\Services\Reports\ReportTemplateSuggestionService;
use App\Services\Reports\ReportProcessingService;
use App\Support\ParserRegistry;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\View\View;

class ReportTemplateController extends Controller
{
    public function __construct(private readonly ReportTemplateSuggestionService $suggestions) {}

    public function index(Request $request): View
    {
        $query = ReportTemplate::query();

        if (request()->user()->isPlatformAdmin()) {
            $query->where(function ($query) {
                $query->whereNull('company_id')
                    ->orWhere('approval_status', ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW);
            });
        } else {
            Tenant::scopeWithGlobal($query, request()->user());
        }

        $query
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';

                $query->where(function ($query) use ($term) {
                    $query->where('template_name', 'like', $term)
                        ->orWhere('sample_subject', 'like', $term)
                        ->orWhereHas('machineModel', function ($query) use ($term) {
                            $query->where('manufacturer', 'like', $term)
                                ->orWhere('model_name', 'like', $term);
                        });
                });
            })
            ->when($request->filled('parser_type'), fn ($query) => $query->where('parser_type', $request->string('parser_type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->when($request->filled('owner'), function ($query) use ($request) {
                match ($request->string('owner')->toString()) {
                    'prebuilt' => $query->whereNull('company_id'),
                    'pending' => $query->where('approval_status', ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW),
                    default => $query->whereNotNull('company_id'),
                };
            });

        return view('report-templates.index', [
            'reportTemplates' => $query->with(['company', 'machineModel.company'])->latest()->paginate(20)->withQueryString(),
            'parserTypes' => ParserRegistry::options(),
        ]);
    }

    public function create(Request $request): View
    {
        $query = MachineModel::query();

        request()->user()->isPlatformAdmin()
            ? $query->whereNull('company_id')
            : Tenant::scopeWithGlobal($query, request()->user());

        $sourceEmail = $request->filled('incoming_report_email_id')
            ? IncomingReportEmail::with('machine.machineModel')->findOrFail($request->integer('incoming_report_email_id'))
            : null;

        if ($sourceEmail) {
            $this->authorizeEmailTenant($sourceEmail);
        }

        $inferredModel = $sourceEmail ? $this->inferMachineModel($sourceEmail, clone $query) : null;

        $reportTemplate = $sourceEmail
            ? new ReportTemplate([
                'machine_model_id' => $sourceEmail->machine?->machine_model_id ?? $inferredModel?->id,
                'template_name' => $sourceEmail->machine?->machineModel
                    ? $this->canonicalTemplateName($sourceEmail->machine->machineModel)
                    : ($inferredModel ? $this->canonicalTemplateName($inferredModel) : null),
                'sample_subject' => $sourceEmail->subject,
                'sample_body' => $sourceEmail->body_text,
                'parser_type' => $sourceEmail->machine?->machineModel?->parser_type
                    ?? $inferredModel?->parser_type
                    ?? $this->suggestions->suggestParserType($sourceEmail->body_text),
                'parser_configuration' => $this->suggestions->suggestParserConfiguration($sourceEmail->body_text),
                'is_active' => true,
                'approval_status' => ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW,
            ])
            : null;

        return view('report-templates.create', [
            'reportTemplate' => $reportTemplate,
            'sourceEmail' => $sourceEmail,
            'detectedFields' => $sourceEmail ? $this->suggestions->detectedFields($sourceEmail->body_text) : collect(),
            'machineModels' => $query->orderBy('manufacturer')->orderBy('model_name')->get(),
            'parserTypes' => ParserRegistry::options(),
        ]);
    }

    public function store(StoreReportTemplateRequest $request, ReportProcessingService $processor, PendingReportReprocessor $reprocessor): RedirectResponse
    {
        $reportTemplate = ReportTemplate::create($this->normalise($request));

        if ($request->filled('incoming_report_email_id')) {
            $email = IncomingReportEmail::find($request->integer('incoming_report_email_id'));

            if ($email) {
                try {
                    $processor->process($email);
                } catch (Throwable $exception) {
                    return redirect()
                        ->route('incoming-report-emails.show', $email)
                        ->with('status', 'Report template created, but the email could not be reprocessed: '.$exception->getMessage());
                }
            }

            $reprocessed = $reprocessor->forTemplate($reportTemplate)->count();

            return redirect()
                ->route('incoming-report-emails.show', $request->integer('incoming_report_email_id'))
                ->with('status', 'Report template created and the pending email was reprocessed.'.($reprocessed ? " {$reprocessed} other pending email(s) for this model were also parsed." : ''));
        }

        $reprocessed = $reprocessor->forTemplate($reportTemplate)->count();

        return redirect()->route('report-templates.index')->with('status', 'Report template created.'.($reprocessed ? " {$reprocessed} pending email(s) for this model were parsed." : ''));
    }

    public function show(ReportTemplate $reportTemplate): View
    {
        $this->authorizeTenant($reportTemplate);

        return view('report-templates.show', ['reportTemplate' => $reportTemplate->load('machineModel')]);
    }

    public function edit(ReportTemplate $reportTemplate): View
    {
        $this->authorizeTenant($reportTemplate);
        abort_if(! request()->user()->isPlatformAdmin() && is_null($reportTemplate->company_id), 403);
        $query = MachineModel::query();

        request()->user()->isPlatformAdmin()
            ? $query->whereNull('company_id')
            : Tenant::scopeWithGlobal($query, request()->user());

        if (blank($reportTemplate->parser_configuration) && filled($reportTemplate->sample_body)) {
            $reportTemplate->parser_configuration = $this->suggestions->suggestParserConfiguration($reportTemplate->sample_body);
        }

        return view('report-templates.edit', [
            'reportTemplate' => $reportTemplate,
            'sourceEmail' => null,
            'detectedFields' => $this->suggestions->detectedFields($reportTemplate->sample_body ?? ''),
            'machineModels' => $query->orderBy('manufacturer')->orderBy('model_name')->get(),
            'parserTypes' => ParserRegistry::options(),
        ]);
    }

    public function update(StoreReportTemplateRequest $request, ReportTemplate $reportTemplate, PendingReportReprocessor $reprocessor): RedirectResponse
    {
        $this->authorizeTenant($reportTemplate);
        abort_if(! $request->user()->isPlatformAdmin() && is_null($reportTemplate->company_id), 403);
        $reportTemplate->update($this->normalise($request, $reportTemplate));
        $reprocessed = $reprocessor->forTemplate($reportTemplate->fresh())->count();

        return redirect()->route('report-templates.show', $reportTemplate)->with('status', 'Report template updated.'.($reprocessed ? " {$reprocessed} pending email(s) for this model were parsed." : ''));
    }

    public function approveGlobal(ReportTemplate $reportTemplate): RedirectResponse
    {
        abort_unless(request()->user()->isPlatformAdmin(), 403);
        abort_unless($reportTemplate->company_id !== null, 422, 'Only company templates need global approval.');

        $globalModel = MachineModel::query()
            ->whereNull('company_id')
            ->where('manufacturer', $reportTemplate->machineModel->manufacturer)
            ->where('model_name', $reportTemplate->machineModel->model_name)
            ->first();

        if (! $globalModel) {
            $manufacturer = Manufacturer::findOrCreateByName($reportTemplate->machineModel->manufacturer);
            $globalModel = MachineModel::create([
                'company_id' => null,
                'manufacturer_id' => $manufacturer->id,
                'manufacturer' => $manufacturer->name,
                'model_name' => $reportTemplate->machineModel->model_name,
                'parser_type' => $reportTemplate->machineModel->parser_type,
                'notes' => 'Created from approved tenant report template.',
            ]);
        }

        $copy = $reportTemplate->replicate();
        $copy->company_id = null;
        $copy->machine_model_id = $globalModel->id;
        $copy->template_name = $this->canonicalTemplateName($globalModel);
        $copy->family_key = $this->familyKey($globalModel, $copy->parser_type);
        $copy->version = $this->nextVersion($copy->family_key, null);
        $copy->approval_status = ReportTemplate::STATUS_APPROVED_GLOBAL;
        $copy->approved_at = now();
        $copy->approved_by = request()->user()->id;
        $copy->is_active = true;
        $copy->save();

        $reportTemplate->forceFill([
            'approval_status' => ReportTemplate::STATUS_APPROVED_GLOBAL,
            'approved_at' => now(),
            'approved_by' => request()->user()->id,
        ])->save();

        return redirect()->route('report-templates.show', $copy)->with('status', 'Template approved as a global prebuilt version.');
    }

    public function duplicate(ReportTemplate $reportTemplate): RedirectResponse
    {
        $this->authorizeTenant($reportTemplate);

        $copy = $reportTemplate->replicate();
        $copy->company_id = request()->user()->isPlatformAdmin() ? null : request()->user()->company_id;
        $copy->template_name = $this->canonicalTemplateName($reportTemplate->machineModel);
        $copy->family_key = $reportTemplate->family_key ?: $this->familyKey($reportTemplate->machineModel, $reportTemplate->parser_type);
        $copy->version = $this->nextVersion($copy->family_key, $copy->company_id);
        $copy->approval_status = $copy->company_id === null ? ReportTemplate::STATUS_APPROVED_GLOBAL : ReportTemplate::STATUS_COMPANY;
        $copy->approved_at = $copy->company_id === null ? now() : null;
        $copy->approved_by = $copy->company_id === null ? request()->user()->id : null;
        $copy->is_active = true;
        $copy->save();

        return redirect()->route('report-templates.edit', $copy)->with('status', 'Template cloned. Review the name and sample before saving.');
    }

    public function destroy(ReportTemplate $reportTemplate): RedirectResponse
    {
        $this->authorizeTenant($reportTemplate);
        abort_if(! request()->user()->isPlatformAdmin() && is_null($reportTemplate->company_id), 403);
        $reportTemplate->delete();

        return redirect()->route('report-templates.index')->with('status', 'Report template deleted.');
    }

    private function normalise(StoreReportTemplateRequest $request, ?ReportTemplate $reportTemplate = null): array
    {
        $data = $request->validated();
        abort_unless(
            $request->user()->isPlatformAdmin()
                ? MachineModel::whereKey($data['machine_model_id'])->whereNull('company_id')->exists()
                : MachineModel::whereKey($data['machine_model_id'])
                    ->where(fn ($query) => $query->where('company_id', $request->user()->company_id)->orWhereNull('company_id'))
                    ->exists(),
            403
        );
        $machineModel = MachineModel::findOrFail($data['machine_model_id']);
        $companyId = $request->user()->isPlatformAdmin() ? null : $request->user()->company_id;
        $familyKey = $reportTemplate?->family_key ?: $this->familyKey($machineModel, $data['parser_type']);

        $data['is_active'] = $request->boolean('is_active');
        $data['company_id'] = $companyId;
        $data['template_name'] = $this->canonicalTemplateName($machineModel);
        $data['family_key'] = $familyKey;
        $data['version'] = $reportTemplate?->version ?: $this->nextVersion($familyKey, $companyId);
        $data['approval_status'] = $reportTemplate?->approval_status
            ?: ($request->user()->isPlatformAdmin()
                ? ReportTemplate::STATUS_APPROVED_GLOBAL
                : ($request->filled('incoming_report_email_id') ? ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW : ReportTemplate::STATUS_COMPANY));
        $data['approved_at'] = $reportTemplate?->approved_at ?: ($request->user()->isPlatformAdmin() ? now() : null);
        $data['approved_by'] = $reportTemplate?->approved_by ?: ($request->user()->isPlatformAdmin() ? $request->user()->id : null);
        $data['parser_configuration'] = filled($data['parser_configuration'] ?? null) ? json_decode($data['parser_configuration'], true) : null;
        unset($data['incoming_report_email_id']);

        return $data;
    }

    private function canonicalTemplateName(MachineModel $machineModel): string
    {
        return trim($machineModel->manufacturer.' '.$machineModel->model_name);
    }

    private function familyKey(MachineModel $machineModel, string $parserType): string
    {
        return str($machineModel->manufacturer.' '.$machineModel->model_name.' '.$parserType)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function nextVersion(string $familyKey, ?int $companyId): int
    {
        return ((int) ReportTemplate::query()
            ->where('family_key', $familyKey)
            ->where('company_id', $companyId)
            ->max('version')) + 1;
    }

    private function authorizeTenant(ReportTemplate $reportTemplate): void
    {
        abort_unless(
            (request()->user()->isPlatformAdmin() && (is_null($reportTemplate->company_id) || $reportTemplate->approval_status === ReportTemplate::STATUS_PENDING_GLOBAL_REVIEW))
            || (! request()->user()->isPlatformAdmin() && (is_null($reportTemplate->company_id) || $reportTemplate->company_id === request()->user()->company_id)),
            403
        );
    }

    private function authorizeEmailTenant(IncomingReportEmail $email): void
    {
        abort_unless(
            request()->user()->isPlatformAdmin() || $email->company_id === null || $email->company_id === request()->user()->company_id,
            403
        );
    }

    private function inferMachineModel(IncomingReportEmail $email, $query): ?MachineModel
    {
        $modelName = collect($this->suggestions->detectedFields($email->body_text))
            ->first(fn (array $field) => in_array(str($field['label'])->lower()->toString(), ['device model', 'model name', 'model'], true))['value'] ?? null;

        if (! $modelName) {
            return null;
        }

        return $query
            ->where(function ($query) use ($modelName) {
                $query->where('model_name', $modelName)
                    ->orWhere('model_name', 'like', '%'.$modelName.'%')
                    ->orWhereRaw('? like "%" || model_name || "%"', [$modelName]);
            })
            ->first();
    }

}
