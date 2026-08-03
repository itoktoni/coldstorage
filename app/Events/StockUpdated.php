<?php

namespace App\Events;

use App\Models\Stock;
use Illuminate\Foundation\Events\Dispatchable;

class StockUpdated
{
    use Dispatchable;

    public function __construct(public Stock $stock, public array $original) {}
}
