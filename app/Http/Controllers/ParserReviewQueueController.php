<?php

namespace App\Http\Controllers;

use App\Models\IncomingReportEmail;
use App\Models\MachineModel;
use App\Models\Manufacturer;
use App\Models\ParserReviewLog;
use App\Models\ReportTemplate;
use App\Services\Reports\AiParserSuggestionService;
use App\Services\Reports\PendingReportReprocessor;
use App\Services\Reports\ReportProcessingService;
use App\Services\Reports\ReportTemplateSuggestionService;
use App\Support\ParserRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ParserReviewQueueController extends Controller
{
    public function __construct(private readonly ReportTemplateSuggestionService $suggestions) {}

    public function index(Request $request): View
    {
        $emails = IncomingReportEmail::query()
            ->with(['company', 'machine.client', 'machine.machineModel'])
            ->whereIn('parse_status', [
                IncomingReportEmail::STATUS_PENDING_TEMPLATE,
                IncomingReportEmail::STATUS_FAILED,
                IncomingReportEmail::STATUS_UNMATCHED,
            ])
            ->when($request->filled('status'), fn ($query) => $query->where('parse_status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';

                $query->where(function ($query) use ($term) {
                    $query->where('subject', 'like', $term)
                        ->orWhere('from_email', 'like', $term)
                        ->orWhereHas('company', fn ($query) => $query->where('name', 'like', $term))
                        ->orWhereHas('machine', fn ($query) => $query
                            ->where('serial_number', 'like', $term)
                            ->orWhere('manufacturer', 'like', $term)
                            ->orWhere('model', 'like', $term));
                });
            })
            ->latest('received_at')
            ->paginate(25)
            ->withQueryString();

        $emails->getCollection()->each(function (IncomingReportEmail $email): void {
            $email->setAttribute('ai_review_recommendation', $this->suggestions->aiReviewRecommendation($email->body_text, filled($email->machine_id)));
        });

        return view('parser-queue.index', ['emails' => $emails]);
    }

    public function show(IncomingReportEmail $incomingReportEmail): View
    {
        $incomingReportEmail->load(['company', 'machine.client', 'machine.machineModel']);
        $localConfiguration = $this->suggestions->suggestParserConfiguration($incomingReportEmail->body_text);
        $aiSuggestion = session("ai_parser_suggestions.{$incomingReportEmail->id}");

        return view('parser-queue.show', [
            'email' => $incomingReportEmail,
            'detectedFields' => $this->suggestions->detectedFields($incomingReportEmail->body_text),
            'suggestedConfiguration' => $aiSuggestion['parser_configuration'] ?? $localConfiguration,
            'suggestedParserType' => $aiSuggestion['parser_type']
                ?? $incomingReportEmail->machine?->machineModel?->parser_type
                ?? $this->suggestions->suggestParserType($incomingReportEmail->body_text),
            'aiSuggestion' => $aiSuggestion,
            'mappingReview' => $this->suggestions->reviewMapping($incomingReportEmail->body_text, $aiSuggestion['parser_configuration'] ?? $localConfiguration),
            'aiReviewRecommendation' => $this->suggestions->aiReviewRecommendation($incomingReportEmail->body_text, filled($incomingReportEmail->machine_id)),
            'parserTypes' => ParserRegistry::options(),
        ]);
    }

    public function suggestWithAi(IncomingReportEmail $incomingReportEmail, AiParserSuggestionService $aiSuggestions): RedirectResponse
    {
        $localConfiguration = $this->suggestions->suggestParserConfiguration($incomingReportEmail->body_text);

        try {
            $suggestion = $aiSuggestions->suggest($incomingReportEmail->body_text, $localConfiguration);
        } catch (Throwable $exception) {
            return back()->withErrors(['ai' => 'AI suggestion failed: '.$exception->getMessage()]);
        }

        session()->put("ai_parser_suggestions.{$incomingReportEmail->id}", [
            'email_id' => $incomingReportEmail->id,
            ...$suggestion,
        ]);

        return redirect()
            ->route('parser-queue.show', $incomingReportEmail)
            ->with('status', 'AI parser suggestion generated. Review it before approving.');
    }

    public function approveCompany(IncomingReportEmail $incomingReportEmail, Request $request, ReportProcessingService $processor, PendingReportReprocessor $reprocessor): RedirectResponse
    {
        $machine = $incomingReportEmail->machine;

        if (! $machine) {
            return back()->withErrors(['email' => 'Match this email to a machine before approving a company template.']);
        }

        $template = $this->createTemplate($incomingReportEmail, $request, $machine->machineModel, $machine->client->company_id, ReportTemplate::STATUS_COMPANY);
        $this->logApproval($incomingReportEmail, $template, 'company');
        $parsed = $this->reprocess($incomingReportEmail, $processor);
        $reprocessed = $reprocessor->forTemplate($template)->count();

        return redirect()
            ->route('parser-queue.show', $incomingReportEmail)
            ->with('status', "Company template approved. {$parsed} source email parsed. {$reprocessed} other pending email(s) parsed.");
    }

    public function approveGlobal(IncomingReportEmail $incomingReportEmail, Request $request, ReportProcessingService $processor, PendingReportReprocessor $reprocessor): RedirectResponse
    {
        $machine = $incomingReportEmail->machine;

        if (! $machine) {
            return back()->withErrors(['email' => 'Match this email to a machine before approving a global template.']);
        }

        $globalModel = $this->globalModelFor($machine->machineModel);
        $template = $this->createTemplate($incomingReportEmail, $request, $globalModel, null, ReportTemplate::STATUS_APPROVED_GLOBAL);
        $this->logApproval($incomingReportEmail, $template, 'global');
        $parsed = $this->reprocess($incomingReportEmail, $processor);
        $reprocessed = $reprocessor->forTemplate($template)->count();

        return redirect()
            ->route('parser-queue.show', $incomingReportEmail)
            ->with('status', "Global template approved. {$parsed} source email parsed. {$reprocessed} other pending email(s) parsed.");
    }

    private function reprocess(IncomingReportEmail $incomingReportEmail, ReportProcessingService $processor): int
    {
        try {
            return $processor->process($incomingReportEmail) ? 1 : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private function createTemplate(IncomingReportEmail $email, Request $request, MachineModel $machineModel, ?int $companyId, string $approvalStatus): ReportTemplate
    {
        $sessionSuggestion = session("ai_parser_suggestions.{$email->id}", []);

        $request->merge([
            'parser_type' => $request->input('parser_type') ?: $request->input('ai_parser_type') ?: ($sessionSuggestion['parser_type'] ?? null),
            'parser_configuration' => filled($request->input('parser_configuration'))
                ? $request->input('parser_configuration')
                : ($request->input('ai_parser_configuration')
                    ?: (isset($sessionSuggestion['parser_configuration']) ? json_encode($sessionSuggestion['parser_configuration']) : null)),
        ]);

        $data = $request->validate([
            'parser_type' => ['required', Rule::in(ParserRegistry::keys())],
            'parser_configuration' => ['required', 'json'],
            'ai_parser_type' => ['nullable', Rule::in(ParserRegistry::keys())],
            'ai_parser_configuration' => ['nullable', 'json'],
        ]);
        $familyKey = $this->familyKey($machineModel, $data['parser_type']);

        $template = ReportTemplate::create([
            'machine_model_id' => $machineModel->id,
            'company_id' => $companyId,
            'template_name' => trim($machineModel->manufacturer.' '.$machineModel->model_name),
            'family_key' => $familyKey,
            'version' => $this->nextVersion($familyKey, $companyId),
            'sample_subject' => $email->subject,
            'sample_body' => $email->body_text,
            'parser_type' => $data['parser_type'],
            'parser_configuration' => json_decode($data['parser_configuration'], true),
            'is_active' => true,
            'approval_status' => $approvalStatus,
            'approved_at' => $companyId === null ? now() : null,
            'approved_by' => $companyId === null ? request()->user()->id : null,
        ]);

        session()->forget("ai_parser_suggestions.{$email->id}");

        return $template;
    }

    private function logApproval(IncomingReportEmail $email, ReportTemplate $template, string $scope): void
    {
        ParserReviewLog::create([
            'incoming_report_email_id' => $email->id,
            'report_template_id' => $template->id,
            'user_id' => request()->user()->id,
            'action' => 'template_approved',
            'scope' => $scope,
            'parser_type' => $template->parser_type,
            'parser_configuration' => $template->parser_configuration,
        ]);
    }

    private function globalModelFor(MachineModel $machineModel): MachineModel
    {
        $globalModel = MachineModel::query()
            ->whereNull('company_id')
            ->where('manufacturer', $machineModel->manufacturer)
            ->where('model_name', $machineModel->model_name)
            ->first();

        if ($globalModel) {
            return $globalModel;
        }

        $manufacturer = $machineModel->manufacturerRecord
            ?? Manufacturer::findOrCreateByName($machineModel->manufacturer);

        return MachineModel::create([
            'company_id' => null,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer' => $manufacturer->name,
            'model_name' => $machineModel->model_name,
            'parser_type' => $machineModel->parser_type,
            'notes' => 'Created from parser review queue.',
        ]);
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
}
