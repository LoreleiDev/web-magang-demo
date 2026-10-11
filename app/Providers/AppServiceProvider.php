<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();

        // AI Mentor hanya untuk siswa (CLAUDE.md bagian 2.1).
        Gate::define('gunakan-ai-mentor', fn (User $user) => $user->hasRole(Role::Siswa));

        // Batasi pemanggilan Gemini per siswa agar kuota/biaya terkendali.
        RateLimiter::for('ai-mentor', fn (Request $request) => Limit::perMinute(10)
            ->by((string) $request->user()?->id)
            ->response(fn () => response()->json([
                'message' => 'Terlalu banyak pertanyaan dalam waktu singkat. Tunggu sebentar, lalu coba lagi.',
            ], 429)));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Proxy yang dipercaya untuk header X-Forwarded-* (https & IP klien).
        TrustProxies::at(config('magang.trusted_proxies'));

        // Di production, semua URL yang dibuat Laravel memakai https.
        if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceHttps();
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
