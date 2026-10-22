<?php

namespace App\Providers;

use App\Billing\BillingGateway;
use App\Billing\LocalBillingGateway;
use App\Billing\StripeBillingGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BillingGateway::class, function (): BillingGateway {
            if (config('services.billing.driver') === 'local') {
                abort_if($this->app->isProduction(), 500, 'The local billing driver cannot be used in production.');

                return new LocalBillingGateway;
            }

            return new StripeBillingGateway;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(function (): Password {
            $rule = Password::min(8)->letters()->numbers();

            return $this->app->isProduction() ? $rule->mixedCase()->uncompromised() : $rule;
        });
    }
}
