<?php

use Jevo\JRelations\Models\RelationType;
use Illuminate\Support\Facades\Event;

Event::listen('evolution.OnDocFormRender', function (array $params): string {
    $templateId = (int) ($params['template'] ?? 0);
    $resourceId = (int) ($params['id'] ?? 0);

    if ($templateId === 0) {
        return '';
    }

    $relationTypes = RelationType::query()
        ->whereHas('templates', static fn ($query) => $query->where('site_templates.id', $templateId))
        ->orderBy('id')
        ->get();

    if ($relationTypes->isEmpty()) {
        return '';
    }

    $managerLanguage = strtolower((string) evo()->getConfig('manager_language', 'uk'));
    $locale = str_starts_with($managerLanguage, 'en') || str_contains($managerLanguage, 'english')
        ? 'en'
        : 'uk';

    return view('jRelations::resource-tab', [
        'resourceId' => $resourceId,
        'relationTypes' => $relationTypes,
        'locale' => $locale,
    ])->render();
});
