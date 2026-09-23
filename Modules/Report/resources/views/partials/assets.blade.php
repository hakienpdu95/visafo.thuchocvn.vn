@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Report/resources/assets/sass/report.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite(array_merge([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
    ], $withCharts ?? false ? ['resources/js/modules/echarts.js'] : [], [
        'Modules/Report/resources/assets/js/report.js',
    ]), 'build/backend')
@endpush
