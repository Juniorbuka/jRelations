@php
    $manager = app(\EvoUI\Support\ManagerContext::class);
    $theme = $manager->theme();
    $themeMode = $manager->themeMode($theme);
    $themeClasses = $manager->themeClasses($theme);
    $themeBackground = $manager->themeBackground($theme);
    $t = fn (string $key): string => trans('jRelations::module.' . $key, [], $language);
    $oldTemplates = array_map('intval', (array) old('templates', []));
@endphp
<!doctype html>
<html
    class="evo-ui-page {{ $themeClasses }}"
    lang="{{ str_replace('_', '-', app()->getLocale() ?: 'uk') }}"
    data-theme="{{ $theme }}"
    data-theme-mode="{{ $themeMode }}"
    style="background-color: var(--evo-ui-bg, {{ $themeBackground }})"
>
<head>
    <meta charset="utf-8">
    <meta name="color-scheme" content="{{ $themeMode === 'dark' ? 'dark light' : 'light dark' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $t('title') }}</title>
    @include('evo::partials.assets')
    <style>
        .evo-ui-jrelations {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        .evo-ui-jrelations__section + .evo-ui-jrelations__section {
            margin-top: 20px;
        }

        .evo-ui-jrelations__form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            align-items: end;
        }

        .evo-ui-jrelations__templates {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 8px 16px;
            margin: 0;
            padding: 14px;
            border: 1px solid var(--evo-ui-border);
            border-radius: var(--evo-ui-radius);
        }

        .evo-ui-jrelations__template {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .evo-ui-jrelations__type {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            align-items: start;
            padding: 14px 0;
            border-top: 1px solid var(--evo-ui-border);
        }

        .evo-ui-jrelations__type:first-child {
            border-top: 0;
        }

        .evo-ui-jrelations__label {
            display: block;
            margin-bottom: 5px;
            color: var(--evo-ui-muted);
            font-size: 13px;
        }

        .evo-ui-jrelations__notice {
            margin: 0 0 16px;
            padding: 12px 14px;
            border: 1px solid var(--evo-ui-border);
            border-radius: var(--evo-ui-radius);
            background: var(--evo-ui-surface);
        }

        .evo-ui-jrelations__notice--error {
            border-color: var(--evo-ui-danger);
            color: var(--evo-ui-danger);
        }

        @media (max-width: 600px) {
            .evo-ui-jrelations {
                padding: 16px;
            }
        }
    </style>
</head>
<body
    class="evo-ui-page {{ $themeClasses }}"
    data-theme="{{ $theme }}"
    data-theme-mode="{{ $themeMode }}"
    style="background-color: var(--evo-ui-bg, {{ $themeBackground }})"
>
    <div class="evo-ui {{ $themeClasses }}" data-evo-ui-root data-theme="{{ $theme }}" data-theme-mode="{{ $themeMode }}">
        <main class="evo-ui-jrelations">
            <header class="evo-ui-surface__header">
                <div>
                    <h2>{{ $t('title') }}</h2>
                    <p>{{ $t('description') }}</p>
                </div>
                <x-evo::icon name="link" aria-hidden="true" />
            </header>

            @if (session('status'))
                <div class="evo-ui-jrelations__notice" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="evo-ui-jrelations__notice evo-ui-jrelations__notice--error" role="alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <x-evo::card class="evo-ui-jrelations__section">
                <header class="evo-ui-surface__header">
                    <h2>{{ $t('types_title') }}</h2>
                </header>

                <form method="post" action="{{ route('jRelations.types.store') }}" class="evo-ui-form-surface evo-ui-form-surface--density-compact">
                    @csrf
                    <div class="evo-ui-jrelations__form-grid">
                        <label class="evo-ui-field">
                            <span class="evo-ui-field__label">{{ $t('slug') }}</span>
                            <input
                                class="evo-ui-input"
                                id="rr-slug"
                                name="slug"
                                value="{{ old('slug') }}"
                                required
                                pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                maxlength="100"
                            >
                        </label>

                        <label class="evo-ui-field">
                            <span class="evo-ui-field__label">{{ $t('name_uk') }}</span>
                            <input class="evo-ui-input" name="name_uk" value="{{ old('name_uk') }}" required maxlength="150">
                        </label>

                        <label class="evo-ui-field">
                            <span class="evo-ui-field__label">{{ $t('name_en') }}</span>
                            <input class="evo-ui-input" name="name_en" value="{{ old('name_en') }}" maxlength="150">
                        </label>

                        <fieldset class="evo-ui-jrelations__templates">
                            <legend>{{ $t('type_templates') }}</legend>
                            <p class="evo-ui-field__hint">{{ $t('type_templates_help') }}</p>
                            @foreach ($templates as $template)
                                <label class="evo-ui-jrelations__template">
                                    <input
                                        type="checkbox"
                                        name="templates[]"
                                        value="{{ $template->id }}"
                                        @checked(in_array((int) $template->id, $oldTemplates, true))
                                    >
                                    <span>{{ $template->templatename }} <small>#{{ $template->id }}</small></span>
                                </label>
                            @endforeach
                        </fieldset>

                        <div>
                            <button class="evo-ui-btn evo-ui-btn--primary evo-ui-btn--filled" type="submit">
                                <x-evo::icon name="plus" class="evo-ui-btn__icon" />
                                <span class="evo-ui-btn__label">{{ $t('add_type') }}</span>
                            </button>
                        </div>
                    </div>
                </form>

                @forelse ($types as $type)
                    <div class="evo-ui-jrelations__type">
                        <div>
                            <span class="evo-ui-jrelations__label">{{ $t('slug') }}</span>
                            <strong>{{ $type->slug }}</strong>
                        </div>
                        <div>
                            <span class="evo-ui-jrelations__label">{{ $t('name_uk') }}</span>
                            {{ $type->name_uk }}
                        </div>
                        <div>
                            <span class="evo-ui-jrelations__label">{{ $t('name_en') }}</span>
                            {{ $type->name_en }}
                        </div>
                        <div>
                            <span class="evo-ui-jrelations__label">{{ $t('type_templates') }}</span>
                            @foreach ($type->templates as $template)
                                {{ $template->templatename }}@unless ($loop->last), @endunless
                            @endforeach
                        </div>
                        <div>
                            <form
                                method="post"
                                action="{{ route('jRelations.types.delete', ['type' => $type->id]) }}"
                                onsubmit="return confirm(this.dataset.confirm);"
                                data-confirm="{{ $t('confirm_delete') }}"
                            >
                                @csrf
                                <button class="evo-ui-btn evo-ui-btn--danger" type="submit">
                                    <x-evo::icon name="trash" class="evo-ui-btn__icon" />
                                    <span class="evo-ui-btn__label">{{ $t('delete') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="evo-ui-empty">{{ $t('empty_types') }}</p>
                @endforelse
            </x-evo::card>
        </main>
    </div>
</body>
</html>
