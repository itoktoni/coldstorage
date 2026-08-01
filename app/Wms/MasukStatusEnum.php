<?php

namespace App\Wms;

use App\Concerns\EnumTrait;

enum MasukStatusEnum: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case PROCESS = 'process';
    case READY = 'ready';
    case COMPLETE = 'complete';

    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESS => 'Process',
            self::READY => 'Ready',
            self::COMPLETE => 'Complete',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'neutral',
            self::PROCESS => 'warning',
            self::READY => 'info',
            self::COMPLETE => 'success',
        };
    }
}
