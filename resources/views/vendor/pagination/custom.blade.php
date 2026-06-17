{{-- ============================================================ --}}
{{-- I-SAVE AS: resources/views/vendor/pagination/custom.blade.php --}}
{{-- ============================================================ --}}

@if ($paginator->hasPages())
<nav class="custom-pagination">
    <div class="pagination-info">
        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
    </div>
    <div class="pagination-numbers">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="page-btn page-disabled">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-btn">‹</a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-btn page-dots">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-btn page-active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-btn">›</a>
        @else
            <span class="page-btn page-disabled">›</span>
        @endif

    </div>
</nav>

<style>
.custom-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 4px 0;
}
.pagination-info {
    font-size: 12.5px;
    color: #a090bc;
}
.pagination-numbers {
    display: flex;
    align-items: center;
    gap: 4px;
}
.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    color: #5b3d8a;
    background: #faf8ff;
    border: 1.5px solid #e8dff5;
    transition: all .14s ease;
    cursor: pointer;
}
.page-btn:hover {
    background: #f0eaf9;
    border-color: #c4a8e8;
    color: #3b0f7a;
}
.page-active {
    background: #3b0f7a !important;
    color: #fff !important;
    border-color: #3b0f7a !important;
    box-shadow: 0 3px 10px rgba(59,15,122,.25);
    cursor: default;
}
.page-disabled {
    color: #d4c5ed !important;
    background: #fdfcff !important;
    border-color: #f0eaf9 !important;
    cursor: not-allowed;
}
.page-dots {
    border: none !important;
    background: transparent !important;
    color: #c0aee0 !important;
    cursor: default;
    min-width: 20px;
}
</style>
@endif