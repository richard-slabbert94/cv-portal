<?php

namespace App\Models;

use App\Support\Pin;
use Database\Factories\AccessCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cv_version_id', 'company', 'role', 'cover_letter', 'show_cover_letter', 'notes', 'is_active', 'expires_at'])]
#[Hidden(['pin', 'pin_lookup'])]
class AccessCode extends Model
{
    /** @use HasFactory<AccessCodeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'pin' => 'encrypted',
            'show_cover_letter' => 'boolean',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function cvVersion(): BelongsTo
    {
        return $this->belongsTo(CvVersion::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function setPin(string $pin): static
    {
        $this->pin = Pin::normalize($pin);
        $this->pin_lookup = Pin::lookupHash($pin);

        return $this;
    }

    public static function findByPin(string $pin): ?self
    {
        return static::where('pin_lookup', Pin::lookupHash($pin))->first();
    }

    /** The cover letter a company should see, or null when there is none or it is switched off. */
    public function visibleCoverLetter(): ?string
    {
        return $this->show_cover_letter && filled($this->cover_letter) ? $this->cover_letter : null;
    }

    public function isUsable(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
