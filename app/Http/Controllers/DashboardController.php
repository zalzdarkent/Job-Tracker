<?php

namespace App\Http\Controllers;

use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly ApplicationService $applicationService) {}

    public function __invoke(Request $request): Response
    {
        $dashboardData = $this->applicationService->getDashboardData($request->user());

        return Inertia::render('dashboard', $dashboardData);
    }
}
