<?php

namespace Jevo\JRelations;

use EvolutionCMS\ServiceProvider;
use Illuminate\Support\Facades\View;

class JRelationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadPluginsFrom(dirname(__DIR__) . '/plugins/');
        $this->app->singleton(JRelationsService::class);
        $this->app->registerRoutingModule(
            'Зв’язки ресурсів',
            dirname(__DIR__) . '/routes.php',
            'tabler-link'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(dirname(__DIR__) . '/resources/views', 'jRelations');
        $this->loadTranslationsFrom(dirname(__DIR__) . '/lang', 'jRelations');
        $this->loadMigrationsFrom(dirname(__DIR__) . '/migrations');

        View::composer('*', function ($view): void {
            $evo = evo();
            if (!$evo->isFrontend() || (int) $evo->documentIdentifier < 1) {
                return;
            }

            $view->with([
                'jRelations' => $this->app->make(JRelationsService::class)
                    ->forResource((int) $evo->documentIdentifier),
            ]);
        });
    }
}
