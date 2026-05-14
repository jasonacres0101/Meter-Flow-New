<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomingReportEmailRequest;
use App\Models\EmailSource;
use App\Models\IncomingReportEmail;
use App\Services\Reports\IncomingEmailIngestionService;
use App\Services\Reports\ReportProcessingService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class IncomingReportEmailController extends Controller
{
    public function index(): View
    {
        return view('incoming-report-emails.index', [
            'emails' => Tenant::scope(IncomingReportEmail::query(), request()->user())->with('machine.client')->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('incoming-report-emails.create');
    }

    public function store(StoreIncomingReportEmailRequest $request, IncomingEmailIngestionService $ingestion): RedirectResponse
    {
        $email = $ingestion->store(array_merge($request->validated(), ['company_id' => Tenant::companyId($request->user())]));

        return redirect()->route('incoming-report-emails.show', $email)->with('status', 'Incoming email stored for parsing.');
    }

    public function show(IncomingReportEmail $incomingReportEmail): View
    {
        $this->authorizeTenant($incomingReportEmail);

        return view('incoming-report-emails.show', ['email' => $incomingReportEmail->load('machine.client')]);
    }

    public function edit(IncomingReportEmail $incomingReportEmail)
    {
        abort(404);
    }

    public function update(StoreIncomingReportEmailRequest $request, IncomingReportEmail $incomingReportEmail)
    {
        abort(404);
    }

    public function destroy(IncomingReportEmail $incomingReportEmail)
    {
        abort(403, 'Raw report emails are retained permanently for reprocessing.');
    }

    public function reprocess(IncomingReportEmail $incomingReportEmail, ReportProcessingService $processor): RedirectResponse
    {
        $this->authorizeTenant($incomingReportEmail);

        $processor->process($incomingReportEmail);

        return back()->with('status', 'Email reprocessed.');
    }

    public function pull(ReportProcessingService $processor): RedirectResponse
    {
        $user = request()->user();
        $before = Tenant::scope(IncomingReportEmail::query(), $user)->count();
        $beforeLatestId = Tenant::scope(IncomingReportEmail::query(), $user)->max('id') ?? 0;
        $sources = Tenant::scope(EmailSource::query(), $user)
            ->where('is_active', true)
            ->get();

        if ($sources->isEmpty()) {
            return back()->withErrors(['pull' => 'No active email sources are configured for this company.']);
        }

        foreach ($sources as $source) {
            if ($source->usesWebhookDelivery()) {
                continue;
            }

            Artisan::call(
                $source->usesMicrosoftGraph() ? 'reports:poll-microsoft-graph' : 'reports:poll-imap',
                ['--source' => $source->id]
            );
        }

        $after = Tenant::scope(IncomingReportEmail::query(), $user)->count();
        $newEmails = max(0, $after - $before);
        $processingErrors = collect();

        Tenant::scope(IncomingReportEmail::query(), $user)
            ->where('id', '>', $beforeLatestId)
            ->get()
            ->each(function (IncomingReportEmail $email) use ($processor, $processingErrors) {
                try {
                    $processor->process($email);
                } catch (\Throwable $exception) {
                    $processingErrors->push("Email {$email->id}: {$exception->getMessage()}");
                }
            });

        $errors = $sources->fresh()
            ->filter(fn (EmailSource $source) => filled($source->last_error))
            ->map(fn (EmailSource $source) => "{$source->name}: {$source->last_error}")
            ->values()
            ->merge($processingErrors);

        $status = $newEmails > 0
            ? "Manual pull finished. {$newEmails} new email(s) imported."
            : 'Manual pull finished. 0 new email(s) imported. Messages already imported from POP mailboxes are skipped on later pulls.';

        if ($errors->isNotEmpty()) {
            return back()
                ->with('status', $status)
                ->withErrors(['pull' => $errors->implode(' ')]);
        }

        return back()->with('status', $status);
    }

    private function authorizeTenant(IncomingReportEmail $email): void
    {
        abort_unless(request()->user()->isPlatformAdmin() || $email->company_id === null || $email->company_id === request()->user()->company_id, 403);
    }
}
