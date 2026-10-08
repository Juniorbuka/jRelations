@php
    $t = fn (string $key): string => trans('jRelations::module.' . $key, [], $locale);
    $tabId = 'jRelationsTab';
@endphp
<style>
    #{{ $tabId }} .rr-resource-relations {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));
        gap: 1rem;
        align-items: start;
    }

    #{{ $tabId }} .rr-resource-relations > .rr-relation-type {
        box-sizing: border-box;
        width: auto;
        max-width: 100%;
        height: auto;
        min-height: 0;
        flex-shrink: 1;
        overflow: visible;
        margin: 0;
    }
</style>
<div class="tab-page" id="{{ $tabId }}">
    <h2 class="tab">{{ $t('tab_title') }}</h2>
    <script>tpSettings.addTabPage(document.getElementById(@json($tabId)));</script>

    @if ($resourceId < 1)
        <p>{{ $t('save_resource_first') }}</p>
    @elseif ($relationTypes->isEmpty())
        <p>{{ $t('no_types') }}</p>
    @else
        <input type="hidden" class="rr-token" value="{{ csrf_token() }}">
        <div class="rr-resource-relations"
             data-resource-id="{{ $resourceId }}"
             data-search-url="{{ route('jRelations.resources.search') }}"
             data-relations-url="{{ route('jRelations.resources.relations', ['resource' => $resourceId]) }}"
             data-save-url="{{ route('jRelations.resources.relations.sync', ['resource' => $resourceId, 'type' => '__TYPE__']) }}"
             data-strings="{{ json_encode([
                 'saved' => $t('relations_saved'),
                 'saving' => $t('saving'),
                 'searching' => $t('searching'),
                 'initializationError' => $t('initialization_error'),
                 'remove' => $t('remove'),
                 'empty' => $t('search_empty'),
                 'searchError' => $t('search_error'),
                 'relationsError' => $t('relations_error'),
                 'saveError' => $t('save_error'),
             ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
            @foreach ($relationTypes as $type)
                <section class="rr-relation-type card mb-3" data-type-id="{{ $type->id }}">
                    <div class="card-header">
                        <strong>{{ $locale === 'en' && $type->name_en ? $type->name_en : $type->name_uk }}</strong>
                    </div>
                    <div class="card-body">
                        <label>
                            <span class="sr-only">{{ $t('choose_resource') }}</span>
                            <input type="search" class="form-control rr-search" placeholder="{{ $t('choose_resource') }}" autocomplete="off">
                        </label>
                        <div class="rr-search-results list-group mt-1" role="listbox"></div>
                        <ul class="rr-selected list-group mt-2"></ul>
                        <button type="button" class="btn btn-primary mt-2 rr-save" disabled>{{ $t('save_relations') }}</button>
                        <span class="rr-status ml-2" role="status" aria-live="polite">{{ $t('loading_relations') }}</span>
                    </div>
                </section>
            @endforeach
        </div>

        <script>
        (function () {
            var currentScript = document.currentScript;
            var root = currentScript && currentScript.previousElementSibling &&
                currentScript.previousElementSibling.matches('.rr-resource-relations')
                ? currentScript.previousElementSibling
                : document.querySelector('.rr-resource-relations');
            if (!root || root.dataset.initialized) return;
            root.dataset.initialized = '1';

            var strings = JSON.parse(root.dataset.strings);
            var resourceId = root.dataset.resourceId;
            var token = root.parentElement.querySelector('.rr-token');
            if (!token) {
                root.querySelectorAll('.rr-status').forEach(function (status) {
                    status.textContent = strings.initializationError;
                });
                return;
            }
            var headers = {'X-CSRF-TOKEN': token.value};
            var panels = Array.prototype.slice.call(root.querySelectorAll('.rr-relation-type'));
            var selected = {};
            panels.forEach(function (panel) {
                selected[panel.dataset.typeId] = [];
            });

            function normalizeResource(item) {
                return {
                    id: Number(item.id),
                    pagetitle: item.pagetitle
                };
            }

            function renderSelected(panel) {
                var list = panel.querySelector('.rr-selected');
                list.replaceChildren();
                (selected[panel.dataset.typeId] || []).forEach(function (item) {
                    var row = document.createElement('li');
                    row.className = 'list-group-item d-flex justify-content-between align-items-center';
                    var title = document.createElement('span');
                    title.textContent = item.pagetitle;
                    var remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'btn btn-sm btn-outline-danger';
                    remove.textContent = strings.remove;
                    remove.addEventListener('click', function () {
                        selected[panel.dataset.typeId] = selected[panel.dataset.typeId].filter(function (entry) {
                            return entry.id !== item.id;
                        });
                        renderSelected(panel);
                    });
                    row.append(title, remove);
                    list.appendChild(row);
                });
            }

            function renderResults(panel, resources, errorMessage) {
                var results = panel.querySelector('.rr-search-results');
                results.replaceChildren();
                if (!resources.length) {
                    var empty = document.createElement('div');
                    empty.className = 'list-group-item text-muted';
                    empty.textContent = errorMessage || strings.empty;
                    results.appendChild(empty);
                    return;
                }
                resources.forEach(function (item) {
                    item = normalizeResource(item);
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.textContent = item.pagetitle;
                    button.addEventListener('click', function () {
                        var items = selected[panel.dataset.typeId] || [];
                        if (!items.some(function (entry) { return entry.id === item.id; })) {
                            items.push(item);
                            selected[panel.dataset.typeId] = items;
                            renderSelected(panel);
                        }
                        results.replaceChildren();
                        panel.querySelector('.rr-search').value = '';
                    });
                    results.appendChild(button);
                });
            }

            panels.forEach(function (panel) {
                var input = panel.querySelector('.rr-search');
                var timer;
                input.addEventListener('input', function () {
                    window.clearTimeout(timer);
                    var query = input.value.trim();
                    if (!query) {
                        panel.querySelector('.rr-search-results').replaceChildren();
                        return;
                    }
                    renderResults(panel, [], strings.searching);
                    timer = window.setTimeout(function () {
                        var url = new URL(root.dataset.searchUrl, window.location.href);
                        url.searchParams.set('q', query);
                        url.searchParams.set('resource_id', resourceId);
                        url.searchParams.set('type_id', panel.dataset.typeId);
                        fetch(url, {
                            credentials: 'same-origin',
                            headers: {'Accept': 'application/json'}
                        })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('Resource search failed with HTTP ' + response.status + '.');
                                }
                                return response.json();
                            })
                            .then(function (resources) { renderResults(panel, resources); })
                            .catch(function () {
                                renderResults(panel, [], strings.searchError);
                                panel.querySelector('.rr-status').textContent = strings.searchError;
                            });
                    }, 250);
                });
            });

            fetch(root.dataset.relationsUrl, {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'}
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Relations could not be loaded.');
                    return response.json();
                })
                .then(function (relations) {
                    panels.forEach(function (panel) {
                        var typeId = panel.dataset.typeId;
                        var loaded = (relations[typeId] || []).map(normalizeResource);
                        var pending = selected[typeId];
                        loaded.forEach(function (item) {
                            if (!pending.some(function (entry) { return entry.id === item.id; })) {
                                pending.push(item);
                            }
                        });
                        renderSelected(panel);
                        panel.querySelector('.rr-save').disabled = false;
                        panel.querySelector('.rr-status').textContent = '';
                    });
                })
                .catch(function () {
                    panels.forEach(function (panel) {
                        panel.querySelector('.rr-status').textContent = strings.relationsError;
                    });
                });

            panels.forEach(function (panel) {
                panel.querySelector('.rr-save').addEventListener('click', function () {
                    var button = this;
                    var status = panel.querySelector('.rr-status');
                    button.disabled = true;
                    status.textContent = strings.saving;
                    var url = root.dataset.saveUrl.replace('__TYPE__', panel.dataset.typeId);
                    var form = new URLSearchParams();
                    form.set('_token', token.value);
                    (selected[panel.dataset.typeId] || []).forEach(function (item) {
                        form.append('related_ids[]', item.id);
                    });
                    fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: Object.assign({
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }, headers),
                        body: form.toString()
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('Relations could not be saved.');
                            status.textContent = strings.saved;
                        })
                        .catch(function () { status.textContent = strings.saveError; })
                        .finally(function () { button.disabled = false; });
                });
            });
        }());
        </script>
    @endif
</div>
