<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDemo extends Command
{
    protected $signature = 'atlas:reset {--force : Skip the confirmation prompt}';

    protected $description = 'Reset the simulator to its clean seeded demo state (drops all tables, re-migrates, re-seeds)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This wipes all data and re-seeds the demo dataset. Continue?', true)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->newLine();
        $this->info('Demo reset: 3 stores, 5 products, 15 offers. No runs, no decisions.');

        return self::SUCCESS;
    }
}
