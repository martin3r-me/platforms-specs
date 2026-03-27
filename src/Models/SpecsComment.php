<?php

namespace Platform\Specs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class SpecsComment extends Model
{
    protected $table = 'specs_comments';

    protected $fillable = [
        'uuid',
        'document_id',
        'section_id',
        'requirement_id',
        'parent_id',
        'content',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = UuidV7::generate();
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SpecsDocument::class, 'document_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SpecsSection::class, 'section_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(SpecsRequirement::class, 'requirement_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function scopeRootComments(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
