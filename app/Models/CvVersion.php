<?php

namespace App\Models;

use Database\Factories\CvVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

#[Fillable(['name', 'content'])]
class CvVersion extends Model
{
    /** @use HasFactory<CvVersionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(fn (CvVersion $version) => $version->deletePhoto());
    }

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public function accessCodes(): HasMany
    {
        return $this->hasMany(AccessCode::class);
    }

    /**
     * Photos live on the private disk (the CV is PIN-protected), so they are
     * served through a short-lived signed URL that works for companies and admins alike.
     */
    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return URL::temporarySignedRoute('cv-versions.photo', now()->addHours(12), [
            'cvVersion' => $this->id,
            'v' => $this->updated_at?->timestamp,
        ]);
    }

    public function deletePhoto(): void
    {
        if ($this->photo_path) {
            Storage::delete($this->photo_path);
        }
    }

    /** The shape every CV's content follows; used to fill gaps in older/partial records. */
    public static function blankContent(): array
    {
        return [
            'name' => '',
            'headline' => '',
            'location' => '',
            'email' => '',
            'phone' => '',
            'links' => [],
            'summary' => '',
            'highlights' => [],
            'experience' => [],
            'skills' => [],
            'projects' => [],
            'education' => [],
            'certifications' => [],
            'references' => [],
        ];
    }
}
