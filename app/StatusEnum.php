<?php

namespace App;

use App\Concerns\EnumTrait;

enum StatusEnum: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case REVIEW = 'review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::REVIEW => 'Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }
}
