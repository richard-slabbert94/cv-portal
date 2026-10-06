<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CvVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvVersionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/CvVersions/Index', [
            'versions' => CvVersion::withCount('accessCodes')->orderBy('name')->get()->map(fn (CvVersion $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'headline' => $v->content['headline'] ?? '',
                'access_codes_count' => $v->access_codes_count,
                'updated_at' => $v->updated_at->diffForHumans(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CvVersions/Form', [
            'version' => ['id' => null, 'name' => '', 'content' => CvVersion::blankContent(), 'photo_url' => null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $version = CvVersion::create($this->validated($request));

        return redirect()->route('admin.cv-versions.edit', $version)->with('success', 'CV version created.');
    }

    public function edit(CvVersion $cvVersion): Response
    {
        return Inertia::render('Admin/CvVersions/Form', [
            'version' => [
                'id' => $cvVersion->id,
                'name' => $cvVersion->name,
                'content' => [...CvVersion::blankContent(), ...$cvVersion->content],
                'photo_url' => $cvVersion->photoUrl(),
            ],
        ]);
    }

    public function update(Request $request, CvVersion $cvVersion): RedirectResponse
    {
        $cvVersion->update($this->validated($request));

        return back()->with('success', 'CV version saved.');
    }

    public function duplicate(CvVersion $cvVersion): RedirectResponse
    {
        $copy = $cvVersion->replicate();
        $copy->name = $cvVersion->name.' (copy)';
        // Each version owns its own photo file, so removing one never breaks another.
        if ($cvVersion->photo_path && Storage::exists($cvVersion->photo_path)) {
            $copy->photo_path = 'cv-photos/'.Str::random(40).'.'.pathinfo($cvVersion->photo_path, PATHINFO_EXTENSION);
            Storage::copy($cvVersion->photo_path, $copy->photo_path);
        } else {
            $copy->photo_path = null;
        }
        $copy->save();

        return redirect()->route('admin.cv-versions.edit', $copy)->with('success', 'Copy created — tailor away.');
    }

    public function storePhoto(Request $request, CvVersion $cvVersion): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=100,min_height=100'],
        ]);

        $cvVersion->deletePhoto();
        $cvVersion->photo_path = $request->file('photo')->store('cv-photos');
        $cvVersion->save();

        return back()->with('success', 'Profile picture updated.');
    }

    public function destroyPhoto(CvVersion $cvVersion): RedirectResponse
    {
        $cvVersion->deletePhoto();
        $cvVersion->photo_path = null;
        $cvVersion->save();

        return back()->with('success', 'Profile picture removed.');
    }

    /** Streams the photo behind a signed URL; see CvVersion::photoUrl(). */
    public function showPhoto(CvVersion $cvVersion): StreamedResponse
    {
        abort_unless($cvVersion->photo_path && Storage::exists($cvVersion->photo_path), 404);

        return Storage::response($cvVersion->photo_path, headers: ['Cache-Control' => 'private, max-age=43200']);
    }

    public function destroy(CvVersion $cvVersion): RedirectResponse
    {
        if ($cvVersion->accessCodes()->exists()) {
            return back()->with('success', 'This version is still used by access codes, so it was not deleted.');
        }

        $cvVersion->delete();

        return redirect()->route('admin.cv-versions.index')->with('success', 'CV version deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'content.name' => ['required', 'string', 'max:255'],
            'content.headline' => ['nullable', 'string', 'max:255'],
            'content.location' => ['nullable', 'string', 'max:255'],
            'content.email' => ['nullable', 'email', 'max:255'],
            'content.phone' => ['nullable', 'string', 'max:64'],
            'content.summary' => ['nullable', 'string', 'max:5000'],
            'content.links' => ['array'],
            'content.links.*.label' => ['required', 'string', 'max:100'],
            'content.links.*.url' => ['required', 'url', 'max:500'],
            'content.highlights' => ['array'],
            'content.highlights.*' => ['string', 'max:500'],
            'content.experience' => ['array'],
            'content.experience.*.role' => ['required', 'string', 'max:255'],
            'content.experience.*.company' => ['required', 'string', 'max:255'],
            'content.experience.*.location' => ['nullable', 'string', 'max:255'],
            'content.experience.*.start' => ['nullable', 'string', 'max:50'],
            'content.experience.*.end' => ['nullable', 'string', 'max:50'],
            'content.experience.*.summary' => ['nullable', 'string', 'max:2000'],
            'content.experience.*.bullets' => ['array'],
            'content.experience.*.bullets.*' => ['string', 'max:1000'],
            'content.experience.*.tech' => ['array'],
            'content.experience.*.tech.*' => ['string', 'max:100'],
            'content.skills' => ['array'],
            'content.skills.*.group' => ['required', 'string', 'max:100'],
            'content.skills.*.items' => ['array'],
            'content.skills.*.items.*' => ['string', 'max:100'],
            'content.projects' => ['array'],
            'content.projects.*.name' => ['required', 'string', 'max:255'],
            'content.projects.*.url' => ['nullable', 'url', 'max:500'],
            'content.projects.*.description' => ['nullable', 'string', 'max:2000'],
            'content.education' => ['array'],
            'content.education.*.qualification' => ['required', 'string', 'max:255'],
            'content.education.*.institution' => ['required', 'string', 'max:255'],
            'content.education.*.start' => ['nullable', 'string', 'max:50'],
            'content.education.*.end' => ['nullable', 'string', 'max:50'],
            'content.education.*.notes' => ['nullable', 'string', 'max:1000'],
            'content.certifications' => ['array'],
            'content.certifications.*.name' => ['required', 'string', 'max:255'],
            'content.certifications.*.issuer' => ['nullable', 'string', 'max:255'],
            'content.certifications.*.year' => ['nullable', 'string', 'max:20'],
            'content.references' => ['array'],
            'content.references.*.name' => ['required', 'string', 'max:255'],
            'content.references.*.title' => ['nullable', 'string', 'max:255'],
            'content.references.*.company' => ['nullable', 'string', 'max:255'],
            'content.references.*.relationship' => ['nullable', 'string', 'max:255'],
            'content.references.*.email' => ['nullable', 'email', 'max:255'],
            'content.references.*.phone' => ['nullable', 'string', 'max:64'],
        ], attributes: $this->attributeNames());
    }

    /**
     * Readable field names for validation errors, e.g. "reference #3 name" instead of "content.references.2.name".
     *
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        $attributes = [
            'name' => 'version name',
            'content.name' => 'name',
            'content.headline' => 'headline',
            'content.location' => 'location',
            'content.email' => 'email',
            'content.phone' => 'phone',
            'content.summary' => 'summary',
            'content.highlights.*' => 'key achievement #:position',
        ];

        $lists = [
            'links' => ['link', ['label' => 'label', 'url' => 'URL']],
            'experience' => ['role', ['role' => 'job title', 'company' => 'company', 'location' => 'location', 'start' => 'start date', 'end' => 'end date', 'summary' => 'context line', 'bullets.*' => 'achievement', 'tech.*' => 'tech']],
            'skills' => ['skill group', ['group' => 'name', 'items.*' => 'skill']],
            'projects' => ['project', ['name' => 'name', 'url' => 'URL', 'description' => 'description']],
            'education' => ['education', ['qualification' => 'qualification', 'institution' => 'institution', 'start' => 'start date', 'end' => 'end date', 'notes' => 'notes']],
            'certifications' => ['certification', ['name' => 'name', 'issuer' => 'issuer', 'year' => 'year']],
            'references' => ['reference', ['name' => 'name', 'title' => 'job title', 'company' => 'company', 'relationship' => 'relationship', 'email' => 'email', 'phone' => 'phone']],
        ];

        foreach ($lists as $key => [$label, $fields]) {
            foreach ($fields as $field => $fieldLabel) {
                $suffix = str_ends_with($field, '.*') ? ' #:second-position' : '';
                $attributes["content.{$key}.*.{$field}"] = "{$label} #:position {$fieldLabel}{$suffix}";
            }
        }

        return $attributes;
    }
}
