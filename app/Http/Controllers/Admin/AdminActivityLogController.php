<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPanelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function __invoke(Request $request, AdminPanelService $adminPanel): View
    {
        $filters = $request->only(['q', 'from', 'to', 'page', 'per_page']);

        return view('admin.logs.index', [
            'logs' => $adminPanel->activityLogs($filters),
            'filters' => $filters,
            'firestoreAvailable' => $adminPanel->firestoreAvailable(),
        ]);
    }
}
