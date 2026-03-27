<?php

namespace Platform\Specs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class SpecsSection extends Model
{
    protected $table = 'specs_sections';

    protected $fillable = [
        'uuid',
        'document_id',
        'parent_id',
        'title',
        'description',
        'position',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
        });
    }

    // Relationships

    public function document(): BelongsTo
    {
        return $this->belongsTo(SpecsDocument::class, 'document_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(SpecsRequirement::class, 'section_id')->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SpecsComment::class, 'section_id');
    }

    // Scopes

    public function scopeRootSections($query)
    {
        return $query->whereNull('parent_id');
    }
}
