@extends('manager::template.page')

@php
    $t = fn (string $key): string => trans('jRelations::module.' . $key, [], $language);
@endphp
<style>
    .row.rr-template-list {
        margin-left: 0;
        margin-right: 0;
    }
</style>

@section('content')
    <h1><x-tabler-link aria-hidden="true" /> {{ $t('title') }}</h1>
    <p style="margin-left: 5px;">{{ $t('description') }}</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="card mb-3">
        <div class="card-header"><strong>{{ $t('types_title') }}</strong></div>
        <div class="card-body">
            <form method="post" action="{{ route('jRelations.types.store') }}" class="mb-4">
                @csrf
                <div class="form-row">
                    <div class="col-md-3 mb-2">
                        <label for="rr-slug">{{ $t('slug') }}</label>
                        <input class="form-control" id="rr-slug" name="slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="100">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="rr-name-uk">{{ $t('name_uk') }}</label>
                        <input class="form-control" id="rr-name-uk" name="name_uk" required maxlength="150">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="rr-name-en">{{ $t('name_en') }}</label>
                        <input class="form-control" id="rr-name-en" name="name_en" maxlength="150">
                    </div>
                    <div class="col-12 mb-2">
                        <fieldset>
                            <legend class="h6">{{ $t('type_templates') }}</legend>
                            <p class="text-muted">{{ $t('type_templates_help') }}</p>
                            <div class="row rr-template-list">
                                @foreach ($templates as $template)
                                    <label class="col-md-4 mb-2">
                                        <input
                                            type="checkbox"
                                            name="templates[]"
                                            value="{{ $template->id }}"
                                        >
                                        {{ $template->templatename }} <small class="text-muted">#{{ $template->id }}</small>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button class="btn btn-primary" type="submit">{{ $t('add_type') }}</button>
                    </div>
                </div>
            </form>

            @forelse ($types as $type)
                <div class="border-top py-2">
                    <div class="form-row">
                        <div class="col-md-3 mb-2">
                            <strong>{{ $t('slug') }}</strong>
                            <div>{{ $type->slug }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong>{{ $t('name_uk') }}</strong>
                            <div>{{ $type->name_uk }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong>{{ $t('name_en') }}</strong>
                            <div>{{ $type->name_en }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong>{{ $t('type_templates') }}</strong>
                            <div>
                                @foreach ($type->templates as $template)
                                    {{ $template->templatename }}@unless ($loop->last), @endunless
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <form method="post" action="{{ route('jRelations.types.delete', ['type' => $type->id]) }}"
                          onsubmit="return confirm(this.dataset.confirm);" data-confirm="{{ $t('confirm_delete') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ $t('delete') }}</button>
                    </form>
                </div>
            @empty
                <p class="text-muted mb-0">{{ $t('empty_types') }}</p>
            @endforelse
        </div>
    </section>
@endsection
