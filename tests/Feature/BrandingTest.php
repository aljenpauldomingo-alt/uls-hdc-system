<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    private const FAVICONS = [
        'favicon-32x32.png?v=2',
        'favicon-16x16.png?v=2',
        'favicon.ico?v=2',
        'apple-touch-icon.png?v=2',
    ];

    public function test_favicon_files_exist(): void
    {
        foreach (['favicon-16x16.png' => 16, 'favicon-32x32.png' => 32, 'apple-touch-icon.png' => 180] as $file => $size) {
            [$width, $height] = getimagesize(public_path($file));
            $this->assertSame([$size, $size], [$width, $height], $file);
        }

        $this->assertStringStartsWith("\x00\x00\x01\x00", file_get_contents(public_path('favicon.ico')));
    }

    public function test_guest_pages_show_the_logo_title_glass_card_and_favicons(): void
    {
        $user = User::factory()->create();
        $pages = [url('/'), route('login'), route('register'), route('password.request'), route('password.reset', Password::createToken($user))];

        foreach ($pages as $url) {
            $response = $this->get($url)->assertOk()
                ->assertSee(asset('images/hdc-logo.png'), false)
                ->assertSee('alt="ULS Human Development Center"', false)
                ->assertSee('h-24 w-24', false)
                ->assertSee('glass-login', false)
                ->assertSee('class="beach-anim"', false)
                ->assertSeeTextInOrder(['Human Development Center', 'University of La Salette, Inc.']);

            foreach (self::FAVICONS as $favicon) {
                $response->assertSee($favicon, false);
            }
        }
    }

    public function test_app_layout_shows_the_logo_in_the_navigation_and_favicons(): void
    {
        $response = $this->actingAs(User::factory()->director()->create())->get(route('director.dashboard'))->assertOk()
            ->assertSee(asset('images/hdc-logo.png'), false)
            ->assertSee('h-9 w-9', false)
            ->assertSeeText('ULS HDC');

        foreach (self::FAVICONS as $favicon) {
            $response->assertSee($favicon, false);
        }
    }
}
