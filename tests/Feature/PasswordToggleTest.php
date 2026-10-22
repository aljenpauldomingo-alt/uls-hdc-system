<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Every password field has a show/hide (eye) button.
 */
class PasswordToggleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Each password input is followed by exactly one toggle that controls it.
     */
    private function assertEveryPasswordFieldHasAToggle(TestResponse $response, int $expected): void
    {
        $html = $response->assertOk()->getContent();

        preg_match_all('/<input[^>]*type="password"[^>]*>/s', $html, $inputs);
        preg_match_all('/<button[^>]*data-password-toggle[^>]*>/s', $html, $toggles);

        $this->assertCount($expected, $inputs[0], 'Password fields on the page.');
        $this->assertCount($expected, $toggles[0], 'One toggle per password field.');

        foreach ($inputs[0] as $index => $input) {
            preg_match('/\bid="([^"]+)"/', $input, $id);
            $this->assertStringContainsString('aria-controls="'.$id[1].'"', $toggles[0][$index]);
            $this->assertStringContainsString('type="button"', $toggles[0][$index], 'The toggle never submits the form.');
            $this->assertStringContainsString('aria-label="Show password"', $toggles[0][$index]);
            $this->assertStringContainsString('aria-pressed="false"', $toggles[0][$index]);
        }
    }

    public function test_guest_pages_have_password_toggles(): void
    {
        $this->assertEveryPasswordFieldHasAToggle($this->get(route('login')), 1);
        $this->assertEveryPasswordFieldHasAToggle($this->get(route('register')), 2);
        $this->assertEveryPasswordFieldHasAToggle($this->get(route('password.reset', 'token')), 2);
    }

    public function test_signed_in_pages_have_password_toggles(): void
    {
        $student = User::factory()->student()->create();
        $this->assertEveryPasswordFieldHasAToggle($this->actingAs($student)->get(route('password.confirm')), 1);
        $this->assertEveryPasswordFieldHasAToggle($this->actingAs($student)->get(route('profile.edit')), 3);

        $newCounselor = User::factory()->counselor()->create(['must_change_password' => true]);
        $this->assertEveryPasswordFieldHasAToggle($this->actingAs($newCounselor)->get(route('password.change')), 3);
    }

    public function test_counselor_create_and_reset_forms_have_password_toggles(): void
    {
        $director = User::factory()->director()->create();
        $counselor = User::factory()->counselor([College::factory()->create()])->create();

        $this->assertEveryPasswordFieldHasAToggle($this->actingAs($director)->get(route('director.counselors.create')), 1);
        $this->assertEveryPasswordFieldHasAToggle($this->actingAs($director)->get(route('director.counselors.edit', $counselor)), 1);
    }
}
