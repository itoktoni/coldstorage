<?php

namespace App\Providers;

use App\Events\NotificationSent;
use App\Listeners\SendNotificationViaCentrifugo;
use App\Models\Menu;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Blaze\Blaze;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerMacros();

        Event::listen(NotificationSent::class, SendNotificationViaCentrifugo::class);

        // URL::forceScheme('https');
        // Blaze::optimize()->in(resource_path('views/components'));

        Blade::directive('bind', function ($expression) {
            return "<?php
                global \$activeBladeModel;
                \$activeBladeModel = $expression;
            ?>";
        });

        Blade::directive('endbind', function () {
            return '<?php
                global $activeBladeModel;
                $activeBladeModel = null;
            ?>';
        });

        // CMS Helper Directives
        Blade::directive('cmsField', function ($expression) {
            return "<?php echo \\App\\Helpers\\CmsHelper::getField($expression); ?>";
        });

        // Share main menu and footer menu with all frontend views
        View::composer('frontend.*', function ($view) {
            $view->with('menu', Menu::getByLocation('main'));
            $view->with('footerMenu', Menu::getByLocation('footer'));
        });
    }

    protected function registerMacros(): void
    {
        $macro = function ($callback = null) {
            $sql = $this->toSql();
            $bindings = $this->getBindings();

            foreach ($bindings as $binding) {
                if (is_null($binding)) {
                    $value = 'null';
                } elseif (is_bool($binding)) {
                    $value = $binding ? 'true' : 'false';
                } elseif (is_numeric($binding)) {
                    $value = (string) $binding;
                } else {
                    $value = "'" . addslashes($binding) . "'";
                }
                $sql = preg_replace('/\?/', $value, $sql, 1);
            }

            if ($callback) {
                $callback($sql);
                return $this;
            }

            return $sql;
        };

        QueryBuilder::macro('showSql', $macro);
        EloquentBuilder::macro('showSql', $macro);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
