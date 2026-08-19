<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\StoreContext;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::where('store_id', app(StoreContext::class)->id())->with('user')->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%' . $request->action . '%'))->latest()->paginate(30)->withQueryString();
        return view('audit.index', compact('logs'));
    }
}
