<?php

namespace App\Services\Reports\Parsers;

use App\Models\IncomingReportEmail;
use App\Models\ReportTemplate;
use App\Services\Reports\ParsedMachineReport;

interface MachineReportParser
{
    public function parse(IncomingReportEmail $email, ?ReportTemplate $template = null): ParsedMachineReport;
}
