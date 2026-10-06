<?php

namespace App\Http\Controllers;

use App\Models\AccessCode;
use App\Support\Pin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UnlockController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (AccessCode::find($request->session()->get('access_code_id'))?->isUsable()) {
            return redirect()->route('cv.show');
        }

        return Inertia::render('Unlock');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['pin' => ['required', 'string', 'max:32']], attributes: ['pin' => 'access code']);

        $code = AccessCode::findByPin($request->string('pin'));

        // One generic message for "wrong", "expired" and "disabled" so nothing leaks.
        if (! $code?->isUsable() || strlen(Pin::normalize($request->string('pin'))) < 4) {
            throw ValidationException::withMessages([
                'pin' => 'That code is not valid. Please check it and try again.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('access_code_id', $code->id);

        $code->logs()->create([
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);
        $code->increment('view_count', 1, ['last_viewed_at' => now()]);

        return redirect()->route('cv.show');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('access_code_id');
        $request->session()->regenerate();

        return redirect()->route('unlock');
    }
}
