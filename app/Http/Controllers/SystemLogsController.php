<?php

namespace Modules\Logs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jackiedo\LogReader\Exceptions\UnableToRetrieveLogFilesException;
use Jackiedo\LogReader\LogReader;

class SystemLogsController extends Controller
{
    protected $reader;
    protected $user;

    public function __construct(LogReader $reader)
    {
        $this->reader = $reader;
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
        // Check if the authenticated user has the required permission to view system logs
        if (is_null($this->user) || !$this->user->can('system-logs.read')) {
            abort(403, 'Sorry! You are not allowed to view system logs.');
        }

        return view('logs::system');
    }

    public function datatable(Request $request)
    {
        // Check if the authenticated user has the required permission to view system logs
        if (is_null($this->user) || !$this->user->can('system-logs.read')) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! You are not allowed to view system logs.',
            ], 403);
        }

        $this->reader->setLogPath(storage_path('logs'));

        try {
            $data = $this->reader->get();
        } catch (UnableToRetrieveLogFilesException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }

        $perPage = (int) $request->input('size', 10);
        $currentPage = (int) $request->input('page', 1);
        $search = trim((string) $request->input('search', ''));
        $sortField = (string) $request->input('sortField', 'date');
        $sortOrder = strtolower((string) $request->input('sortOrder', 'desc'));
        $allowedSortFields = ['id', 'context', 'file_path', 'environment', 'level', 'date'];

        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'date';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $totalRecords = $data->count();

        if ($search !== '') {
            $data = $data->filter(function ($item) use ($search) {
                return stripos($item['level'], $search) !== false ||
                    stripos($item['environment'], $search) !== false ||
                    stripos($item['id'], $search) !== false ||
                    stripos($item['file_path'], $search) !== false ||
                    stripos($item['context'], $search) !== false;
            });
        }

        $filteredRecords = $data->count();

        $data = $sortOrder === 'asc'
            ? $data->sortBy($sortField, SORT_NATURAL | SORT_FLAG_CASE)
            : $data->sortByDesc($sortField, SORT_NATURAL | SORT_FLAG_CASE);

        $offset = ($currentPage - 1) * $perPage;
        $data = $data->slice($offset, $perPage)->values();

        $pageCount = $perPage > 0 ? (int) ceil($filteredRecords / $perPage) : 0;

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'pageCount'       => $pageCount,
            'page'            => $currentPage,
            'totalCount'      => $filteredRecords,
            'data'            => $data->all(),
        ]);
    }
}
