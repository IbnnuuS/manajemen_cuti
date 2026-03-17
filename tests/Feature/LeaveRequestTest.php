<?php

namespace Tests\Feature;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat admin
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Buat karyawan biasa
        $this->user = User::factory()->create(['role' => 'user']);

        // Buat tipe cuti
        $this->leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'default_quota' => 12,
        ]);
    }

    // ───────────────────────────────────────────────
    // HELPER: buat leave balance untuk user
    // ───────────────────────────────────────────────
    private function grantBalance(int $quota = 12, int $used = 0): LeaveBalance
    {
        return LeaveBalance::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'total_quota'   => $quota,
            'used'          => $used,
            'year'          => now()->year,
        ]);
    }

    /** @test */
    public function test_user_dapat_submit_cuti_jika_quota_cukup()
    {
        $this->grantBalance(12, 0); // 12 hari tersedia

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/leave-requests', [
                'leave_type_id' => $this->leaveType->id,
                'start_date'    => now()->addDays(1)->format('Y-m-d'),
                'end_date'      => now()->addDays(3)->format('Y-m-d'),
                'reason'        => 'Liburan keluarga',
            ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('leave_requests', ['user_id' => $this->user->id, 'status' => 'pending']);
    }

    /** @test */
    public function test_quota_tidak_cukup_gagal_submit()
    {
        $this->grantBalance(2, 2); // 0 hari tersisa (semua terpakai)

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/leave-requests', [
                'leave_type_id' => $this->leaveType->id,
                'start_date'    => now()->addDays(1)->format('Y-m-d'),
                'end_date'      => now()->addDays(5)->format('Y-m-d'),
                'reason'        => 'Test quota habis',
            ]);

        $response->assertStatus(400)
                 ->assertJson(['success' => false]);

        $this->assertStringContainsString('Insufficient', $response->json('message'));
    }

    /** @test */
    public function test_approve_mengurangi_saldo_cuti()
    {
        $balance = $this->grantBalance(12, 0);

        $leaveRequest = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDays(1)->format('Y-m-d'),
            'end_date'      => now()->addDays(3)->format('Y-m-d'),
            'total_days'    => 3,
            'reason'        => 'Test approve',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/leave-requests/{$leaveRequest->id}/approve", [
                'notes' => 'Disetujui',
            ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Saldo harus berkurang sebesar total_days
        $balance->refresh();
        $this->assertEquals(3, $balance->used);
        $this->assertEquals(9, $balance->total_quota - $balance->used);
    }

    /** @test */
    public function test_reject_tidak_mengurangi_saldo_cuti()
    {
        $balance = $this->grantBalance(12, 0);

        $leaveRequest = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDays(1)->format('Y-m-d'),
            'end_date'      => now()->addDays(3)->format('Y-m-d'),
            'total_days'    => 3,
            'reason'        => 'Test reject',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/leave-requests/{$leaveRequest->id}/reject", [
                'notes' => 'Alasan penolakan',
            ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Saldo TIDAK boleh berkurang
        $balance->refresh();
        $this->assertEquals(0, $balance->used);
        $this->assertEquals(12, $balance->total_quota - $balance->used);
    }

    /** @test */
    public function test_cancel_hanya_bisa_jika_status_pending()
    {
        $this->grantBalance(12, 0);

        // Buat request yang sudah approved
        $leaveRequest = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDays(1)->format('Y-m-d'),
            'end_date'      => now()->addDays(2)->format('Y-m-d'),
            'total_days'    => 2,
            'reason'        => 'Test cancel approved',
            'status'        => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/leave-requests/{$leaveRequest->id}/cancel");

        $response->assertStatus(400)
                 ->assertJson(['success' => false]);

        $this->assertStringContainsString('pending', $response->json('message'));
    }

    /** @test */
    public function test_delete_hanya_bisa_untuk_status_final()
    {
        $this->grantBalance(12, 0);

        // Buat request yang masih pending
        $leaveRequest = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDays(1)->format('Y-m-d'),
            'end_date'      => now()->addDays(2)->format('Y-m-d'),
            'total_days'    => 2,
            'reason'        => 'Test delete pending',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/leave-requests/{$leaveRequest->id}");

        $response->assertStatus(400)
                 ->assertJson(['success' => false]);
    }

    /** @test */
    public function test_tanggal_masa_lalu_ditolak()
    {
        $this->grantBalance(12, 0);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/leave-requests', [
                'leave_type_id' => $this->leaveType->id,
                'start_date'    => now()->subDays(5)->format('Y-m-d'),
                'end_date'      => now()->subDays(2)->format('Y-m-d'),
                'reason'        => 'Test tanggal lalu',
            ]);

        $response->assertStatus(400)
                 ->assertJson(['success' => false]);
    }

    /** @test */
    public function test_non_admin_tidak_bisa_approve()
    {
        $this->grantBalance(12, 0);

        $leaveRequest = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDays(1)->format('Y-m-d'),
            'end_date'      => now()->addDays(2)->format('Y-m-d'),
            'total_days'    => 2,
            'reason'        => 'Test non-admin approve',
            'status'        => 'pending',
        ]);

        // Buat user kedua (bukan admin) yang mencoba approve
        $otherUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/leave-requests/{$leaveRequest->id}/approve", [
                'notes' => 'Coba approve',
            ]);

        $response->assertStatus(403);
    }
}
