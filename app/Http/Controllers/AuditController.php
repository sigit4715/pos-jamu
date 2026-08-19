<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%' . $request->action . '%'))->latest()->paginate(30)->withQueryString();
        return view('audit.index', compact('logs'));
    }
}
