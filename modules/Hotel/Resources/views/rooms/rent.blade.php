@extends('tenant.layouts.app')

@section('content')
    <tenant-hotel-rent 
        :room='@json($room)' 
        :affectation-igv-types='@json($affectation_igv_types)'
        :all-series='{{ $series }}'
    ></tenant-hotel-rent>
@endsection

@push('scripts')
<script>
(function () {
    var phpInputTime  = '{{ now()->format("H:i") }}';
    var phpOutputTime = '{{ now()->addHours(8)->format("H:i") }}';
    var phpInputDate  = '{{ now()->format("Y-m-d") }}';

    var attempts = 0;
    var timer = setInterval(function () {
        if (++attempts > 60) return clearInterval(timer);

        function findComp(vm) {
            if (vm && vm.form && 'input_time' in vm.form) return vm;
            var children = (vm && vm.$children) || [];
            for (var i = 0; i < children.length; i++) {
                var found = findComp(children[i]);
                if (found) return found;
            }
            return null;
        }

        var nodes = document.querySelectorAll('*');
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].__vue__) {
                var comp = findComp(nodes[i].__vue__.$root);
                if (comp) {
                    comp.form.input_time  = phpInputTime;
                    comp.form.output_time = phpOutputTime;
                    comp.form.input_date  = phpInputDate;
                    clearInterval(timer);
                }
                break;
            }
        }
    }, 200);
}());
</script>
@endpush
