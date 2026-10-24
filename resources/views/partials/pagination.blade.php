@if ($paginator->hasPages())
<nav class="pagination" aria-label="Pagination">
    <span class="muted num">
        {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}@if(method_exists($paginator, 'total')) sur {{ $paginator->total() }}@endif
    </span>
    <ul>
        @if ($paginator->onFirstPage())
            <li><span class="p dis" aria-disabled="true">Précédent</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">Précédent</a></li>
        @endif

        @if(isset($elements))
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="p dis">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="p cur" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        @endif

        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant</a></li>
        @else
            <li><span class="p dis" aria-disabled="true">Suivant</span></li>
        @endif
    </ul>
</nav>
@endif
