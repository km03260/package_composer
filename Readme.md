# SSO AUthentification Package

"repositories": [
  {
    "type": "path",
    "url": "packages/213devops/ssoauthenticated"
  }
]
# composer require 213devops/ssoauthenticated:@dev
## SSO Configuration
# composer require gedivepro/ssoprofile:@dev
To enable Single Sign-On (SSO), you need to configure the package in your Laravel `.env` file.

Add the following entries:

SSO_SERVER_URL=https://your-sso-server.com

SSO_CLIENT_ID=your-client-id
SSO_CLIENT_SECRET=your-client-secret

# php artisan vendor:publish --provider="DevOps213\ssoauthenticated\ssoprofileServiceProvider" --tag=ssoprofile-assets

# php artisan vendor:publish --tag=DevOps213


php artisan vendor:publish --tag=ssoauthenticated-config
php artisan vendor:publish --tag=ssoauthenticated-views
php artisan vendor:publish --tag=ssoauthenticated-assets
