<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Company;
use App\Models\Recruiter;
use App\Models\User;

class ApplicationService
{
    public function getDashboardData(User $user): array
    {
        $applications = $user->applications()
            ->with(['company', 'recruiter'])
            ->latest('application_date')
            ->latest('created_at')
            ->get();

        $stats = [
            'totalApplications' => $applications->count(),
            'activeApplications' => $applications->filter(fn (Application $application) => ! in_array($application->status, ['offer', 'rejection', 'rejected', 'withdrawn'], true))->count(),
            'interviews' => $applications->where('status', 'interview')->count(),
            'assessments' => $applications->where('status', 'assessment')->count(),
            'offers' => $applications->where('status', 'offer')->count(),
            'rejections' => $applications->whereIn('status', ['rejection', 'rejected'])->count(),
            'companiesCount' => $applications->pluck('company_id')->filter()->unique()->count(),
            'responseRate' => $applications->count() > 0 ? round($applications->whereIn('status', ['interview', 'assessment'])->count() / $applications->count() * 100, 1) : 0.0,
        ];

        $monthlyTrend = $applications->groupBy(fn (Application $application) => $application->application_date?->format('Y-m') ?? 'Unknown')
            ->map(fn ($items, $month) => ['month' => $month, 'count' => $items->count()])
            ->sortKeys()
            ->values()
            ->all();

        $statusBreakdown = [
            ['status' => 'Applied', 'count' => $applications->where('status', 'applied')->count(), 'color' => '#6366f1'],
            ['status' => 'Interview', 'count' => $applications->where('status', 'interview')->count(), 'color' => '#f59e0b'],
            ['status' => 'Assessment', 'count' => $applications->where('status', 'assessment')->count(), 'color' => '#3b82f6'],
            ['status' => 'Offer', 'count' => $applications->where('status', 'offer')->count(), 'color' => '#10b981'],
            ['status' => 'Rejection', 'count' => $applications->whereIn('status', ['rejection', 'rejected'])->count(), 'color' => '#f43f5e'],
        ];

        return [
            'stats' => $stats,
            'recentApplications' => $applications->take(6)->map(fn (Application $application) => [
                'id' => $application->id,
                'position_title' => $application->position_title,
                'status' => $application->status,
                'company_name' => $application->company?->name ?? 'Unknown',
                'recruiter_name' => $application->recruiter?->name ?? null,
                'application_date' => $application->application_date?->toDateString(),
                'source' => $application->source,
            ])->values()->all(),
            'monthlyApplicationTrend' => $monthlyTrend,
            'statusBreakdown' => $statusBreakdown,
        ];
    }

    public function createApplication(User $user, array $data): Application
    {
        $company = $this->resolveCompany($user, $data);
        $recruiter = $this->resolveRecruiter($user, $company, $data);

        return $user->applications()->create([
            'company_id' => $company?->id,
            'recruiter_id' => $recruiter?->id,
            'position_title' => $data['position_title'],
            'status' => $data['status'] ?? 'applied',
            'application_date' => $data['application_date'] ?? now()->toDateString(),
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
            'summary' => $data['summary'] ?? null,
        ]);
    }

    private function resolveCompany(User $user, array $data): ?Company
    {
        if (empty($data['company_name'])) {
            return null;
        }

        $name = trim($data['company_name']);

        return Company::firstOrCreate(
            ['user_id' => $user->id, 'name' => $name],
            ['name' => $name],
        );
    }

    private function resolveRecruiter(User $user, ?Company $company, array $data): ?Recruiter
    {
        if (empty($data['recruiter_name']) && empty($data['recruiter_email'])) {
            return null;
        }

        $attributes = [
            'user_id' => $user->id,
            'company_id' => $company?->id,
            'name' => trim($data['recruiter_name'] ?? ''),
            'email' => trim($data['recruiter_email'] ?? ''),
        ];

        return Recruiter::firstOrCreate(
            ['user_id' => $user->id, 'name' => $attributes['name'], 'email' => $attributes['email']],
            $attributes,
        );
    }
}
