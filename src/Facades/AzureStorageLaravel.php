<?php

namespace Hwkdo\AzureStorageLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array listBlobs(?string $prefix = null)
 * @method static array uploadFile(string $blobName, string $pathToFile)
 * @method static bool deleteBlob(string $blobName)
 * @method static \Hwkdo\AzureStorageLaravel\AzureStorageLaravel connection(string $connection)
 * @method static bool runIndexer(?string $indexerName = null)
 * @method static array getIndexerStatus(?string $indexerName = null)
 * @method static bool resetIndexer(?string $indexerName = null)
 * @method static array listIndexers()
 *
 * @see \Hwkdo\AzureStorageLaravel\AzureStorageLaravel
 */
class AzureStorageLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\AzureStorageLaravel\AzureStorageLaravel::class;
    }
}
