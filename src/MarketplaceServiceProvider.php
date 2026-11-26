<?php

namespace LaveroAdmin\Admin\Marketplace;

use Illuminate\Support\ServiceProvider;
use LaveroAdmin\Admin\Marketplace\Services\LocalExtensionLoader;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        try {
            LocalExtensionLoader::registerAll($this->app);
        } catch (\Throwable) {
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lavero-admin-marketplace');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'lavero-admin-marketplace');

        Marketplace::boot();
    }
}
