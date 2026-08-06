<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationDetailController extends Controller
{
    public function show(Request $request, Application $application): Response
    {
        abort_if($application->user_id !== $request->user()->id, 403);

        return Inertia::render('applications/show', [
            'application' => [
                'id' => $application->id,
                'position_title' => $application->position_title,
                'status' => $application->status,
                'company_name' => $application->company?->name ?? 'Unknown',
                'recruiter_name' => $application->recruiter?->name ?? null,
                'application_date' => $application->application_date?->toDateString(),
                'source' => $application->source,
                'notes' => $application->notes,
                'summary' => $application->summary,
                'attachments' => $application->attachments ?? [],
            ],
        ]);
    }
}
