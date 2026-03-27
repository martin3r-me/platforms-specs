<?php

namespace Platform\Specs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class SpecsTrace extends Model
{
    protected $table = 'specs_traces';

    protected $fillable = [
        'uuid',
        'source_requirement_id',
        'target_requirement_id',
        'description',
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

    public function sourceRequirement(): BelongsTo
    {
        return $this->belongsTo(SpecsRequirement::class, 'source_requirement_id');
    }

    public function targetRequirement(): BelongsTo
    {
        return $this->belongsTo(SpecsRequirement::class, 'target_requirement_id');
    }
}
