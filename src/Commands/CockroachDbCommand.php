<?php

namespace YlsIdeas\CockroachDb\Commands;

use Illuminate\Console\Command;

class CockroachDbCommand extends Command
{
    public $signature = 'cockroachdb-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
