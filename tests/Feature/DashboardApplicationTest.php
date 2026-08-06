<?php

use App\Models\Company;
use App\Models\Recruiter;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('returns dashboard statistics and recent applications for the authenticated user', function (): void {
    $user = User::factory()->create();

    $company = Company::create([
        'user_id' => $user->id,
        'name' => 'Acme Corp',
    ]);

    $recruiter = Recruiter::create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $user->applications()->createMany([
        [
            'company_id' => $company->id,
            'recruiter_id' => $recruiter->id,
            'position_title' => 'Senior Engineer',
            'status' => 'applied',
            'application_date' => '2026-08-01',
            'source' => 'LinkedIn',
            'notes' => 'Initial application',
        ],
        [
            'company_id' => $company->id,
            'recruiter_id' => $recruiter->id,
            'position_title' => 'Product Designer',
            'status' => 'interview',
            'application_date' => '2026-08-02',
            'source' => 'Referral',
            'notes' => 'Interview scheduled',
        ],
        [
            'company_id' => $company->id,
            'recruiter_id' => $recruiter->id,
            'position_title' => 'Backend Developer',
            'status' => 'offer',
            'application_date' => '2026-08-03',
            'source' => 'Company site',
            'notes' => 'Offer received',
        ],
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(function (AssertableInertia $page): void {
        $page->component('dashboard')
            ->where('stats.totalApplications', 3)
            ->where('stats.interviews', 1)
            ->where('stats.offers', 1)
            ->where('stats.rejections', 0)
            ->where('stats.companiesCount', 1)
            ->where('stats.responseRate', 33.3)
            ->has('recentApplications', 3);
    });
});

it('creates a new application for the authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/applications', [
        'company_name' => 'Northwind',
        'position_title' => 'Staff Engineer',
        'status' => 'applied',
        'application_date' => '2026-08-06',
        'source' => 'Email',
        'notes' => 'Follow up next week',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('applications', [
        'user_id' => $user->id,
        'position_title' => 'Staff Engineer',
        'status' => 'applied',
        'source' => 'Email',
    ]);
    $this->assertDatabaseHas('companies', [
        'user_id' => $user->id,
        'name' => 'Northwind',
    ]);
});
