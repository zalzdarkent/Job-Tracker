<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $applicationService) {}

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $this->applicationService->createApplication($request->user(), $request->validated());

        return redirect()->route('dashboard');
    }
}
