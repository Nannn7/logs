<?php

    namespace Modules\Logs\Http\Controllers;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use Modules\Usermanagement\Models\User;
    use Spatie\Activitylog\Models\Activity;
    use Illuminate\Support\Facades\Auth;

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
