<?php

namespace App\Events;

use App\Models\Stock;
use Illuminate\Foundation\Events\Dispatchable;

class StockDeleted
{
    use Dispatchable;

    public function __construct(public Stock $stock) {}
}
