<?php

namespace App\Wms;

use App\Concerns\EnumTrait;

enum PoStatusEnum: string
{
    use EnumTrait;

    case PENDING = 'Pending';
    case ORDERED = 'Ordered';
    case PARTIAL = 'Partial';
    case CLOSED  = 'Closed';

    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ORDERED => 'Ordered',
            self::PARTIAL => 'Partial',
            self::CLOSED  => 'Closed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'neutral',
            self::ORDERED => 'info',
            self::PARTIAL => 'warning',
            self::CLOSED  => 'success',
        };
    }
}
