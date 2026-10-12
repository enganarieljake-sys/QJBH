@if ($paginator->hasPages())
    <nav class="simple-pagination" role="navigation" aria-label="Pagination">
        <p class="pagination-summary">
            Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong>
        </p>

        <div class="pagination-actions">
            @if ($paginator->onFirstPage())
                <span class="pagination-button is-disabled" aria-disabled="true">Previous</span>
            @else
                <a class="pagination-button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            <span class="pagination-page">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="pagination-button" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="pagination-button is-disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@elseif ($paginator->total() > 0)
    <p class="pagination-summary pagination-only-summary">
        Showing all <strong>{{ $paginator->total() }}</strong> {{ Str::plural('record', $paginator->total()) }}
    </p>
@endif
