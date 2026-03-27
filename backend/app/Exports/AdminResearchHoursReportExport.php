<?php

namespace App\Exports;

class AdminResearchHoursReportExport extends AdminLecturerHoursSummaryExport
{
    public function __construct(array $rows, array $meta = [])
    {
        parent::__construct($rows, $meta);
    }
}
