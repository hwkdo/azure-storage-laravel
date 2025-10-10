<?php

namespace Hwkdo\AzureStorageLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\AzureStorageLaravel\AzureStorageLaravel
 */
class AzureStorageLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\AzureStorageLaravel\AzureStorageLaravel::class;
    }
}
