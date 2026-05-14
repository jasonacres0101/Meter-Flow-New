<?php

namespace App\Jobs;

use App\Models\IncomingReportEmail;
use App\Services\Reports\ReportProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingReportEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public IncomingReportEmail $incomingReportEmail)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ReportProcessingService $processor): void
    {
        $processor->process($this->incomingReportEmail->fresh());
    }
}
