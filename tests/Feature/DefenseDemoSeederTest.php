<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Room;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DefenseDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DefenseDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DefenseDemoSeeder::class);
    }

    public function test_seeds_five_staff_users(): void
    {
        $this->assertDatabaseHas('users', ['email' => 'admin@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'josefina@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'maria@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'eduardo@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'analiza@securecat.local']);
    }

    public function test_seeds_four_rooms(): void
    {
        $this->assertGreaterThanOrEqual(4, Room::count());
        $this->assertDatabaseHas('rooms', ['building' => 'Main Building', 'name' => 'Room 101']);
        $this->assertDatabaseHas('rooms', ['building' => 'Main Building', 'name' => 'Room 102']);
    }

    public function test_seeds_twenty_applications(): void
    {
        $this->assertSame(20, Application::count());
    }

    public function test_seeds_twelve_applicant_portal_accounts(): void
    {
        $this->assertSame(12, Applicant::count());
    }

    public function test_seeds_four_exam_sessions(): void
    {
        $this->assertSame(4, ExamSession::count());
    }

    public function test_session_a_is_finalized(): void
    {
        $this->assertSame(1, GradingSession::where('status', GradingSession::STATUS_FINALIZED)->count());
    }

    public function test_session_b_grading_is_in_progress(): void
    {
        $this->assertSame(1, GradingSession::where('status', GradingSession::STATUS_IN_PROGRESS)->count());
    }

    public function test_session_a_consultation_summaries_are_released(): void
    {
        $this->assertSame(3, ConsultationSummary::where('status', ConsultationSummary::STATUS_RELEASED)->count());
    }

    public function test_session_b_consultation_summaries_are_pending(): void
    {
        $this->assertSame(2, ConsultationSummary::where('status', ConsultationSummary::STATUS_PENDING)->count());
    }

    public function test_session_b_consultations_have_no_course_or_comments_pre_filled(): void
    {
        $pending = ConsultationSummary::where('status', ConsultationSummary::STATUS_PENDING)->get();

        foreach ($pending as $summary) {
            $this->assertNull(
                $summary->recommended_course_id,
                'Session B consultations must have null recommended_course_id so the demo can fill it live.'
            );
            $this->assertNull(
                $summary->counselor_comments,
                'Session B consultations must have null counselor_comments so the demo can fill it live.'
            );
        }
    }

    public function test_session_c_has_one_present_and_two_pending_attendance(): void
    {
        $sessionC = ExamSession::whereDate('date', today())->first();
        $this->assertNotNull($sessionC);

        $present = DB::table('exam_session_applicant')
            ->where('exam_session_id', $sessionC->id)
            ->where('attendance_status', 'present')
            ->count();

        $pending = DB::table('exam_session_applicant')
            ->where('exam_session_id', $sessionC->id)
            ->where('attendance_status', 'pending')
            ->count();

        $this->assertSame(1, $present);
        $this->assertSame(3, $pending);
    }
}
