@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="btn-row" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary" aria-disabled="true">Previous</span>
        @else
            <a class="btn btn-secondary" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif

        <span class="note">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-secondary" href="{{ $paginator->nextPageUrl() }}">Next</a>
        @else
            <span class="btn btn-secondary" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
