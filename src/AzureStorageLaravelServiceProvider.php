<?php

namespace Hwkdo\AzureStorageLaravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AzureStorageLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('azure-storage-laravel')
            ->hasConfigFile()
            ->hasMigration('create_azure_storage_laravel_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AzureStorageLaravel::class, function ($app) {
            return new AzureStorageLaravel;
        });
    }
}
