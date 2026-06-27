<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Read-only viewer over audit_logs (append-only trail written by App\Services\AuditLog). */
class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->select(
                'a.id', 'a.action', 'a.model_type', 'a.model_id', 'a.new_values',
                'a.ip_address', 'a.created_at', 'a.role',
                DB::raw("TRIM(CONCAT_WS(' ', u.first_name, u.last_name)) as actor_name"),
            )
            ->orderByDesc('a.created_at');

        if ($action = $request->query('action')) {
            $query->where('a.action', $action);
        }
        if ($date = $request->query('date')) {
            $query->whereDate('a.created_at', $date);
        }

        $logs = $query->paginate(25)->withQueryString();
        $actions = DB::table('audit_logs')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', compact('logs', 'actions'));
    }
}
