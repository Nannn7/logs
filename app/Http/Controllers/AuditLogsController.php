<?php

    namespace Modules\Logs\Http\Controllers;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use Modules\Usermanagement\Models\User;
    use Spatie\Activitylog\Models\Activity;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;
    use Modules\Adk\Models\JenisBuktiKepemilikan;

    class AuditLogsController extends Controller
    {
        protected $user;

        public function __construct()
        {
            // Mengatur middleware auth
            $this->middleware('auth');

            // Mengatur user setelah middleware auth dijalankan
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
        }

        /**
         * Display a listing of the resource.
         */
        public function index()
        {
            // Check if the authenticated user has the required permission to view audit logs
            if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
                abort(403, 'Sorry! You are not allowed to view audit logs.');
            }

            return view('logs::audit');
        }

        public function indexAdminKredit()
        {
            // Check if the authenticated user has the required permission to view audit logs
            if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
                abort(403, 'Sorry! You are not allowed to view audit logs.');
            }

            return view('logs::adminkredit');
        }

        public function datatableAdminKredit(Request $request)
        {
            // Check if the authenticated user has the required permission to view audit logs
            if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
                abort(403, 'Sorry! You are not allowed to view audit logs.');
            }

            // Retrieve data from the database
            $query = Activity::query()
            ->where('log_name','ADK')
            ->where('subject_type', 'LIKE', '%Dokumen%');

            // Apply search filter if provided
            if ($request->has('search') && !empty($request->get('search'))) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('log_name', 'LIKE', "%$search%")
                      ->orWhere('description', 'LIKE', "%$search%")
                      ->orWhere('subject_id', 'LIKE', "%$search%")
                      ->orWhere('subject_type', 'LIKE', "%$search%")
                      ->orWhere('causer_id', 'LIKE', "%$search%")
                      ->orWhere('properties', 'LIKE', "%$search%");
                });
            }

            // Apply sorting if provided
            if ($request->has('sortOrder') && !empty($request->get('sortOrder'))) {
                $order  = $request->get('sortOrder');
                $column = $request->get('sortField');
                $query->orderBy($column, $order);
            } else {
                // Default sorting by created_at descending
                $query->orderBy('created_at', 'desc');
            }

            // Get the total count of records before pagination
            $totalRecords = Activity::count();

            // Get the filtered count before pagination
            $filteredRecords = $query->count();

            // Apply pagination if provided
            if ($request->has('page') && $request->has('size')) {
                $page   = $request->get('page');
                $size   = $request->get('size');
                $offset = ($page - 1) * $size; // Calculate the offset

                $query->skip($offset)->take($size);
            }

            // Get the data for the current page
            $data = $query->get();

            // Map causer_id to creator name
            $data = $data->map(function ($item) {
    // Map creator name first
    if ($item->causer_id && $item->causer_type === 'Modules\\Usermanagement\\Models\\User') {
        $user = User::find($item->causer_id);
        $item->creator_name = $user ? $user->name : 'Unknown User';
    } else {
        $item->creator_name = 'System';
    }

    // Apply filtering ONLY for adminkredit role
    if (auth()->user()->hasRole('adminkredit')) {

        // For adminkredit, we ONLY care about specific field changes

        // Handle DokumenJaminan - ONLY show if jenis_bukti_kepemilikan_id changed
        if ($item->subject_type === 'Modules\\Adk\\Models\\DokumenJaminan') {
            if ($item->properties) {
                $props = json_decode($item->properties, true);

                if (isset($props['old']['jenis_bukti_kepemilikan_id'],
                         $props['attributes']['jenis_bukti_kepemilikan_id'])) {

                    $oldValue = $props['old']['jenis_bukti_kepemilikan_id'];
                    $newValue = $props['attributes']['jenis_bukti_kepemilikan_id'];

                    // Only return if this specific field changed
                    if ($oldValue !== $newValue) {
                        $before = JenisBuktiKepemilikan::find($oldValue)?->name;
                        $after = JenisBuktiKepemilikan::find($newValue)?->name;

                        $props['old']['jenis_bukti_kepemilikan_id'] = $before;
                        $props['attributes']['jenis_bukti_kepemilikan_id'] = $after;

                        $item->properties = $props;
                        return $item; // ✅ Return only if field changed
                    }
                }
            }
            // If jenis_bukti_kepemilikan_id didn't change, skip this item
            return null;
        }

        // Handle DokumenLegal and DokumenPendukung - ONLY show if nama_dokumen changed
        else if ($item->subject_type === 'Modules\\Adk\\Models\\DokumenLegal' ||
                 $item->subject_type === 'Modules\\Adk\\Models\\DokumenPendukung') {

            $props = is_array($item->properties)
                ? $item->properties
                : json_decode($item->properties, true);

            if (!is_array($props)) {
                return null; // Skip invalid data
            }

            // Only return if nama_dokumen changed
            if (isset($props['old']['nama_dokumen'], $props['attributes']['nama_dokumen'])
                && $props['old']['nama_dokumen'] !== $props['attributes']['nama_dokumen']) {

                $item->properties = $props;
                return $item; // ✅ Return only if nama_dokumen changed (regardless of description)
            }

            // If nama_dokumen didn't change, skip this item
            return null;
        }

        // For other document types that adminkredit can see
        else {
            // Skip 'updated' description for other types
            if ($item->description === 'updated') {
                return null;
            }
            return $item;
        }
    }

    // For NON-adminkredit users (Auditor, etc.) - NO FILTERING, return everything
    return $item;
});

