@props(['items'])

<nav class="breadcrumb" aria-label="Breadcrumb">
    <ol>
        @foreach ($items as $item)
            <li>
                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
