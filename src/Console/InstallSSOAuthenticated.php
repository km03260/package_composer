<?php

namespace DevOps213\SSOauthenticated\Console;

use Illuminate\Console\Command;

class InstallSSOAuthenticated extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ssoauth:install';

    /**
     * The console command description.
     */
    protected $description = 'Install ssoauth package (publish config, views, assets)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Publishing ssoauth configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'ssoauth-config',
            '--force' => true,
        ]);

        $this->info('Publishing ssoauth views...');
        $this->call('vendor:publish', [
            '--tag' => 'ssoauth-views',
            '--force' => true,
        ]);

        $this->info('Publishing ssoauth assets (JS)...');
        $this->call('vendor:publish', [
            '--tag' => 'ssoauth-assets',
            '--force' => true,
        ]);

        $this->info('ssoauth package installed successfully!');
        $this->info('Remember to add SSO config values to your .env file.');

        return Command::SUCCESS;
    }
}
