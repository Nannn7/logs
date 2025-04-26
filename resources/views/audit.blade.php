@extends('layouts.main')

@section('breadcrumbs')
    {{ Breadcrumbs::render('logs.audit') }}
@endsection

@section('content')
    <div class="grid">
        <div class="card card-grid min-w-full" data-datatable="false" data-datatable-page-size="10" data-datatable-state-save="false" id="audit-logs-table" data-api-url="{{ route('logs.audit.datatables') }}">
            <div class="card-header py-5 flex-wrap">
                <h3 class="card-title">
                    Audit Logs
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5">
                    <div class="flex">
                        <label class="input input-sm"> <i class="ki-filled ki-magnifier"> </i>
                            <input placeholder="Search Logs" id="search" type="text" value="">
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="scrollable-x-auto">
                    <table class="table table-auto table-border align-middle text-gray-700 font-medium text-sm" data-datatable-table="true">
                        <thead>
                        <tr>
                            <th class="min-w-[150px]" data-datatable-column="log_name">
                                <span class="sort"> <span class="sort-label"> Log Type </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[150px]" data-datatable-column="subject_type">
                                <span class="sort"> <span class="sort-label"> Subject Type </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[250px]" data-datatable-column="description">
                                <span class="sort"> <span class="sort-label"> Description </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[100px]" data-datatable-column="properties">
                                <span class="sort"> <span class="sort-label"> Properties </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[150px]" data-datatable-column="causer_type">
                                <span class="sort"> <span class="sort-label"> Causer Type </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[100px]" data-datatable-column="causer_id">
                                <span class="sort"> <span class="sort-label"> Causer ID </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[200px]" data-datatable-column="created_at">
                                <span class="sort"> <span class="sort-label"> Date/Time </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                    <div class="flex items-center gap-2">
                        Show
                        <select class="select select-sm w-16" data-datatable-size="true" name="perpage"> </select> per page
                    </div>
                    <div class="flex items-center gap-4">
                        <span data-datatable-info="true"> </span>
                        <div class="pagination" data-datatable-pagination="true">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const element = document.querySelector('#audit-logs-table');
        const searchInput = document.getElementById('search');

        const apiUrl = element.getAttribute('data-api-url');
        const dataTableOptions = {
            apiEndpoint: apiUrl,
            pageSize: 10,
            columns: {
                log_name: {
                    title: 'Log Type',
                    render: (item, data) => {
                        return `<span class="badge badge-light-primary">${data.log_name || 'N/A'}</span>`;
                    }
                },
                description: {
                    title: 'Description',
                },
                subject_type: {
                    title: 'Subject Type',
                    render: (item, data) => {
                        if (!data.subject_type) return 'N/A';
                        return data.subject_type.split('\\').pop();
                    }
                },
                properties:{
                    title: 'Properties',
                    render: (item, data) => {
                        if (!data.properties) return 'N/A';
                        return `<pre>${JSON.stringify(data.properties, null, 2)}</pre>`;
                    }
                },
                causer_type: {
                    title: 'Causer Type',
                    render: (item, data) => {
                        if (!data.causer_type) return 'System';
                        return data.causer_type.split('\\').pop();
                    }
                },
                causer_id: {
                    title: 'Causer ID',
                    render: (item, data) => {
                        return data.creator_name || 'System';
                    }
                },
                created_at: {
                    title: 'Date/Time',
                    render: (item, data) => {
                        const date = new Date(data.created_at);
                        return date.toLocaleString();
                    }
                }
            },
        };

        let dataTable = new KTDataTable(element, dataTableOptions);

        // Custom search functionality
        searchInput.addEventListener('input', function() {
            const searchValue = this.value.trim();
            // Reset to page 1 when searching and then perform search
            dataTable.goToPage(1);
            dataTable.search(searchValue, true);
        });

        window.dataTable = dataTable;
    </script>
@endpush
