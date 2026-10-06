<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'admin' => $request->user()?->only('name', 'email'),
            'owner' => [
                'name' => config('cv.owner_name'),
                'contactUrl' => config('cv.contact_url'),
                'contactLabel' => config('cv.contact_label'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'pin' => fn () => $request->session()->get('pin'),
            ],
        ];
    }
}
