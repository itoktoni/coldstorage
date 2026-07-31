<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\Customer;

class CustomerController extends Controller
{
    use ControllerTrait;

    public function __construct(Customer $model)
    {
        $this->model = $model::getModel();
    }
}
