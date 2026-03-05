<?php

namespace DevOps213\SSOauthenticated\Console;

use Illuminate\Console\Command;

class InstallUserProfile extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'userprofile:install';

    /**
     * The console command description.
     */
    protected $description = 'Install UserProfile package (publish config, views, assets)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Publishing UserProfile configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'userprofile-config',
            '--force' => true,
        ]);

        $this->info('Publishing UserProfile views...');
        $this->call('vendor:publish', [
            '--tag' => 'userprofile-views',
            '--force' => true,
        ]);

        $this->info('Publishing UserProfile assets (JS)...');
        $this->call('vendor:publish', [
            '--tag' => 'userprofile-assets',
            '--force' => true,
        ]);

        $this->info('UserProfile package installed successfully!');
        $this->info('Remember to add SSO config values to your .env file.');

        return Command::SUCCESS;
    }
}
