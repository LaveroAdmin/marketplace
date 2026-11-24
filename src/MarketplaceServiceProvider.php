<?php

namespace LaveroAdmin\Admin\Marketplace;

use Illuminate\Support\ServiceProvider;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lavero-admin-marketplace');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'lavero-admin-marketplace');

        Marketplace::boot();
    }
}
