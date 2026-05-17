<?php

namespace Tests\Feature\ReleasePrint;

use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkPdfJobTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Storage::fake('local');
    }

    public function test_dispatch_bulk_pdf_job_creates_print_job()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job?ids=1,2,3&copies=2');

        $response->assertOk();
        $response->assertJsonStructure(['jobId']);

        $this->assertDatabaseHas('print_jobs', [
            'user_id' => $this->admin->id,
            'copies' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_dispatch_validates_copies_range()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job?ids=1&copies=0');

        $response->assertStatus(422);

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job?ids=1&copies=11');

        $response->assertStatus(422);
    }

    public function test_dispatch_requires_applicant_ids()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job');

        $response->assertStatus(422);
    }

    public function test_print_job_status_returns_progress()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1, 2],
            'copies' => 1,
            'status' => 'processing',
            'progress' => 50,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/admin/release/print/print-job/{$job->id}");

        $response->assertOk();
        $response->assertJson([
            'status' => 'processing',
            'progress' => 50,
            'pdfUrl' => null,
        ]);
    }

    public function test_print_job_status_returns_pdf_url_when_completed()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1],
            'copies' => 1,
            'status' => 'completed',
            'progress' => 100,
            'pdf_path' => 'print-jobs/test.pdf',
        ]);

        Storage::disk('local')->put('print-jobs/test.pdf', 'fake-pdf');

        $response = $this->actingAs($this->admin)
            ->getJson("/admin/release/print/print-job/{$job->id}");

        $response->assertOk();
        $response->assertJson([
            'status' => 'completed',
            'progress' => 100,
        ]);
        $this->assertNotNull($response->json('pdfUrl'));
    }

    public function test_download_returns_404_for_processing_job()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1],
            'copies' => 1,
            'status' => 'processing',
            'progress' => 30,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/release/print/print-job/{$job->id}/download");

        $response->assertStatus(404);
    }

    public function test_download_returns_pdf_for_completed_job()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1],
            'copies' => 1,
            'status' => 'completed',
            'progress' => 100,
            'pdf_path' => 'print-jobs/test.pdf',
        ]);

        Storage::disk('local')->put('print-jobs/test.pdf', 'fake-pdf-content');

        $response = $this->actingAs($this->admin)
            ->get("/admin/release/print/print-job/{$job->id}/download");

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
