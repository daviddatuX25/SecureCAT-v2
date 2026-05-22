<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\AptitudeArea;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\ResultSheetTemplate;
use App\Models\User;
use App\Services\ResultSheetPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class ReleasePrintControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createSessionWithApplicant(): array
    {
        $examSession = ExamSession::factory()->create();
        $session = GradingSession::factory()->create([
            'exam_session_id' => $examSession->id,
            'status' => GradingSession::STATUS_FINALIZED,
        ]);
        $applicant = Applicant::factory()->create();
        $session->applicants()->attach($applicant->id, ['result_printed_at' => null]);

        return [$session, $applicant, $examSession];
    }

    public function test_index_displays_print_batch_page(): void
    {
        [$session, $applicant] = $this->createSessionWithApplicant();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.index', $session));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('applicants', 1)
            ->where('applicants.0.printed', false)
        );
    }

    public function test_mark_printed_sets_result_printed_at(): void
    {
        [$session, $applicant] = $this->createSessionWithApplicant();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->post(route('admin.release.print.mark-printed', $session), [
                'applicant_ids' => [$applicant->id],
                'printed' => true,
            ]);

        $response->assertRedirect();
        $this->assertNotNull(
            $session->applicants()->where('applicants.id', $applicant->id)->first()->pivot->result_printed_at
        );
    }

    public function test_unmark_printed_clears_result_printed_at(): void
    {
        [$session, $applicant] = $this->createSessionWithApplicant();
        $session->applicants()->updateExistingPivot($applicant->id, ['result_printed_at' => now()]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->post(route('admin.release.print.mark-printed', $session), [
                'applicant_ids' => [$applicant->id],
                'printed' => false,
            ]);

        $response->assertRedirect();
        $this->assertNull(
            $session->applicants()->where('applicants.id', $applicant->id)->first()->pivot->result_printed_at
        );
    }

    public function test_result_sheet_displays_single_applicant(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        [$session, $applicant] = $this->createSessionWithApplicant();
        AptitudeArea::factory()->create(['is_active' => true]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.result-sheet', [$session, $applicant]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('applicant')
            ->where('printed', false)
        );
    }

    public function test_print_bulk_displays_bulk_view(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        [$session, $applicant] = $this->createSessionWithApplicant();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.print-bulk', ['grading_session' => $session->id, 'ids' => $applicant->id]));

        $response->assertOk();
    }

    public function test_result_sheet_returns_404_for_applicant_not_in_session(): void
    {
        [$session] = $this->createSessionWithApplicant();
        $otherApplicant = Applicant::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.result-sheet', [$session, $otherApplicant]));

        $response->assertNotFound();
    }

    public function test_result_sheet_shows_error_when_no_active_template(): void
    {
        [$session, $applicant] = $this->createSessionWithApplicant();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.result-sheet', [$session, $applicant]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('templateError', 'No active result sheet template. Please create one in Admin > Result templates.')
        );
    }

    public function test_mark_printed_validation_fails_with_empty_applicant_ids(): void
    {
        [$session] = $this->createSessionWithApplicant();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->post(route('admin.release.print.mark-printed', $session), [
                'applicant_ids' => [],
                'printed' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['applicant_ids']);
    }

    public function test_mark_printed_validation_fails_with_invalid_applicant_id(): void
    {
        [$session] = $this->createSessionWithApplicant();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->post(route('admin.release.print.mark-printed', $session), [
                'applicant_ids' => [999999],
                'printed' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['applicant_ids']);
    }

    public function test_non_authorized_user_cannot_access(): void
    {
        [$session] = $this->createSessionWithApplicant();

        $response = $this->get(route('admin.release.print.index', $session));
        $response->assertRedirect(route('login'));
    }

    public function test_full_print_workflow_from_release(): void
    {
        $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_FINALIZED]);
        $applicant = Applicant::factory()->create();
        $session->applicants()->attach($applicant->id, ['result_printed_at' => null]);
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        // 1. View print batch
        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.index', $session));
        $response->assertOk();

        // 2. Mark as printed
        $response = $this->actingAs($admin)
            ->post(route('admin.release.print.mark-printed', $session), [
                'applicant_ids' => [$applicant->id],
                'printed' => true,
            ]);
        $response->assertRedirect();
        $this->assertNotNull(
            $session->fresh()->applicants()->where('applicants.id', $applicant->id)->first()->pivot->result_printed_at
        );

        // 3. View result sheet
        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.result-sheet', [$session, $applicant]));
        $response->assertOk();

        // 4. Verify printed flag shows in session page
        $response = $this->actingAs($admin)
            ->get(route('admin.grading.sessions.show', $session));
        $response->assertInertia(fn ($page) => $page
            ->where('applicants.0.printed', true)
        );
    }

    public function test_result_sheet_pdf_returns_inline_pdf_for_valid_applicant(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        [$session, $applicant] = $this->createSessionWithApplicant();
        ApplicantScore::factory()->create([
            'grading_session_id' => $session->id,
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => AptitudeArea::factory(),
        ]);

        $mock = Mockery::mock(ResultSheetPdfService::class);
        $mock->shouldReceive('inline')->once()->andReturn(
            new Response('pdf-content', 200, ['Content-Type' => 'application/pdf'])
        );
        $this->app->instance(ResultSheetPdfService::class, $mock);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.result-sheet-pdf', [$session, $applicant]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_result_sheet_pdf_returns_404_for_applicant_not_in_session(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        [$session] = $this->createSessionWithApplicant();
        $otherApplicant = Applicant::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.result-sheet-pdf', [$session, $otherApplicant]));

        $response->assertNotFound();
    }

    public function test_print_bulk_pdf_returns_inline_pdf_by_default(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        [$session, $applicant] = $this->createSessionWithApplicant();

        $mock = Mockery::mock(ResultSheetPdfService::class);
        $mock->shouldReceive('bulkInline')->once()->andReturn(
            new Response('pdf-content', 200, ['Content-Type' => 'application/pdf'])
        );
        $this->app->instance(ResultSheetPdfService::class, $mock);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.print-bulk-pdf', ['grading_session' => $session->id, 'ids' => $applicant->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_print_bulk_agnostic_pdf_returns_inline_pdf_by_default(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        $session = GradingSession::factory()->create();
        $applicant = Applicant::factory()->create();
        $session->applicants()->attach($applicant->id, ['result_printed_at' => null]);
        ApplicantScore::factory()->create([
            'grading_session_id' => $session->id,
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => AptitudeArea::factory(),
        ]);

        $mock = Mockery::mock(ResultSheetPdfService::class);
        $mock->shouldReceive('bulkInline')->once()->andReturn(
            new Response('pdf-content', 200, ['Content-Type' => 'application/pdf'])
        );
        $this->app->instance(ResultSheetPdfService::class, $mock);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.release.print.bulk-agnostic-pdf', ['ids' => $applicant->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
