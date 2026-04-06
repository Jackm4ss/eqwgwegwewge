<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminQrManagementLookupRequest;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\AdminQrManagementLookupService;
use App\Support\CountryCatalog;
use Illuminate\Contracts\View\View;

class QrManagementController extends Controller
{
    public function __construct(
        private readonly AdminQrManagementLookupService $lookupService,
        private readonly AdminPanelService $adminPanel,
    ) {
    }

    public function index(AdminQrManagementLookupRequest $request): View
    {
        $lookupAttempted = $request->hasLookupAttempt();
        $lookupResult = $lookupAttempted
            ? $this->lookupService->lookup($request->validated())
            : null;

        return view('admin.qr-management.index', [
            'lookupAttempted' => $lookupAttempted,
            'lookupResult' => $lookupResult,
            'searchType' => (string) ($request->input('search_type') ?? 'email') ?: 'email',
            'lookupInput' => [
                'email' => (string) ($request->input('email') ?? ''),
                'phone_country_code' => (string) ($request->input('phone_country_code') ?? '+60'),
                'phone_national_number' => (string) ($request->input('phone_national_number') ?? ''),
                'country' => (string) ($request->input('country') ?? 'MY'),
                'identity_number' => (string) ($request->input('identity_number') ?? ''),
            ],
            'countryOptionGroups' => CountryCatalog::registrationCountryGroups(),
            'phoneOptionGroups' => CountryCatalog::registrationPhoneGroups(),
            'firestoreAvailable' => $this->adminPanel->firestoreAvailable(),
        ]);
    }
}
