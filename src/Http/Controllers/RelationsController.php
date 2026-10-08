<?php

namespace Jevo\JRelations\Http\Controllers;

use EvolutionCMS\Legacy\Permissions;
use EvolutionCMS\Models\SiteContent;
use Jevo\JRelations\Models\RelationTemplate;
use Jevo\JRelations\Models\RelationType;
use Jevo\JRelations\JRelationsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RelationsController
{
    public function __construct(
        private readonly JRelationsService $relations
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $this->assertCanEditDocuments();

        $data = Validator::make($request->all(), [
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'resource_id' => ['required', 'integer', 'min:1'],
            'type_id' => ['required', 'integer', 'min:1'],
        ])->validate();
        $document = $this->assertCanEditResource((int) $data['resource_id']);
        $this->ensure(
            RelationType::query()
                ->whereKey((int) $data['type_id'])
                ->whereHas('templates', static fn ($query) => $query->where(
                    'site_templates.id',
                    (int) $document->template
                ))
                ->exists(),
            403
        );

        $resources = SiteContent::query()
            ->select('site_content.id', 'site_content.pagetitle')
            ->where('site_content.deleted', 0)
            ->where('site_content.id', '<>', (int) $data['resource_id'])
            ->where('site_content.pagetitle', 'like', '%' . addcslashes($data['q'], '\\%_') . '%')
            ->withoutProtected()
            ->distinct()
            ->orderBy('site_content.pagetitle')
            ->limit(20)
            ->get()
            ->map(static fn (SiteContent $resource): array => [
                'id' => (int) $resource->id,
                'pagetitle' => (string) $resource->pagetitle,
            ])
            ->values();

        return response()->json($resources);
    }

    public function index(int $resource): JsonResponse
    {
        $this->assertCanEditDocuments();
        $this->assertCanEditResource($resource);

        $links = DB::table('resource_relation_links')
            ->where(function ($query) use ($resource) {
                $query->where('resource_a_id', $resource)
                    ->orWhere('resource_b_id', $resource);
            })
            ->get();

        $idsByType = [];
        foreach ($links as $link) {
            $otherId = (int) $link->resource_a_id === $resource
                ? (int) $link->resource_b_id
                : (int) $link->resource_a_id;
            $idsByType[(int) $link->relation_type_id][] = $otherId;
        }

        $result = [];
        foreach ($idsByType as $typeId => $ids) {
            $result[$typeId] = SiteContent::query()
                ->select('site_content.id', 'site_content.pagetitle')
                ->active()
                ->whereIn('site_content.id', array_values(array_unique($ids)))
                ->withoutProtected()
                ->distinct()
                ->orderBy('site_content.pagetitle')
                ->get();
        }

        return response()->json($result);
    }

    public function sync(Request $request, int $resource, int $type): JsonResponse
    {
        $this->assertCanEditDocuments();
        $document = $this->assertCanEditResource($resource);

        $this->ensure(
            RelationTemplate::query()->where('template_id', $document->template)->exists(),
            403
        );

        $relationType = RelationType::query()
            ->whereKey($type)
            ->whereHas('templates', static fn ($query) => $query->where(
                'site_templates.id',
                (int) $document->template
            ))
            ->firstOrFail();

        $data = Validator::make($request->all(), [
            'related_ids' => ['nullable', 'array', 'max:100'],
            'related_ids.*' => ['integer', 'distinct', 'min:1'],
        ])->validate();
        $relatedIds = array_values(array_unique(array_map('intval', $data['related_ids'] ?? [])));
        $this->ensure(!in_array($resource, $relatedIds, true), 422);

        if ($relatedIds !== []) {
            $accessibleCount = SiteContent::query()
                ->select('site_content.id')
                ->where('site_content.deleted', 0)
                ->whereIn('site_content.id', $relatedIds)
                ->withoutProtected()
                ->distinct()
                ->count('site_content.id');
            $this->ensure($accessibleCount === count($relatedIds), 422);
        }

        $this->relations->sync($resource, $relationType->id, $relatedIds);

        return response()->json(['saved' => true]);
    }

    private function assertCanEditDocuments(): void
    {
        $this->ensure(evo()->hasPermission('edit_document'), 403);
    }

    private function assertCanEditResource(int $resourceId): SiteContent
    {
        $document = SiteContent::query()
            ->where('site_content.id', $resourceId)
            ->where('site_content.deleted', 0)
            ->firstOrFail();

        $permissions = new Permissions();
        $permissions->user = evo()->getLoginUserID('mgr');
        $permissions->document = $resourceId;
        $permissions->role = $_SESSION['mgrRole'] ?? 0;

        $this->ensure($permissions->checkPermissions(), 403);

        return $document;
    }

    private function ensure(bool $condition, int $status): void
    {
        if (!$condition) {
            throw new HttpException($status);
        }
    }
}
