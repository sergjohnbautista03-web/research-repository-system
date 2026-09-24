@extends('layouts.admin')
@section('title', 'Coordinator Reports')
@section('page-title', 'Reports')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    <div class="coord-card">
        <div class="coord-head">
            <div>
                <h2>Research Reports</h2>
                <p>Generate reports by department, school year, semester, research type, and status.</p>
            </div>
            <div class="coord-actions">
                <button type="button" class="coord-btn coord-btn-soft" onclick="printCoordinatorReport()">Print</button>
                <a href="{{ route('admin.coordinator.reports.export', request()->query()) }}" class="coord-btn coord-btn-primary">Download CSV</a>
            </div>
        </div>
    </div>

    @include('admin.partials.coordinator-research-filters')
    @include('admin.partials.coordinator-research-table', ['mode' => 'reports'])
</div>

@push('scripts')
<script>
const coordinatorReportRows = @json($reportRows);

function escapeReportHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[character]));
}

function printCoordinatorReport() {
    const rows = coordinatorReportRows.map((row, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${escapeReportHtml(row.title)}</td>
            <td>${escapeReportHtml(row.author)}</td>
            <td>${escapeReportHtml(row.department)}</td>
            <td>${escapeReportHtml(row.type)}</td>
            <td>${escapeReportHtml(row.term)}</td>
            <td>${escapeReportHtml(row.status)}</td>
        </tr>
    `).join('');

    const html = `
        <html>
        <head>
            <title>Coordinator Research Report</title>
            <style>
                body{font-family:Arial,sans-serif;padding:28px;color:#1f1235;font-size:12px}
                h1{margin:0 0 4px;color:#43216f;font-size:22px}
                .meta{color:#665276;margin-bottom:14px}
                table{width:100%;border-collapse:collapse;margin-top:12px}
                th{padding:8px;border:1px solid #d9cce9;background:#f4effb;color:#3b0f63;text-align:left;font-size:10px;text-transform:uppercase}
                td{padding:8px;border:1px solid #e8e0f2;vertical-align:top}
                @media print{body{padding:16px} tr{break-inside:avoid}}
            </style>
        </head>
        <body>
            <h1>Coordinator Research Report</h1>
            <div class="meta">Generated: ${escapeReportHtml(new Date().toLocaleString())}</div>
            <table>
                <thead><tr><th>#</th><th>Title</th><th>Authors</th><th>Department</th><th>Type</th><th>Term</th><th>Status</th></tr></thead>
                <tbody>${rows || '<tr><td colspan="7">No matching records.</td></tr>'}</tbody>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob([html], {type: 'text/html'});
    const url = URL.createObjectURL(blob);
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;top:0;left:0;width:0;height:0;border:0;visibility:hidden;';
    iframe.src = url;
    document.body.appendChild(iframe);
    iframe.onload = function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        iframe.contentWindow.onafterprint = function() {
            document.body.removeChild(iframe);
            URL.revokeObjectURL(url);
        };
    };
}
</script>
@endpush
@endsection
