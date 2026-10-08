<?php

namespace Jevo\JRelations;

use EvolutionCMS\Models\SiteContent;
use Jevo\JRelations\Models\RelationType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class JRelationsService
{
    /**
     * @var array<int, array<string, array{type: RelationType, resources: Collection<int, SiteContent>}>>
     */
    private array $resourceCache = [];

    /**
     * Return active related resources grouped by relation type for one resource.
     *
     * @return array<string, array{type: RelationType, resources: Collection<int, SiteContent>}>
     */
    public function forResource(int $resourceId): array
    {
        if (isset($this->resourceCache[$resourceId])) {
            return $this->resourceCache[$resourceId];
        }

        $types = RelationType::query()
            ->orderBy('id')
            ->get();

        if ($types->isEmpty()) {
            return $this->resourceCache[$resourceId] = [];
        }

        $links = DB::table('resource_relation_links')
            ->where(function ($query) use ($resourceId) {
                $query->where('resource_a_id', $resourceId)
                    ->orWhere('resource_b_id', $resourceId);
            })
            ->get();

        $relatedIdsByType = [];
        foreach ($links as $link) {
            $relatedId = (int) $link->resource_a_id === $resourceId
                ? (int) $link->resource_b_id
                : (int) $link->resource_a_id;
            $relatedIdsByType[(int) $link->relation_type_id][] = $relatedId;
        }

        $result = [];
        foreach ($types as $type) {
            $ids = array_values(array_unique($relatedIdsByType[$type->id] ?? []));
            if ($ids === []) {
                continue;
            }

            $resources = SiteContent::query()
                ->active()
                ->withoutProtected()
                ->whereIn('site_content.id', $ids)
                ->orderBy('site_content.pagetitle')
                ->get();

            if ($resources->isNotEmpty()) {
                $result[$type->slug] = [
                    'type' => $type,
                    'resources' => $resources,
                ];
            }
        }

        return $this->resourceCache[$resourceId] = $result;
    }

    /**
     * Replace a resource's links of one type, preserving the canonical unordered pair.
     *
     * @param array<int, int> $relatedIds
     */
    public function sync(int $resourceId, int $relationTypeId, array $relatedIds): void
    {
        $relatedIds = array_values(array_unique(array_filter(
            array_map('intval', $relatedIds),
            static fn (int $id): bool => $id > 0 && $id !== $resourceId
        )));

        DB::transaction(function () use ($resourceId, $relationTypeId, $relatedIds): void {
            DB::table('resource_relation_links')
                ->where('relation_type_id', $relationTypeId)
                ->where(function ($query) use ($resourceId) {
                    $query->where('resource_a_id', $resourceId)
                        ->orWhere('resource_b_id', $resourceId);
                })
                ->delete();

            $rows = [];
            foreach ($relatedIds as $relatedId) {
                $rows[] = [
                    'relation_type_id' => $relationTypeId,
                    'resource_a_id' => min($resourceId, $relatedId),
                    'resource_b_id' => max($resourceId, $relatedId),
                ];
            }

            if ($rows !== []) {
                DB::table('resource_relation_links')->insert($rows);
            }
        });
    }
}
