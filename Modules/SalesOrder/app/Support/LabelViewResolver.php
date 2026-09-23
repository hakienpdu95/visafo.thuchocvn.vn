<?php

namespace Modules\SalesOrder\Support;

use Modules\SalesOrder\Models\PrintLog;

class LabelViewResolver
{
    public const DEFAULT_VIEW = 'labels.templates.visafo_100x75';

    public const DEFAULT_SIZE = '100x75';

    public function forLog(PrintLog $log): string
    {
        return $log->labelTemplate?->view_path ?: self::DEFAULT_VIEW;
    }
}
