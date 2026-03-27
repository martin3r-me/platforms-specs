<?php

namespace Platform\Specs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class SpecsAcceptanceCriterion extends Model
{
    protected $table = 'specs_acceptance_criteria';

    protected $fillable = [
        'uuid',
        'requirement_id',
        'content',
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

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(SpecsRequirement::class, 'requirement_id');
    }
}
