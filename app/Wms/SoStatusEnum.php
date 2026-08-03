<?php

namespace App\Wms;

use App\Concerns\EnumTrait;

enum SoStatusEnum: string
{
    use EnumTrait;

    case PENDING   = 'Pending';
    case PREPARE   = 'Prepare';
    case CONFIRMED = 'Confirmed';
    case SHIPPED   = 'Shipped';
    case CLOSED    = 'Closed';

    public function description(): string
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::PREPARE   => 'Prepare',
            self::CONFIRMED => 'Confirmed',
            self::SHIPPED   => 'Shipped',
            self::CLOSED    => 'Closed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING   => 'neutral',
            self::PREPARE   => 'warning',
            self::CONFIRMED => 'info',
            self::SHIPPED   => 'primary',
            self::CLOSED    => 'success',
        };
    }
}
