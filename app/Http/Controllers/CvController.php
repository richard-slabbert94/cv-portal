<?php

namespace App\Http\Controllers;

use App\Models\AccessCode;
use App\Models\CvVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CvController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->render($request->attributes->get('accessCode'));
    }

    /** Lets the admin see exactly what a company will see, without logging a view. */
    public function preview(AccessCode $accessCode): Response
    {
        return $this->render($accessCode, preview: true);
    }

    private function render(AccessCode $code, bool $preview = false): Response
    {
        return Inertia::render('Cv/Show', [
            'cv' => [...CvVersion::blankContent(), ...$code->cvVersion->content],
            'photoUrl' => $code->cvVersion->photoUrl(),
            'company' => $code->company,
            'role' => $code->role,
            'coverLetter' => $code->visibleCoverLetter(),
            'preview' => $preview,
        ]);
    }
}
