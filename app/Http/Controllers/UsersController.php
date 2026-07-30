<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Models\User;
use App\RoleEnum;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    use ControllerTrait;

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'role' => RoleEnum::getOptions(),
        ];

        return array_merge($default, $data);
    }

    public function __construct(User $model)
    {
        $this->model = $model::getModel();
    }

    public static function boot()
    {
        parent::saving(function ($model) {

            if (! empty(request()->get('password'))) {
                $model->password = Hash::make(request()->get('password'));
            }
        });
        parent::boot();
    }
}
