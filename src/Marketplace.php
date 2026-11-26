<?php

namespace LaveroAdmin\Admin\Marketplace;

use LaveroAdmin\Admin\Admin;
use LaveroAdmin\Admin\Extension;

class Marketplace extends Extension
{
    public static function boot()
    {
        static::registerRoutes();

        Admin::extend('marketplace', __CLASS__);
    }

    protected static function registerRoutes()
    {
        parent::routes(function ($router) {
            /* @var \Illuminate\Routing\Router $router */
            $router->get('marketplace', 'LaveroAdmin\Admin\Marketplace\Http\Controllers\MarketplaceController@index')->name('marketplace-index');
            $router->post('marketplace/run', 'LaveroAdmin\Admin\Marketplace\Http\Controllers\MarketplaceController@runAction')->name('marketplace-run');
            $router->post('marketplace/upload', 'LaveroAdmin\Admin\Marketplace\Http\Controllers\MarketplaceController@upload')->name('marketplace-upload');
            $router->post('marketplace/refresh', 'LaveroAdmin\Admin\Marketplace\Http\Controllers\MarketplaceController@refresh')->name('marketplace-refresh');
        });
    }

    public static function import()
    {
        parent::createMenu('Marketplace', 'marketplace', 'icon-store');
        parent::createPermission('Marketplace', 'ext.marketplace', 'marketplace*');
    }
}
