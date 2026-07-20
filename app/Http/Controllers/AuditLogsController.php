<?php

namespace Modules\Logs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Usermanagement\Models\User;
use Spatie\Activitylog\Models\Activity;

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
        if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! You are not allowed to view audit logs.',
            ], 403);
        }

        $query = Activity::query()
            ->select([
                'id',
                'log_name',
                'description',
                'subject_id',
                'subject_type',
                'causer_id',
                'causer_type',
                'properties',
                'created_at',
            ])
            ->with('causer');

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('log_name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('subject_type', 'ilike', "%{$search}%")
                    ->orWhereRaw('subject_id::text ilike ?', ["%{$search}%"])
                    ->orWhereRaw('causer_id::text ilike ?', ["%{$search}%"])
                    ->orWhereRaw('properties::text ilike ?', ["%{$search}%"]);
            });
        }

        $sortField = (string) $request->get('sortField', 'created_at');
        $sortOrder = strtolower((string) $request->get('sortOrder', 'desc'));
        $allowedSort = [
            'log_name',
            'subject_type',
            'description',
            'properties',
            'causer_type',
            'causer_id',
            'created_at',
        ];

        if (!in_array($sortField, $allowedSort, true)) {
            $sortField = 'created_at';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        if ($sortField === 'properties') {
            $query->orderByRaw("properties::text {$sortOrder}");
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $totalRecords = Activity::count();
        $filteredRecords = (clone $query)->count();

        $page = max((int) $request->get('page', 1), 1);
        $size = max((int) $request->get('size', 10), 1);
        $offset = ($page - 1) * $size;

        $data = $query
            ->skip($offset)
            ->take($size)
            ->get()
            ->map(function ($item) {
                if ($item->causer instanceof User) {
                    $item->creator_name = $item->causer->name;
                } elseif ($item->causer_id && $item->causer_type === User::class) {
                    $item->creator_name = 'Unknown User';
                } else {
                    $item->creator_name = 'System';
                }

                return $item;
            });

        $pageCount = (int) ceil($filteredRecords / $size);

        return response()->json([
            'draw'            => $request->get('draw'),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'pageCount'       => $pageCount,
            'page'            => $page,
            'totalCount'      => $filteredRecords,
            'data'            => $data,
        ]);
    }
}
