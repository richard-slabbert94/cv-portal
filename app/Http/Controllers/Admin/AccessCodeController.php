<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\CvVersion;
use App\Support\Pin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccessCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/AccessCodes/Index', [
            'codes' => AccessCode::with('cvVersion:id,name')->latest()->get()->map(fn (AccessCode $code) => [
                'id' => $code->id,
                'company' => $code->company,
                'role' => $code->role,
                'pin' => Pin::format($code->pin),
                'cv_version' => $code->cvVersion->name,
                'has_cover_letter' => $code->visibleCoverLetter() !== null,
                'is_active' => $code->is_active,
                'is_usable' => $code->isUsable(),
                'expires_at' => $code->expires_at?->toDateString(),
                'view_count' => $code->view_count,
                'last_viewed_at' => $code->last_viewed_at?->diffForHumans(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AccessCodes/Form', [
            'code' => null,
            'suggestedPin' => Pin::format(Pin::generate()),
            'versions' => CvVersion::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $code = new AccessCode($data);
        $code->setPin($data['pin'])->save();

        return redirect()->route('admin.access-codes.index')
            ->with('success', "Access code created for {$code->company}.")
            ->with('pin', Pin::format($code->pin));
    }

    public function edit(AccessCode $accessCode): Response
    {
        return Inertia::render('Admin/AccessCodes/Form', [
            'code' => [
                ...$accessCode->only('id', 'cv_version_id', 'company', 'role', 'cover_letter', 'show_cover_letter', 'notes', 'is_active'),
                'pin' => Pin::format($accessCode->pin),
                'expires_at' => $accessCode->expires_at?->toDateString(),
            ],
            'versions' => CvVersion::orderBy('name')->get(['id', 'name']),
            'logs' => $accessCode->logs()->latest('created_at')->limit(50)->get()->map(fn ($log) => [
                'at' => $log->created_at->toDayDateTimeString(),
                'ip' => $log->ip,
                'user_agent' => $log->user_agent,
            ]),
        ]);
    }

    public function update(Request $request, AccessCode $accessCode): RedirectResponse
    {
        $data = $this->validated($request, $accessCode);

        $accessCode->fill($data)->setPin($data['pin'])->save();

        return redirect()->route('admin.access-codes.index')->with('success', 'Access code updated.');
    }

    public function destroy(AccessCode $accessCode): RedirectResponse
    {
        $accessCode->delete();

        return redirect()->route('admin.access-codes.index')->with('success', 'Access code deleted.');
    }

    private function validated(Request $request, ?AccessCode $existing = null): array
    {
        $request->merge(['pin' => Pin::normalize((string) $request->input('pin'))]);

        $data = $request->validate([
            'cv_version_id' => ['required', 'exists:cv_versions,id'],
            'company' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'pin' => ['required', 'string', 'min:6', 'max:16'],
            'cover_letter' => ['nullable', 'string', 'max:20000'],
            'show_cover_letter' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ], attributes: [
            'cv_version_id' => 'CV version',
            'pin' => 'PIN',
            'cover_letter' => 'cover letter',
            'show_cover_letter' => 'show cover letter',
            'is_active' => 'active',
            'expires_at' => 'expiry date',
        ]);

        $taken = AccessCode::where('pin_lookup', Pin::lookupHash($data['pin']))
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
            ->exists();
        if ($taken) {
            throw ValidationException::withMessages(['pin' => 'This PIN is already in use.']);
        }

        return $data;
    }
}
