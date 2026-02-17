<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'locale' => $request->session()->get('locale') ?? $request->user()?->locale ?? 'en',
            'translations' => function () use ($request) {
                $locale = $request->session()->get('locale') ?? $request->user()?->locale ?? 'en';
                $path = base_path("lang/{$locale}.json");
                return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
            },
            'ziggy' => fn() => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'midtrans_client_key' => config('services.midtrans.client_key'),
            'midtrans_is_production' => (bool) config('services.midtrans.is_production'),
        ];
    }
}
