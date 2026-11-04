<?php

declare(strict_types=1);

namespace App\Enums;

enum Browser: string
{
    case Chrome = 'Chrome';
    case Firefox = 'Firefox';
    case Safari = 'Safari';
    case Edge = 'Edge';
    case Opera = 'Opera';
    case Other = 'Other';
}
