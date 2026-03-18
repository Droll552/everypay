<?php

declare(strict_types=1);

namespace App\Domain\Charge;

enum ChargeStatus: string
{
    case Successful = 'successful';
    case Failed = 'failed';
}