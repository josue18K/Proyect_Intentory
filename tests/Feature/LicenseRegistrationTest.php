<?php

namespace Tests\Feature;

use App\Models\{License, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_activates_an_available_license(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $license = License::create(['code' => 'TEST-CODE-01', 'created_by' => $admin->id]);

        $this->post(route('register.store'), [
            'name' => 'Nuevo vendedor', 'email' => 'vendedor@test.local',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'license_code' => 'test-code-01',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(User::where('email', 'vendedor@test.local')->first());
        $this->assertDatabaseHas('licenses', ['id' => $license->id, 'status' => 'activated']);
    }

    public function test_registration_rejects_a_used_license(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        License::create(['code' => 'USED-CODE', 'created_by' => $admin->id, 'status' => 'activated']);

        $this->post(route('register.store'), [
            'name' => 'Rechazado', 'email' => 'reject@test.local',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'license_code' => 'USED-CODE',
        ])->assertStatus(422);
    }
}
