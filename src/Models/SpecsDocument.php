<?php

namespace Platform\Specs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class SpecsDocument extends Model
{
    use LogsActivity, SoftDeletes;

    // Status-Konstanten (Funnel-Reihenfolge)
    public const STATUS_BACKLOG = 'backlog';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_BACKLOG,
        self::STATUS_IN_PROGRESS,
        self::STATUS_REVIEW,
        self::STATUS_VALIDATED,
        self::STATUS_ARCHIVED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_BACKLOG => 'Backlog',
        self::STATUS_IN_PROGRESS => 'In Arbeit',
        self::STATUS_REVIEW => 'Review',
        self::STATUS_VALIDATED => 'Abgenommen',
        self::STATUS_ARCHIVED => 'Archiviert',
    ];

    public const STATUS_ICONS = [
        self::STATUS_BACKLOG => 'heroicon-o-inbox-stack',
        self::STATUS_IN_PROGRESS => 'heroicon-o-pencil-square',
        self::STATUS_REVIEW => 'heroicon-o-eye',
        self::STATUS_VALIDATED => 'heroicon-o-check-badge',
        self::STATUS_ARCHIVED => 'heroicon-o-archive-box',
    ];

    public const STATUS_VARIANTS = [
        self::STATUS_BACKLOG => 'secondary',
        self::STATUS_IN_PROGRESS => 'warning',
        self::STATUS_REVIEW => 'info',
        self::STATUS_VALIDATED => 'success',
        self::STATUS_ARCHIVED => 'secondary',
    ];

    public const TYPE_LASTENHEFT = 'lastenheft';
    public const TYPE_PFLICHTENHEFT = 'pflichtenheft';

    public const DOCUMENT_TYPES = [
        self::TYPE_LASTENHEFT,
        self::TYPE_PFLICHTENHEFT,
    ];

    public const TYPE_LABELS = [
        self::TYPE_LASTENHEFT => 'Lastenheft',
        self::TYPE_PFLICHTENHEFT => 'Pflichtenheft',
    ];

    public const TYPE_PREFIXES = [
        self::TYPE_LASTENHEFT => 'LH',
        self::TYPE_PFLICHTENHEFT => 'PH',
    ];

    protected $table = 'specs_documents';

    protected $fillable = [
        'uuid',
        'team_id',
        'name',
        'description',
        'document_type',
        'status',
        'public_token',
        'is_public',
        'prefix',
        'next_requirement_number',
        'linked_document_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'next_requirement_number' => 'integer',
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
            if (empty($model->prefix)) {
                $model->prefix = self::TYPE_PREFIXES[$model->document_type] ?? 'REQ';
            }
        });
    }

    /**
     * Generate the next requirement ID and increment the counter.
     */
    public function generateRequirementId(): string
    {
        $number = $this->next_requirement_number;
        $this->increment('next_requirement_number');

        return sprintf('%s-%03d', $this->prefix, $number);
    }

    // Relationships

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function linkedDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_document_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SpecsSection::class, 'document_id')->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SpecsComment::class, 'document_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SpecsDocumentSnapshot::class, 'document_id')->orderBy('version', 'desc');
    }

    // Scopes

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->whereNotNull('public_token');
    }

    public function generatePublicToken(): string
    {
        $this->public_token = bin2hex(random_bytes(16));
        $this->is_public = true;
        $this->save();

        return $this->public_token;
    }

    public function getPublicUrl(): ?string
    {
        if (! $this->public_token) {
            return null;
        }

        return route('specs.public.show', $this->public_token);
    }

    /**
     * Export the full document data as an array.
     */
    public function toDocumentArray(): array
    {
        $this->loadMissing(['sections.requirements.acceptanceCriteria']);

        $sections = [];
        foreach ($this->sections as $section) {
            $requirements = [];
            foreach ($section->requirements as $req) {
                $requirements[] = [
                    'id' => $req->id,
                    'uuid' => $req->uuid,
                    'requirement_id' => $req->requirement_id,
                    'title' => $req->title,
                    'content' => $req->content,
                    'requirement_type' => $req->requirement_type,
                    'priority' => $req->priority,
                    'status' => $req->status,
                    'position' => $req->position,
                    'metadata' => $req->metadata,
                    'acceptance_criteria' => $req->acceptanceCriteria->map(fn ($ac) => [
                        'id' => $ac->id,
                        'uuid' => $ac->uuid,
                        'content' => $ac->content,
                        'position' => $ac->position,
                    ])->values()->toArray(),
                ];
            }

            $sections[] = [
                'id' => $section->id,
                'uuid' => $section->uuid,
                'title' => $section->title,
                'description' => $section->description,
                'position' => $section->position,
                'parent_id' => $section->parent_id,
                'requirements' => $requirements,
            ];
        }

        return [
            'document' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'document_type' => $this->document_type,
                'status' => $this->status,
                'prefix' => $this->prefix,
                'team_id' => $this->team_id,
                'linked_document_id' => $this->linked_document_id,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'sections' => $sections,
        ];
    }
}
