<?php

namespace App\Providers;

use App\Enums\UserNotificationChannel;
use App\Support\RateLimiters;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setUpModel();
        $this->setUpRateLimiter();
    }

    private function setUpModel(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    private function setUpRateLimiter(): void
    {
        foreach (UserNotificationChannel::cases() as $channel) {
            RateLimiter::for(RateLimiters::USER_NOTIFICATIONS_PREFIX.":{$channel->value}", fn () => Limit::perSecond(100));
        }
    }
}
