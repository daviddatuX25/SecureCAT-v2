<?php

namespace Tests\Feature\Admin;

use App\Models\ExamDomain;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamDomainControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\ExamDomainSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());

        return $user;
    }

    private function testAdministrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        return $user;
    }

    public function test_registrar_admin_cannot_view_exam_domains_index(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.exam-domains.index'));

        $response->assertForbidden();
    }

    public function test_registrar_admin_cannot_create_exam_domain(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.exam-domains.store'), [
            'name' => 'New Pillar',
            'code' => 'NP',
            'max_items' => 30,
            'display_order' => 10,
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_test_administrator_can_view_exam_domains_index(): void
    {
        $response = $this->actingAs($this->testAdministrator())->get(route('admin.exam-domains.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ExamDomains/Index')
            ->has('exam_domains')
        );
    }

    public function test_test_administrator_can_create_exam_domain(): void
    {
        $response = $this->actingAs($this->testAdministrator())->post(route('admin.exam-domains.store'), [
            'name' => 'New Pillar',
            'code' => 'NP',
            'max_items' => 30,
            'display_order' => 10,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.exam-domains.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('exam_domains', ['code' => 'NP', 'name' => 'New Pillar', 'max_items' => 30]);
    }

    public function test_test_administrator_can_update_exam_domain(): void
    {
        $domain = ExamDomain::first();
        $response = $this->actingAs($this->testAdministrator())->put(route('admin.exam-domains.update', $domain), [
            'name' => 'Updated Name',
            'code' => $domain->code,
            'max_items' => 20,
            'display_order' => 1,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('admin.exam-domains.index'));
        $response->assertSessionHas('success');
        $domain->refresh();
        $this->assertSame('Updated Name', $domain->name);
        $this->assertSame(20, $domain->max_items);
        $this->assertFalse($domain->is_active);
    }
}
