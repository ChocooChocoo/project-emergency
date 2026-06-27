<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Read-only registry over archival_logs; restore reuses each module's existing restore action. */
class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('archival_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.archived_by')
            ->select(
                'a.id', 'a.table_name', 'a.record_id', 'a.archive_reason', 'a.archived_at',
                DB::raw("TRIM(CONCAT_WS(' ', u.first_name, u.last_name)) as archived_by_name"),
            )
            ->orderByDesc('a.archived_at');

        if ($table = $request->query('table')) {
            $query->where('a.table_name', $table);
        }

        $logs = $query->paginate(25)->withQueryString();
        $tables = DB::table('archival_logs')->distinct()->orderBy('table_name')->pluck('table_name');

        return view('admin.archive.index', compact('logs', 'tables'));
    }
}
