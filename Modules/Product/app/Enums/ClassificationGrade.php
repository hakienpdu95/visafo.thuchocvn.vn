<?php

namespace Modules\Product\Enums;

enum ClassificationGrade: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';

    public function label(): string
    {
        return 'Loại ' . $this->value;
    }
}
