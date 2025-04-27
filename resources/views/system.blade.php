@extends('layouts.main')

@section('breadcrumbs')
    {{ Breadcrumbs::render('logs.system') }}
@endsection

@section('content')
    <div class="grid">
        <div class="card card-grid min-w-full" data-datatable="false" data-datatable-page-size="10" data-datatable-state-save="false" id="system-logs-table" data-api-url="{{ route('logs.system.datatables') }}">
            <div class="card-header py-5 flex-wrap">
                <h3 class="card-title">
                    System Logs
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
                            <th class="min-w-[100px]" data-datatable-column="id">
                                <span class="sort"> <span class="sort-label"> ID </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[300px]" data-datatable-column="context">
                                <span class="sort"> <span class="sort-label"> Context </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[100px]" data-datatable-column="file_path">
                                <span class="sort"> <span class="sort-label"> File Path </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[100px]" data-datatable-column="environment">
                                <span class="sort"> <span class="sort-label"> Environment </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[100px]" data-datatable-column="level">
                                <span class="sort"> <span class="sort-label"> Level </span>
                                    <span class="sort-icon"> </span> </span>
                            </th>
                            <th class="min-w-[100px]" data-datatable-column="date">
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
        const element = document.querySelector('#system-logs-table');
        const searchInput = document.getElementById('search');

        const apiUrl = element.getAttribute('data-api-url');
        const dataTableOptions = {
            apiEndpoint: apiUrl,
            pageSize: 10,
            columns: {
                id:{
                    title: 'ID',
                    render: (item, data) => {
                        return `${data.id || 'N/A'}`;
                    }
                },
                context: {
                    title: 'Context',
                    render: (item, data) => {
                        if (!data.context) return 'N/A';

                        // Generate a unique ID for this context
                        const contextId = `context-${data.id || Math.random().toString(36).substr(2, 9)}`;

                        // Handle context data properly based on its type
                        let contextStr;
                        let preview;

                        try {
                            // Check if context is already a string
                            if (typeof data.context === 'string') {
                                contextStr = data.context;
                            }
                            // If it's an object, stringify it
                            else if (typeof data.context === 'object') {
                                contextStr = JSON.stringify(data.context, null, 2);
                            }
                            // For any other type, convert to string
                            else {
                                contextStr = String(data.context);
                            }

                            // Create a shortened preview (first 50 characters)
                            preview = contextStr.substring(0, 50) + (contextStr.length > 50 ? '...' : '');
                        } catch (e) {
                            // Fallback if any error occurs during processing
                            contextStr = 'Error processing context data';
                            preview = 'Error processing context data';
                        }

                        // Return HTML with expand/collapse functionality using Tailwind classes
                        return `
                            <div class="relative w-full">
                                <div class="flex justify-between items-center w-full">
                                    <div class="max-w-[calc(100%-50px)] whitespace-nowrap overflow-hidden text-ellipsis" id="preview-${contextId}">${preview}</div>
                                    <div class="hidden max-h-[300px] overflow-y-auto bg-gray-100 p-2.5 rounded mt-1.5 w-full" id="full-${contextId}">
                                        <pre class="m-0 whitespace-pre-wrap break-words">${contextStr}</pre>
                                    </div>
                                    <div class="flex items-center ml-2.5">
                                        <button type="button" class="btn btn-sm btn-outline btn-icon btn-info expand-property"
                                                data-property-id="${contextId}" id="expand-${contextId}">
                                            <i class="ki-duotone ki-arrow-down fs-7"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline btn-icon btn-info collapse-property hidden"
                                                data-property-id="${contextId}" id="collapse-${contextId}">
                                            <i class="ki-duotone ki-arrow-up fs-7"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                },
                file_path: {
                    title: 'File Path',
                    render: (item, data) => {
                        if (!data.file_path) return 'N/A';

                        // Extract just the filename from the path for display
                        const pathParts = data.file_path.split('/');
                        const fileName = pathParts[pathParts.length - 1];

                        return `<span title="${data.file_path}">${fileName}</span>`;
                    }
                },
                environment: {
                    title: 'Environment',
                    render: (item, data) => {
                        return `<span class="badge badge-outline badge-info">${data.environment || 'N/A'}</span>`;
                    }
                },
                level: {
                    title: 'Level',
                    render: (item, data) => {
                        let badgeClass = 'badge-info';

                        // Assign different badge colors based on log level
                        switch(data.level.toLowerCase()) {
                            case 'error':
                            case 'critical':
                            case 'alert':
                            case 'emergency':
                                badgeClass = 'badge-danger';
                                break;
                            case 'warning':
                                badgeClass = 'badge-warning';
                                break;
                            case 'notice':
                                badgeClass = 'badge-success';
                                break;
                            case 'info':
                                badgeClass = 'badge-info';
                                break;
                            case 'debug':
                                badgeClass = 'badge-dark';
                                break;
                        }

                        return `<span class="badge badge-outline ${badgeClass}">${data.level || 'N/A'}</span>`;
                    }
                },
                date: {
                    title: 'Date/Time',
                    render: (item, data) => {
                        const date = new Date(data.date);
                        return window.formatTanggalWaktuIndonesia(date);
                    }
                }
            },
        };

        let dataTable = new KTDataTable(element, dataTableOptions);

        // Add event delegation for expand/collapse buttons
        document.querySelector('#system-logs-table').addEventListener('click', function(e) {
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
