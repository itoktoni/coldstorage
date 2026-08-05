<?php

namespace App\Wms;

use App\Concerns\EnumTrait;

enum PoStatusEnum: string
{
    use EnumTrait;

    case PENDING = 'Pending';
    case PROCESS = 'Process';
    case READY = 'Ready';
    case DONE = 'Done';

    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESS => 'Process',
            self::READY => 'Ready',
            self::DONE => 'Done',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'neutral',
            self::PROCESS => 'info',
            self::READY => 'warning',
            self::DONE => 'success',
        };
    }
}
