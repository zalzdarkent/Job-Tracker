<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationListController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $request->user()->applications()->with(['company', 'recruiter'])->latest('application_date');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('company')) {
            $query->whereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', '%'.$request->input('company').'%'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($builder) => $builder->where('position_title', 'like', '%'.$search.'%')
                ->orWhere('notes', 'like', '%'.$search.'%'));
        }

        $paginated = $query->paginate(15)->withQueryString();

        $paginated->getCollection()->transform(fn (Application $application) => [
            'id' => $application->id,
            'position_title' => $application->position_title,
            'status' => $application->status,
            'company_name' => $application->company?->name ?? 'Unknown',
            'recruiter_name' => $application->recruiter?->name ?? null,
            'application_date' => $application->application_date?->toDateString(),
            'source' => $application->source,
        ]);

        return Inertia::render('applications/index', [
            'applications' => $paginated,
            'filters' => $request->only(['status', 'company', 'search']),
        ]);
    }
}
