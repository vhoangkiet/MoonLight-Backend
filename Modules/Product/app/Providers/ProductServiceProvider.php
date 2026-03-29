<?php

namespace Modules\Product\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Product\Repositories\Eloquent\CategoryRepository;
use Modules\Product\Repositories\Eloquent\DiscountRepository;
use Modules\Product\Repositories\Eloquent\ProductRepository;
use Modules\Product\Repositories\Eloquent\ProductVariantRepository;
use Modules\Product\Repositories\Eloquent\VoucherRepository;
use Modules\Product\Repositories\Interfaces\CategoryRepositoryInterface;
use Modules\Product\Repositories\Interfaces\DiscountRepositoryInterface;
use Modules\Product\Repositories\Interfaces\ProductRepositoryInterface;
use Modules\Product\Repositories\Interfaces\ProductVariantRepositoryInterface;
use Modules\Product\Repositories\Interfaces\VoucherRepositoryInterface;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProductServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Product';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'product';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );

        $this->app->singleton(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->singleton(
            ProductVariantRepositoryInterface::class,
            ProductVariantRepository::class
        );

        $this->app->singleton(
            DiscountRepositoryInterface::class,
            DiscountRepository::class
        );

        $this->app->singleton(
            VoucherRepositoryInterface::class,
            VoucherRepository::class
        );
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
