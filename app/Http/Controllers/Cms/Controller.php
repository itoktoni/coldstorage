<?php

namespace App\Http\Controllers\Cms;

abstract class Controller
{
    protected function share($data = [])
    {
        return $data;
    }
}
