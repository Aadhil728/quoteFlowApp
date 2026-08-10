<?php

declare(strict_types=1);

namespace App\Enums;

enum OperatingMode: string
{
    case SingleBusiness = 'single_business';
    case Saas = 'saas';
}
