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

    public function datatableAdminKredit(Request $request)
    {
        $searchPayload = $request->get('search');
        $filters       = [];

        if ($searchPayload) {
            $decoded = json_decode($searchPayload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $filters = $decoded;
            } else {
                $filters['search'] = $searchPayload;
            }
        }

        $keyword     = trim($filters['search']       ?? '');
        $tipeLog     = trim($filters['tipe_log']     ?? '');
        $tipeSubject = trim($filters['tipe_subject'] ?? '');
        $tipeDokumen = trim($filters['tipe_dokumen'] ?? '');
        $userRole    = trim($filters['user_role']    ?? '');
        $username    = trim($filters['username']     ?? '');

        $userIdsFromKeyword  = collect();
        $userIdsFromUsername = collect();

        if (!empty($keyword)) {
            try {
                $userIdsFromKeyword = User::where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('username', 'LIKE', "%{$keyword}%")
                    ->pluck('id');
            } catch (\Exception $e) {
                $userIdsFromKeyword = User::where('name', 'LIKE', "%{$keyword}%")
                    ->pluck('id');
            }
        }

        if (!empty($username)) {
            try {
                $userIdsFromUsername = User::where('name', 'LIKE', "%{$username}%")
                    ->orWhere('username', 'LIKE', "%{$username}%")
                    ->pluck('id');
            } catch (\Exception $e) {
                $userIdsFromUsername = User::where('name', 'LIKE', "%{$username}%")
                    ->pluck('id');
            }
        }

        $query = Activity::query()
            ->where('log_name', 'ADK')
            ->where('subject_type', 'LIKE', '%Dokumen%');

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword, $userIdsFromKeyword) {
                $q->where('log_name',      'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%")
                    ->orWhere('subject_type', 'LIKE', "%{$keyword}%")
                    ->orWhere('properties',  'LIKE', "%{$keyword}%");

                if ($userIdsFromKeyword->isNotEmpty()) {
                    $q->orWhereIn('causer_id', $userIdsFromKeyword);
                }
            });
        }

        if (!empty($tipeLog)) {
            $query->where('log_name', $tipeLog);
        }

        if (!empty($tipeSubject)) {
            $query->where('subject_type', 'LIKE', "%{$tipeSubject}");
        }

        if (!empty($tipeDokumen)) {
            $query->where('description', 'LIKE', "%{$tipeDokumen}%");
        }

        if (!empty($userRole)) {
            if ($userRole === 'System') {
                $query->whereNull('causer_id');
            } else {
                $query->whereNotNull('causer_id');
            }
        }

        if (!empty($username)) {
            if ($userIdsFromUsername->isNotEmpty()) {
                $query->whereIn('causer_id', $userIdsFromUsername);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->has('sortOrder') && !empty($request->get('sortOrder'))) {
            $query->orderBy($request->get('sortField'), $request->get('sortOrder'));
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $totalRecords = Activity::count();

        $allFiltered = $query->get();

        $hasExplicitFilter = !empty($tipeLog) || !empty($tipeSubject)
            || !empty($tipeDokumen) || !empty($userRole)
            || !empty($username) || !empty($keyword);

        $data = $allFiltered->map(function ($item) use ($hasExplicitFilter) {
            if ($item->causer_id && $item->causer_type === 'Modules\\Usermanagement\\Models\\User') {
                $user = User::find($item->causer_id);
                $item->creator_name = $user ? $user->name : 'Unknown User';
            } else {
                $item->creator_name = 'System';
            }

            if ($hasExplicitFilter) {
                if ($item->subject_type === 'Modules\\Adk\\Models\\DokumenJaminan' && $item->properties) {
                    $props = json_decode($item->properties, true);
                    if (
                        isset(
                            $props['old']['jenis_bukti_kepemilikan_id'],
                            $props['attributes']['jenis_bukti_kepemilikan_id']
                        )
                    ) {
                        $old = $props['old']['jenis_bukti_kepemilikan_id'];
                        $new = $props['attributes']['jenis_bukti_kepemilikan_id'];
                        if ($old !== $new) {
                            $props['old']['jenis_bukti_kepemilikan_id']        = JenisBuktiKepemilikan::find($old)?->name;
                            $props['attributes']['jenis_bukti_kepemilikan_id'] = JenisBuktiKepemilikan::find($new)?->name;
                            $item->properties = $props;
                        }
                    }
                }
                return $item;
            }

            if (auth()->user()->hasRole('adminkredit')) {
                if ($item->subject_type === 'Modules\\Adk\\Models\\DokumenJaminan') {
                    if ($item->properties) {
                        $props = json_decode($item->properties, true);

                        if (isset(
                            $props['old']['jenis_bukti_kepemilikan_id'],
                            $props['attributes']['jenis_bukti_kepemilikan_id']
                        )) {
                            $oldValue = $props['old']['jenis_bukti_kepemilikan_id'];
                            $newValue = $props['attributes']['jenis_bukti_kepemilikan_id'];

                            if ($oldValue !== $newValue) {
                                $before = JenisBuktiKepemilikan::find($oldValue)?->name;
                                $after  = JenisBuktiKepemilikan::find($newValue)?->name;

                                $props['old']['jenis_bukti_kepemilikan_id']        = $before;
                                $props['attributes']['jenis_bukti_kepemilikan_id'] = $after;

                                $item->properties = $props;

                                return $item;
                            }
                        }
                    }

                    return null;
                } elseif (
                    $item->subject_type === 'Modules\\Adk\\Models\\DokumenLegal' ||
                    $item->subject_type === 'Modules\\Adk\\Models\\DokumenPendukung'
                ) {
                    $props = is_array($item->properties)
                        ? $item->properties
                        : json_decode($item->properties, true);

                    if (!is_array($props)) {
                        return null;
                    }

                    if (
                        isset($props['old']['nama_dokumen'], $props['attributes']['nama_dokumen'])
                        && $props['old']['nama_dokumen'] !== $props['attributes']['nama_dokumen']
                    ) {
                        $item->properties = $props;
                        return $item;
                    }

                    return null;
                } else {
                    if ($item->description === 'updated') {
                        return null;
                    }

                    return $item;
                }
            }

            return $item;
        });

        $data = $data->filter()->values();

        $filteredRecords = $data->count();

        $page        = (int) ($request->get('page') ?: 1);
        $size        = (int) ($request->get('size') ?: 10);
        $pageCount   = (int) ceil($filteredRecords / $size);
        $currentPage = $page;
        $offset      = ($page - 1) * $size;

        $data = $data->slice($offset, $size)->values();

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
