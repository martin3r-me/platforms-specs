<?php
namespace Platform\Specs\Tools;
use Platform\Core\Contracts\{ToolContract, ToolContext, ToolMetadataContract, ToolResult};
use Platform\Specs\Models\{SpecsDocument, SpecsComment};
use Platform\Specs\Tools\Concerns\ResolvesSpecsTeam;

class ListCommentsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesSpecsTeam;
    public function getName(): string { return 'specs.comments.GET'; }
    public function getDescription(): string { return 'GET /specs/comments - Listet Kommentare eines Dokuments (threaded, mit Section/Requirement-Zuordnung). Parameter: document_id (required).'; }
    public function getSchema(): array { return ['type' => 'object', 'properties' => ['team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'], 'document_id' => ['type' => 'integer', 'description' => 'ID des Dokuments (ERFORDERLICH).']], 'required' => ['document_id']]; }
    public function execute(array $arguments, ToolContext $context): ToolResult {
        try {
            $resolved = $this->resolveTeam($arguments, $context); if ($resolved['error']) return $resolved['error']; $teamId = (int)$resolved['team_id'];
            $docId = (int)($arguments['document_id'] ?? 0); if ($docId <= 0) return ToolResult::error('VALIDATION_ERROR', 'document_id ist erforderlich.');
            $doc = SpecsDocument::query()->where('team_id', $teamId)->find($docId);
            if (!$doc) return ToolResult::error('NOT_FOUND', 'Dokument nicht gefunden (oder kein Zugriff).');
            $comments = SpecsComment::query()->where('document_id', $docId)->rootComments()->with(['replies', 'section', 'requirement'])->orderBy('created_at')->get();
            $total = SpecsComment::query()->where('document_id', $docId)->count();
            $data = $comments->map(fn (SpecsComment $c) => [
                'id' => $c->id, 'content' => $c->content,
                'section_title' => $c->section?->title, 'requirement_label' => $c->requirement?->requirement_id,
                'created_at' => $c->created_at?->toISOString(),
                'replies' => $c->replies->map(fn (SpecsComment $r) => ['id' => $r->id, 'content' => $r->content, 'created_at' => $r->created_at?->toISOString()])->values()->toArray(),
            ])->values()->toArray();
            return ToolResult::success(['document_id' => $docId, 'document_name' => $doc->name, 'total_comments' => $total, 'comments' => $data]);
        } catch (\Throwable $e) { return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage()); }
    }
    public function getMetadata(): array { return ['read_only' => true, 'category' => 'read', 'tags' => ['specs', 'comments', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true]; }
}
