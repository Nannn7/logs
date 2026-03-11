@extends('layouts.main')

@section('breadcrumbs')
    {{ Breadcrumbs::render('logs.audit') }}
@endsection

@section('content')
    <div class="grid">
        <div class="card card-grid min-w-full" id="audit-logs-table"
            data-api-url="{{ route('logs.audit.datatablesAdminKredit') }}">
            <div class="card-header py-5 flex-wrap">
                <h3 class="card-title">
                    Data Audit Log
                </h3>

                <div class="flex flex-wrap gap-2 lg:gap-5">
                    <div class="flex">
                        <label class="input input-sm">
                            <i class="ki-filled ki-magnifier"></i>
                            <input placeholder="Cari ..." id="search" type="text" value="">
                        </label>
                    </div>

                    <div class="flex">
                        <select id="tipeLogFilter" class="select select-sm w-44">
                            <option value="">Semua Tipe Log</option>
                            <option value="ADK">ADK</option>
                            <option value="default">default</option>
                        </select>
                    </div>

                    <div class="flex">
                        <select id="tipeSubjectFilter" class="select select-sm w-52">
                            <option value="">Semua Tipe Subject</option>
                            <option value="DokumenJaminan">DokumenJaminan</option>
                            <option value="DokumenLegal">DokumenLegal</option>
                            <option value="DokumenPendukung">DokumenPendukung</option>
                            <option value="Asuransi">Asuransi</option>
                        </select>
                    </div>

                    <div class="flex">
                        <select id="tipeDokumenFilter" class="select select-sm w-44">
                            <option value="">Semua Tipe Dokumen</option>
                            <option value="created">created</option>
                            <option value="updated">updated</option>
                            <option value="deleted">deleted</option>
                        </select>
                    </div>

                    <div class="flex">
                        <select id="userRoleFilter" class="select select-sm w-44">
                            <option value="">Semua User Role</option>
                            <option value="User">User</option>
                            <option value="System">System</option>
                        </select>
                    </div>

                    <div class="flex">
                        <button id="resetFilter" class="btn btn-sm btn-danger">
                            <i class="ki-filled ki-arrows-circle"></i>
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="relative" style="max-height: 650px; overflow-y: auto;">
                    <table class="table table-auto table-border align-middle text-gray-700 font-medium text-sm">
                        <thead style="position: sticky; top: 0; z-index: 1; background-color: white;">
                            <tr>
                                <th data-col="log_name" style="cursor:pointer">
                                    <span class="sort"><span class="sort-label">Tipe Log</span><span
                                            class="sort-icon"></span></span>
                                </th>

                                <th data-col="subject_type" style="cursor:pointer">
                                    <span class="sort"><span class="sort-label">Tipe Subject</span><span
                                            class="sort-icon"></span></span>
                                </th>

                                <th data-col="description" style="cursor:pointer">
                                    <span class="sort"><span class="sort-label">Tipe Dokumen</span><span
                                            class="sort-icon"></span></span>
                                </th>

                                <th>Perubahan</th>

                                <th data-col="causer_type" style="cursor:pointer">
                                    <span class="sort"><span class="sort-label">User Role</span><span
                                            class="sort-icon"></span></span>
                                </th>

                                <th data-col="causer_id" style="cursor:pointer">
                                    <span class="sort"><span class="sort-label">Username</span><span
                                            class="sort-icon"></span></span>
                                </th>

                                <th data-col="created_at" style="cursor:pointer">
                                    <span class="sort"><span class="sort-label">Tanggal Log</span><span
                                            class="sort-icon"></span></span>
                                </th>
                            </tr>
                        </thead>

                        <tbody id="audit-tbody">
                            <tr>
                                <td colspan="7" class="text-center py-10 text-gray-500">
                                    <p>Memuat data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="card-footer justify-center md:justify-between flex-col md:flex-row gap-3 text-gray-600 text-2sm font-medium">
                    <div class="flex items-center gap-2">
                        Tampilkan
                        <select class="select select-sm w-16" id="pageSizeSelect">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        per halaman
                    </div>
                    <div class="flex items-center gap-4">
                        <span id="tableInfo"></span>
                        <div id="tablePagination" class="pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableEl = document.getElementById('audit-logs-table');
            const tbody = document.getElementById('audit-tbody');
            const apiUrl = tableEl.dataset.apiUrl;

            let currentPage = 1;
            let pageSize = 10;
            let sortField = 'created_at';
            let sortOrder = 'desc';
            let searchParams = {};

            function syncToUrl() {
                const url = new URL(window.location.href);
                ['search', 'tipe_log', 'tipe_subject', 'tipe_dokumen', 'user_role', 'username', 'page', 'size']
                .forEach(k => url.searchParams.delete(k));

                if (searchParams.search) url.searchParams.set('search', searchParams.search);
                if (searchParams.tipe_log) url.searchParams.set('tipe_log', searchParams.tipe_log);
                if (searchParams.tipe_subject) url.searchParams.set('tipe_subject', searchParams.tipe_subject);
                if (searchParams.tipe_dokumen) url.searchParams.set('tipe_dokumen', searchParams.tipe_dokumen);
                if (searchParams.user_role) url.searchParams.set('user_role', searchParams.user_role);
                if (searchParams.username) url.searchParams.set('username', searchParams.username);
                if (currentPage > 1) url.searchParams.set('page', currentPage);
                if (pageSize !== 10) url.searchParams.set('size', pageSize);

                window.history.replaceState({}, '', url.toString());
            }

            function readFromUrl() {
                const s = new URL(window.location.href).searchParams;

                if (s.get('search')) {
                    searchParams.search = s.get('search');
                    document.getElementById('search').value = s.get('search');
                }
                if (s.get('tipe_log')) {
                    searchParams.tipe_log = s.get('tipe_log');
                    document.getElementById('tipeLogFilter').value = s.get('tipe_log');
                }
                if (s.get('tipe_subject')) {
                    searchParams.tipe_subject = s.get('tipe_subject');
                    document.getElementById('tipeSubjectFilter').value = s.get('tipe_subject');
                }
                if (s.get('tipe_dokumen')) {
                    searchParams.tipe_dokumen = s.get('tipe_dokumen');
                    document.getElementById('tipeDokumenFilter').value = s.get('tipe_dokumen');
                }
                if (s.get('user_role')) {
                    searchParams.user_role = s.get('user_role');
                    document.getElementById('userRoleFilter').value = s.get('user_role');
                }
                if (s.get('page')) {
                    currentPage = parseInt(s.get('page')) || 1;
                }
                if (s.get('size')) {
                    pageSize = parseInt(s.get('size')) || 10;
                    document.getElementById('pageSizeSelect').value = pageSize;
                }
            }

            function fetchData() {
                tbody.innerHTML =
                    `<tr><td colspan="7" class="text-center py-10 text-gray-500"><p>Memuat data...</p></td></tr>`;

                syncToUrl();

                const params = new URLSearchParams({
                    page: currentPage,
                    size: pageSize,
                    sortField: sortField,
                    sortOrder: sortOrder,
                    search: JSON.stringify(searchParams),
                });

                fetch(`${apiUrl}?${params}`)
                    .then(r => r.json())
                    .then(res => {
                        renderTable(res.data);
                        renderPagination(res);
                        renderInfo(res);
                    })
                    .catch(() => {
                        tbody.innerHTML =
                            `<tr><td colspan="7" class="text-center py-10 text-danger">Gagal memuat data.</td></tr>`;
                    });
            }

            function renderTable(data) {
                if (!data || data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                <i class="ki-filled ki-information-5 text-4xl mb-3"></i>
                                <p>Tidak ada data ditemukan</p>
                            </td>
                        </tr>`;
                    return;
                }

                tbody.innerHTML = '';
                data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${renderTipeLog(row.log_name)}</td>
                        <td>${row.subject_type ? row.subject_type.split('\\\\').pop() : 'N/A'}</td>
                        <td>${row.description || 'N/A'}</td>
                        <td>${renderChanges(row)}</td>
                        <td>${row.causer_type ? row.causer_type.split('\\\\').pop() : 'System'}</td>
                        <td>${row.creator_name || 'System'}</td>
                        <td>${formatTanggal(row.created_at)}</td>
                    `;
                    tbody.appendChild(tr);
                });

                attachChangeListeners();
            }

            function renderTipeLog(logName) {
                if (!logName) return 'N/A';
                return `<span class="badge badge-light-primary">${logName}</span>`;
            }

            function formatTanggal(dateStr) {
                if (!dateStr) return 'N/A';
                const date = new Date(dateStr);
                if (isNaN(date)) return dateStr;
                if (typeof window.formatTanggalWaktuIndonesia === 'function') {
                    return window.formatTanggalWaktuIndonesia(date);
                }
                return date.toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }

            function renderChanges(row) {
                let properties = row.properties;
                if (!properties) return 'N/A';
                if (typeof properties === 'string') {
                    try {
                        properties = JSON.parse(properties);
                    } catch (e) {
                        return 'N/A';
                    }
                }

                const oldData = properties.old || {};
                const newData = properties.attributes || {};

                if (!Object.keys(oldData).length && !Object.keys(newData).length) return 'N/A';

                const excludedFields = ['updated_at', 'file_path', 'file_name', 'file_size', 'file_type'];
                const diffs = {};
                Object.keys(newData).forEach(key => {
                    if (!excludedFields.includes(key) &&
                        JSON.stringify(oldData[key]) !== JSON.stringify(newData[key])) {
                        diffs[key] = {
                            old: oldData[key],
                            new: newData[key]
                        };
                    }
                });

                if (!Object.keys(diffs).length) return 'No changes';

                const changeId = `change-${row.id || Math.random().toString(36).substr(2, 9)}`;
                const changeCount = Object.keys(diffs).length;
                const firstKey = Object.keys(diffs)[0];
                const preview =
                    `<span class="font-medium text-blue-700">${changeCount}</span> data diubah: <span class="font-semibold">"${firstKey}"</span>${changeCount > 1 ? ', ...' : ''}`;

                let fullHtml = '<div class="grid grid-cols-2 gap-4">';

                fullHtml +=
                    '<div class="bg-red-50 rounded-lg p-3"><div class="flex items-center gap-2 mb-3"><strong class="text-red-700 text-base">Before</strong></div><div class="space-y-2">';
                Object.keys(diffs).forEach(key => {
                    fullHtml +=
                        `<div class="bg-white rounded p-2 border-l-4 border-red-500"><div class="font-semibold text-sm text-gray-700 mb-1">${key}</div><pre class="m-0 whitespace-pre-wrap break-words text-sm text-gray-600">${JSON.stringify(diffs[key].old, null, 2)}</pre></div>`;
                });
                fullHtml += '</div></div>';

                fullHtml +=
                    '<div class="bg-green-50 rounded-lg p-3"><div class="flex items-center gap-2 mb-3"><strong class="text-green-700 text-base">After</strong></div><div class="space-y-2">';
                Object.keys(diffs).forEach(key => {
                    fullHtml +=
                        `<div class="bg-white rounded p-2 border-l-4 border-green-500"><div class="font-semibold text-sm text-gray-700 mb-1">${key}</div><pre class="m-0 whitespace-pre-wrap break-words text-sm text-gray-600">${JSON.stringify(diffs[key].new, null, 2)}</pre></div>`;
                });
                fullHtml += '</div></div></div>';

                return `
                    <div class="relative w-full">
                        <div class="flex justify-between items-center w-full gap-3">
                            <div class="flex-1 min-w-0 text-sm" id="preview-${changeId}">${preview}</div>
                            <div class="flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-outline btn-icon btn-info expand-change" data-change-id="${changeId}" id="expand-${changeId}">
                                    <i class="ki-duotone ki-arrow-down fs-7"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline btn-icon btn-info collapse-change" style="display:none" data-change-id="${changeId}" id="collapse-${changeId}">
                                    <i class="ki-duotone ki-arrow-up fs-7"></i>
                                </button>
                            </div>
                        </div>
                        <div style="display:none" class="max-h-96 overflow-y-auto rounded-lg mt-3 shadow-sm border border-gray-200" id="full-${changeId}">
                            ${fullHtml}
                        </div>
                    </div>`;
            }

            function attachChangeListeners() {
                tbody.querySelectorAll('.expand-change').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.changeId;
                        document.getElementById(`preview-${id}`).style.display = 'none';
                        document.getElementById(`full-${id}`).style.display = 'block';
                        document.getElementById(`expand-${id}`).style.display = 'none';
                        document.getElementById(`collapse-${id}`).style.display = 'inline-flex';
                    });
                });
                tbody.querySelectorAll('.collapse-change').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.changeId;
                        document.getElementById(`preview-${id}`).style.display = 'block';
                        document.getElementById(`full-${id}`).style.display = 'none';
                        document.getElementById(`expand-${id}`).style.display = 'inline-flex';
                        document.getElementById(`collapse-${id}`).style.display = 'none';
                    });
                });
            }

            function renderPagination(res) {
                const el = document.getElementById('tablePagination');
                el.innerHTML = '';
                if (res.pageCount <= 1) return;

                const prev = document.createElement('button');
                prev.className = `btn btn-sm btn-icon ${currentPage === 1 ? 'btn-light disabled' : 'btn-light'}`;
                prev.disabled = currentPage === 1;
                prev.innerHTML = '<i class="ki-filled ki-arrow-left"></i>';
                prev.onclick = () => {
                    if (currentPage > 1) {
                        currentPage--;
                        fetchData();
                    }
                };
                el.appendChild(prev);

                for (let i = 1; i <= res.pageCount; i++) {
                    if (i === 1 || i === res.pageCount || (i >= currentPage - 2 && i <= currentPage + 2)) {
                        const btn = document.createElement('button');
                        btn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-light'}`;
                        btn.textContent = i;
                        btn.onclick = () => {
                            currentPage = i;
                            fetchData();
                        };
                        el.appendChild(btn);
                    } else if (i === currentPage - 3 || i === currentPage + 3) {
                        const dots = document.createElement('span');
                        dots.className = 'px-2';
                        dots.textContent = '...';
                        el.appendChild(dots);
                    }
                }

                const next = document.createElement('button');
                next.className =
                    `btn btn-sm btn-icon ${currentPage === res.pageCount ? 'btn-light disabled' : 'btn-light'}`;
                next.disabled = currentPage === res.pageCount;
                next.innerHTML = '<i class="ki-filled ki-arrow-right"></i>';
                next.onclick = () => {
                    if (currentPage < res.pageCount) {
                        currentPage++;
                        fetchData();
                    }
                };
                el.appendChild(next);
            }

            function renderInfo(res) {
                const start = (currentPage - 1) * pageSize + 1;
                const end = Math.min(currentPage * pageSize, res.recordsFiltered);
                document.getElementById('tableInfo').textContent =
                    `Menampilkan ${res.recordsFiltered > 0 ? start : 0}–${end} dari ${res.recordsFiltered} data`;
            }

            document.querySelectorAll('th[data-col]').forEach(th => {
                th.addEventListener('click', function() {
                    const col = this.dataset.col;
                    sortField = col;
                    sortOrder = (sortField === col && sortOrder === 'asc') ? 'desc' : 'asc';
                    currentPage = 1;
                    fetchData();
                });
            });

            function debounce(fn, wait) {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), wait);
                };
            }

            document.getElementById('search').addEventListener('input', debounce(function(e) {
                searchParams.search = e.target.value;
                currentPage = 1;
                fetchData();
            }, 300));

            document.getElementById('tipeLogFilter').addEventListener('change', function(e) {
                searchParams.tipe_log = e.target.value;
                currentPage = 1;
                fetchData();
            });

            document.getElementById('tipeSubjectFilter').addEventListener('change', function(e) {
                searchParams.tipe_subject = e.target.value;
                currentPage = 1;
                fetchData();
            });

            document.getElementById('tipeDokumenFilter').addEventListener('change', function(e) {
                searchParams.tipe_dokumen = e.target.value;
                currentPage = 1;
                fetchData();
            });

            document.getElementById('userRoleFilter').addEventListener('change', function(e) {
                searchParams.user_role = e.target.value;
                currentPage = 1;
                fetchData();
            });

            document.getElementById('resetFilter').addEventListener('click', function() {
                document.getElementById('search').value = '';
                document.getElementById('tipeLogFilter').value = '';
                document.getElementById('tipeSubjectFilter').value = '';
                document.getElementById('tipeDokumenFilter').value = '';
                document.getElementById('userRoleFilter').value = '';
                searchParams = {};
                currentPage = 1;

                const url = new URL(window.location.href);
                ['search', 'tipe_log', 'tipe_subject', 'tipe_dokumen', 'user_role', 'username', 'page',
                    'size'
                ]
                .forEach(k => url.searchParams.delete(k));
                window.history.replaceState({}, '', url.toString());
                fetchData();
            });

            document.getElementById('pageSizeSelect').addEventListener('change', function(e) {
                pageSize = parseInt(e.target.value);
                currentPage = 1;
                fetchData();
            });

            readFromUrl();
            fetchData();
        });
    </script>
@endpush
