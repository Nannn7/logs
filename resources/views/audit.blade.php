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

                        // Generate a unique ID for this property
                        const propertyId = `property-${data.id || Math.random().toString(36).substr(2, 9)}`;

                        // Create a shortened preview (first 50 characters)
                        const preview = JSON.stringify(data.properties).substring(0, 50) + (JSON.stringify(data.properties).length > 50 ? '...' : '');

                        // Return HTML with expand/collapse functionality using Tailwind classes
                        return `
                            <div class="relative w-full">
                                <div class="flex justify-between items-center w-full">
                                    <div class="max-w-[calc(100%-50px)] whitespace-nowrap overflow-hidden text-ellipsis" id="preview-${propertyId}">${preview}</div>
                                    <div class="hidden max-h-[300px] overflow-y-auto bg-gray-100 p-2.5 rounded mt-1.5 w-full" id="full-${propertyId}">
                                        <pre class="m-0 whitespace-pre-wrap break-words">${JSON.stringify(data.properties, null, 2)}</pre>
                                    </div>
                                    <div class="flex items-center ml-2.5">
                                        <button type="button" class="btn btn-sm btn-outline btn-icon btn-info expand-property"
                                                data-property-id="${propertyId}" id="expand-${propertyId}">
                                            <i class="ki-duotone ki-arrow-down fs-7"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline btn-icon btn-info collapse-property hidden"
                                                data-property-id="${propertyId}" id="collapse-${propertyId}">
                                            <i class="ki-duotone ki-arrow-up fs-7"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
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
                        return window.formatTanggalWaktuIndonesia(date)
                    }
                }
            },
        };

        let dataTable = new KTDataTable(element, dataTableOptions);

        // Add event delegation for expand/collapse buttons
        document.querySelector('#audit-logs-table').addEventListener('click', function(e) {
            // Handle expand button click
            if (e.target.closest('.expand-property')) {
                const button = e.target.closest('.expand-property');
                const propertyId = button.getAttribute('data-property-id');

                // Show full property and hide preview
                document.getElementById(`preview-${propertyId}`).style.display = 'none';
                document.getElementById(`full-${propertyId}`).style.display = 'block';

                // Toggle buttons
                document.getElementById(`expand-${propertyId}`).style.display = 'none';
                document.getElementById(`collapse-${propertyId}`).style.display = 'inline-flex';
            }

            // Handle collapse button click
            if (e.target.closest('.collapse-property')) {
                const button = e.target.closest('.collapse-property');
                const propertyId = button.getAttribute('data-property-id');

                // Hide full property and show preview
                document.getElementById(`preview-${propertyId}`).style.display = 'block';
                document.getElementById(`full-${propertyId}`).style.display = 'none';

                // Toggle buttons
                document.getElementById(`expand-${propertyId}`).style.display = 'inline-flex';
                document.getElementById(`collapse-${propertyId}`).style.display = 'none';
            }
        });

        // Custom search functionality
        searchInput.addEventListener('input', function() {
            const searchValue = this.value.trim();
            // Reset to page 1 when searching and then perform search
            dataTable.goPage(1);
            dataTable.search(searchValue, true);
        });

        window.dataTable = dataTable;
    </script>
@endpush
