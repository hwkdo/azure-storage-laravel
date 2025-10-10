<?php

namespace Hwkdo\AzureStorageLaravel\Commands;

use Illuminate\Console\Command;

class AzureStorageLaravelCommand extends Command
{
    public $signature = 'azure-storage-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
