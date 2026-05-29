<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallDemoCommand extends Command
{
    protected $signature = 'app:install-demo';

    protected $description = 'Seed demo restaurant ACME';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => 'DemoSeeder']);

        $this->info('Demo installed: http://127.0.0.1:4600/acme');

        return self::SUCCESS;
    }
}
