<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPanelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request, AdminPanelService $adminPanel): View
    {
        $filters = $request->only(['q', 'from', 'to']);

        return view('admin.reports.index', [
            'reports' => $adminPanel->reports($filters),
            'filters' => $filters,
            'firestoreAvailable' => $adminPanel->firestoreAvailable(),
        ]);
    }
}
