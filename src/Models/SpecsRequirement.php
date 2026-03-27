<?php

namespace Platform\Specs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class SpecsRequirement extends Model
{
    use LogsActivity, SoftDeletes;

    public const TYPE_FUNCTIONAL = 'functional';
    public const TYPE_NON_FUNCTIONAL = 'non_functional';
    public const TYPE_CONSTRAINT = 'constraint';
    public const TYPE_USER_STORY = 'user_story';
    public const TYPE_USE_CASE = 'use_case';

    public const REQUIREMENT_TYPES = [
        self::TYPE_FUNCTIONAL,
        self::TYPE_NON_FUNCTIONAL,
        self::TYPE_CONSTRAINT,
        self::TYPE_USER_STORY,
        self::TYPE_USE_CASE,
    ];

    public const TYPE_LABELS = [
        self::TYPE_FUNCTIONAL => 'Funktionale Anforderung',
        self::TYPE_NON_FUNCTIONAL => 'Nicht-funktionale Anforderung',
        self::TYPE_CONSTRAINT => 'Rahmenbedingung',
        self::TYPE_USER_STORY => 'User Story',
        self::TYPE_USE_CASE => 'Use Case',
    ];

    public const PRIORITY_MUST = 'must';
    public const PRIORITY_SHOULD = 'should';
    public const PRIORITY_COULD = 'could';
    public const PRIORITY_WONT = 'wont';

    public const PRIORITIES = [
        self::PRIORITY_MUST,
        self::PRIORITY_SHOULD,
        self::PRIORITY_COULD,
        self::PRIORITY_WONT,
    ];

    public const PRIORITY_LABELS = [
        self::PRIORITY_MUST => 'Muss',
        self::PRIORITY_SHOULD => 'Soll',
        self::PRIORITY_COULD => 'Kann',
        self::PRIORITY_WONT => 'Wird nicht',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IMPLEMENTED = 'implemented';
    public const STATUS_VERIFIED = 'verified';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_IMPLEMENTED,
        self::STATUS_VERIFIED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Entwurf',
        self::STATUS_APPROVED => 'Abgenommen',
        self::STATUS_IMPLEMENTED => 'Umgesetzt',
        self::STATUS_VERIFIED => 'Verifiziert',
    ];

    protected $table = 'specs_requirements';

    protected $fillable = [
        'uuid',
        'section_id',
        'requirement_id',
        'title',
        'content',
        'requirement_type',
        'priority',
        'status',
        'position',
        'metadata',
        'created_by_user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(SpecsSection::class, 'section_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function acceptanceCriteria(): HasMany
    {
        return $this->hasMany(SpecsAcceptanceCriterion::class, 'requirement_id')->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SpecsComment::class, 'requirement_id');
    }

    public function sourceTraces(): HasMany
    {
        return $this->hasMany(SpecsTrace::class, 'source_requirement_id');
    }

    public function targetTraces(): HasMany
    {
        return $this->hasMany(SpecsTrace::class, 'target_requirement_id');
    }
}
