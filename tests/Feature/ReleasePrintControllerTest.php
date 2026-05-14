<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\AptitudeArea;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\ResultSheetTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleasePrintControllerTest extends TestCase
{
    use RefreshDatabase;

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
}
