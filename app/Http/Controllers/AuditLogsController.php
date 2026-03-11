<?php

namespace Modules\Logs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Usermanagement\Models\User;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Modules\Adk\Models\JenisBuktiKepemilikan;

class AuditLogsController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            return $next($request);
        });
    }

    public function index()
    {
        // if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
        //     abort(403, 'Sorry! You are not allowed to view audit logs.');
        // }

        return view('logs::audit');
    }

    public function indexAdminKredit()
    {
        // if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
        //     abort(403, 'Sorry! You are not allowed to view audit logs.');
        // }

        return view('logs::adminkredit');
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

    public function datatable(Request $request)
    {
        // if (is_null($this->user) || !$this->user->can('audit-logs.read')) {
        //     abort(403, 'Sorry! You are not allowed to view audit logs.');
        // }

        $query = Activity::query();

        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('log_name',     'LIKE', "%$search%")
                    ->orWhere('description', 'LIKE', "%$search%")
                    ->orWhere('subject_id',  'LIKE', "%$search%")
                    ->orWhere('subject_type', 'LIKE', "%$search%")
                    ->orWhere('causer_id',   'LIKE', "%$search%")
                    ->orWhere('properties',  'LIKE', "%$search%");
            });
        }

        if ($request->has('sortOrder') && !empty($request->get('sortOrder'))) {
            $order  = $request->get('sortOrder');
            $column = $request->get('sortField');
            $query->orderBy($column, $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $totalRecords    = Activity::count();
        $filteredRecords = $query->count();

        if ($request->has('page') && $request->has('size')) {
            $page   = $request->get('page');
            $size   = $request->get('size');
            $offset = ($page - 1) * $size;

            $query->skip($offset)->take($size);
        }

        $data = $query->get();

        $data = $data->map(function ($item) {
            if ($item->causer_id && $item->causer_type === 'Modules\\Usermanagement\\Models\\User') {
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

        $pageCount   = ceil($filteredRecords / ($request->get('size') ?: 1));
        $currentPage = $request->get('page') ?: 1;

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
