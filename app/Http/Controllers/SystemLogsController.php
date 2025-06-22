<?php

namespace Modules\Logs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jackiedo\LogReader\Exceptions\UnableToRetrieveLogFilesException;
use Jackiedo\LogReader\LogReader;

class SystemLogsController extends Controller
{
    protected $reader;
    protected $user;

    public function __construct(LogReader $reader)
    {
        $this->reader = $reader;
        $this->user = Auth::guard('web')->user();
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

    public function datatable(Request $request){
        // Check if the authenticated user has the required permission to view system logs
        if (is_null($this->user) || !$this->user->can('system-logs.read')) {
            abort(403, 'Sorry! You are not allowed to view system logs.');
        }

        $data = collect();
        $this->reader->setLogPath(storage_path('logs'));
        try {
            $data = $this->reader->get()->merge($data);
        } catch (UnableToRetrieveLogFilesException $exception) {
            echo $exception->getMessage();
            exit;
        }

        $data = $data->map(function ($a) {
            return (collect($a))->only(['id', 'date', 'environment', 'level', 'file_path', 'context']);
        });


        // Get pagination parameters from request
        $perPage = (int) $request->input('size', 10);
        $currentPage = (int) $request->input('page', 1);
        $search = $request->input('search', '');

        // Apply search if provided
        if (!empty($search)) {
            $data = $data->filter(function ($item) use ($search) {
                // Search in relevant fields
                return stripos($item['level'], $search) !== false ||
                    stripos($item['environment'], $search) !== false ||
                    stripos($item['id'], $search) !== false ||
                    stripos($item['context'], $search) !== false;
            });
        }

        // Get total count before pagination
        $totalRecords = $data->count();
        $filteredRecords = $totalRecords; // Same as total if no filtering applied

        // Apply pagination
        $offset = ($currentPage - 1) * $perPage;
        $data = $data->slice($offset, $perPage)->values();

        // Calculate page count
        $pageCount = ceil($filteredRecords / $perPage);

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'pageCount'       => $pageCount,
            'page'            => $currentPage,
            'totalCount'      => $filteredRecords,
            'data'            => $data,
        ]);
    }
}
