@extends('tenant.layouts.app')

@section('content')
    <tenant-hotel-rent-checkout
        :room='@json($room)'
        :customer='@json($customer)'
        :rent='@json($rent)'
        :payment-method-types='{{ $payment_method_types }}'
        :payment-destinations='{{ $payment_destinations }}'
        :all-series='{{ $series }}'
        :document-types-invoice='{{ $document_types_invoice }}'
        :configuration="{{\App\Models\Tenant\Configuration::getPublicConfig()}}"
        :affectation-igv-types='{{ $affectation_igv_types }}'
        :payments='{{ $payments }}'
        :rent-items='{{ $items }}'
    >
    </tenant-hotel-rent-checkout>
@endsection

@push('scripts')
<script>
    function formatInputsInTable() {
        var tables = document.querySelectorAll('table');
        tables.forEach(function(table) {
            var ths = table.querySelectorAll('th');
            if (ths.length > 3 && ths[3].innerText.includes('Monto')) {
                var inputs = table.querySelectorAll('tbody tr td:nth-child(4) input.el-input__inner');
                inputs.forEach(function(input) {
                    var val = parseFloat(input.value);
                    if (!isNaN(val) && input.value !== val.toFixed(2)) {
                        input.value = val.toFixed(2);
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target && e.target.tagName === 'INPUT' && e.target.classList.contains('el-input__inner')) {
            var td = e.target.closest('td');
            if (td && td.cellIndex === 3) {
                var table = e.target.closest('table');
                var ths = table ? table.querySelectorAll('th') : [];
                if (ths.length > 3 && ths[3].innerText.includes('Monto')) {
                    formatInputsInTable();
                }
            }
        }
    }, true);

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                formatInputsInTable();
            }
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
</script>
@endpush
