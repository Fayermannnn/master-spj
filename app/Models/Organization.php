<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Organization
 */
#[Fillable([
    'code', 'name', 'npwp', 'address', 'phone', 'email', 'is_active',
    'logo_disk', 'logo_path', 'logo_original_filename', 'logo_mime_type', 'logo_size',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasOne<NumberingSetting, $this>
     */
    public function numberingSetting(): HasOne
    {
        return $this->hasOne(NumberingSetting::class);
    }

    public function hasLogo(): bool
    {
        return $this->logo_path !== null;
    }
}
