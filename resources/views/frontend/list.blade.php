@if ($relations !== [])
    @foreach ($relations as $key => $resources)
        <section class="resource-relations resource-relations--{{ $key }}">
            <ul>
                @foreach ($resources as $resource)
                    <li><a href="{{ evo()->makeUrl($resource->id) }}">{{ $resource->pagetitle }}</a></li>
                @endforeach
            </ul>
        </section>
    @endforeach
@endif