// Remove null values and re-index (only affects adminkredit filtered items)
$data = $data->filter()->values();

// IMPORTANT: Recalculate filtered records count after filtering
$filteredRecords = $data->count();

// Calculate the page count based on NEW filtered count
$pageCount = ceil($filteredRecords / ($request->get('size') ?: 1));

// Calculate the current page number
$currentPage = $request->get('page') ?: 1;

Log::info("Data :", $data->pluck('id')->toArray());
            // Return the response data as a JSON object
            return response()->json([
                'draw'            => $request->get('draw'),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'pageCount'       => $pageCount,
                'page'            => $currentPage,
                'totalCount'      => $filteredRecords,
                'data'            => $data,
            ]);
        }
        public function datatable(Request $request)
        {
            // Check if the authenticated user has the required permission to view audit logs
            if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
                abort(403, 'Sorry! You are not allowed to view audit logs.');
            }

            // Retrieve data from the database
            $query = Activity::query();

            // Apply search filter if provided
            if ($request->has('search') && !empty($request->get('search'))) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('log_name', 'LIKE', "%$search%")
                      ->orWhere('description', 'LIKE', "%$search%")
                      ->orWhere('subject_id', 'LIKE', "%$search%")
                      ->orWhere('subject_type', 'LIKE', "%$search%")
                      ->orWhere('causer_id', 'LIKE', "%$search%")
                      ->orWhere('properties', 'LIKE', "%$search%");
                });
            }

            // Apply sorting if provided
            if ($request->has('sortOrder') && !empty($request->get('sortOrder'))) {
                $order  = $request->get('sortOrder');
                $column = $request->get('sortField');
                $query->orderBy($column, $order);
            } else {
                // Default sorting by created_at descending
                $query->orderBy('created_at', 'desc');
            }

            // Get the total count of records before pagination
            $totalRecords = Activity::count();

            // Get the filtered count before pagination
            $filteredRecords = $query->count();

            // Apply pagination if provided
            if ($request->has('page') && $request->has('size')) {
                $page   = $request->get('page');
                $size   = $request->get('size');
                $offset = ($page - 1) * $size; // Calculate the offset

                $query->skip($offset)->take($size);
            }

            // Get the data for the current page
            $data = $query->get();

            // Map causer_id to creator name
            $data = $data->map(function ($item) {
                // Create a new property for the creator's name
                if ($item->causer_id && $item->causer_type === 'Modules\\Usermanagement\\Models\\User') {
                    // Try to find the user
                    $user = User::find($item->causer_id);
                    if ($user) {
                        $item->creator_name = $user->name;
                    } else {
                        $item->creator_name = 'Unknown User';
                    }
                } else {
                    $item->creator_name = 'System';
                }

                return $item;
            });

            // Calculate the page count
            $pageCount = ceil($filteredRecords / ($request->get('size') ?: 1));

            // Calculate the current page number
            $currentPage = $request->get('page') ?: 1;

            // Return the response data as a JSON object
            return response()->json([
                'draw'            => $request->get('draw'),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'pageCount'       => $pageCount,
                'page'            => $currentPage,
                'totalCount'      => $filteredRecords,
                'data'            => $data,
            ]);
        }
    }
