<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
        Validator::extend('min_age', function ($attribute, $value, $parameters, $validator) {
            $minAge = (!empty($parameters[0])) ? (int) $parameters[0] : 21;
            return Carbon::parse($value)->age >= $minAge;
        });
    }
}
