<?php

namespace Jevo\JRelations\Http\Controllers;

use EvolutionCMS\Models\SiteTemplate;
use Jevo\JRelations\Models\RelationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ModuleController
{
    public function index()
    {
        $this->ensure(evo()->hasPermission('exec_module'), 403);

        $language = $this->language();

        return view('jRelations::module.index', [
            'types' => RelationType::query()->with('templates')->orderBy('id')->get(),
            'templates' => SiteTemplate::query()->orderBy('templatename')->get(),
            'language' => $language,
        ]);
    }

    public function storeType(Request $request): RedirectResponse
    {
        $this->ensure(evo()->hasPermission('exec_module'), 403);

        $data = Validator::make($request->all(), [
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:resource_relation_types,slug'],
            'name_uk' => ['required', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'templates' => ['required', 'array', 'min:1'],
            'templates.*' => [
                'required',
                'integer',
                'distinct',
                'exists:site_templates,id',
            ],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $type = RelationType::query()->create([
                'slug' => $data['slug'],
                'name_uk' => $data['name_uk'],
                'name_en' => $data['name_en'] ?? null,
            ]);

            $type->templates()->sync(array_map('intval', $data['templates']));
        });

        return redirect()->route('jRelations.index')
            ->with('status', trans('jRelations::module.type_added', [], $this->language()));
    }

    public function deleteType(int $type): RedirectResponse
    {
        $this->ensure(evo()->hasPermission('exec_module'), 403);

        RelationType::query()->findOrFail($type)->delete();

        return redirect()->route('jRelations.index')
            ->with('status', trans('jRelations::module.type_deleted', [], $this->language()));
    }

    private function language(): string
    {
        $managerLanguage = strtolower((string) evo()->getConfig('manager_language', 'uk'));

        return str_starts_with($managerLanguage, 'en') || str_contains($managerLanguage, 'english')
            ? 'en'
            : 'uk';
    }

    private function ensure(bool $condition, int $status): void
    {
        if (!$condition) {
            throw new HttpException($status);
        }
    }
}
