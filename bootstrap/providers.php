<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ModelAliasServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ModelAliasServiceProvider::class,
];
