@if ($relations !== [])
    @foreach ($relations as $group)
        <section class="resource-relations resource-relations--{{ $group['type']->slug }}">
            <h2>{{ $locale === 'en' && $group['type']->name_en ? $group['type']->name_en : $group['type']->name_uk }}</h2>
            <ul>
                @foreach ($group['resources'] as $resource)
                    <li><a href="{{ evo()->makeUrl($resource->id) }}">{{ $resource->pagetitle }}</a></li>
                @endforeach
            </ul>
        </section>
    @endforeach
@endif
