# ssoprofile Package

## SSO Configuration
# composer require gedivepro/ssoprofile:@dev
To enable Single Sign-On (SSO), you need to configure the package in your Laravel `.env` file.

Add the following entries:

SSO_SERVER_URL=https://your-sso-server.com

SSO_CLIENT_ID=your-client-id
SSO_CLIENT_SECRET=your-client-secret

# php artisan vendor:publish --provider="Gedivepro\ssoprofile\ssoprofileServiceProvider" --tag=ssoprofile-assets

# php artisan vendor:publish --tag=ssoprofile


php artisan vendor:publish --tag=ssoprofile-config
php artisan vendor:publish --tag=ssoprofile-views
php artisan vendor:publish --tag=ssoprofile-assets
