<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserCredential;
use App\Support\GoogleOAuthConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleSignInTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialite(string $sub, string $email): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($sub);
        $socialUser->shouldReceive('getEmail')->andReturn($email);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);
        $provider->shouldReceive('redirect')->andReturn(
            redirect('https://accounts.google.com/o/oauth2/auth?fake=1')
        );

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'fake-client-id',
            'services.google.client_secret' => 'fake-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_redirect_sends_guest_to_google(): void
    {
        $this->configureGoogle();
        $this->mockSocialite('sub-1', 'test@gmail.com');

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('google.com', $response->headers->get('Location'));
    }

    public function test_callback_logs_in_existing_linked_user(): void
    {
        $this->configureGoogle();

        $user = User::factory()->create(['email' => 'linked@gmail.com']);
        UserCredential::create([
            'user_id' => $user->id,
            'provider' => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-linked-123',
        ]);

        $this->mockSocialite('sub-linked-123', 'linked@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_auto_links_and_logs_in_user_on_first_signin(): void
    {
        $this->configureGoogle();

        $user = User::factory()->create(['email' => 'newlink@gmail.com']);
        $this->mockSocialite('sub-new-456', 'newlink@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('user_credentials', [
            'user_id' => $user->id,
            'provider' => 'google',
            'identifier' => 'sub-new-456',
        ]);
    }

    public function test_callback_redirects_to_login_when_no_matching_user(): void
    {
        $this->configureGoogle();

        $this->mockSocialite('sub-orphan', 'orphan@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_google_routes_not_registered_without_config(): void
    {
        // Skip if Google is configured in the environment (routes already registered at boot)
        // This test verifies behavior when env vars are ABSENT (CI/pre-production)
        if (GoogleOAuthConfig::isConfigured()) {
            $this->markTestSkipped('Google OAuth is configured in this environment; routes are registered at boot and cannot be unregistered per-test.');
        }

        config(['services.google.client_id' => '', 'services.google.client_secret' => '']);

        $this->assertFalse(GoogleOAuthConfig::isConfigured());

        // Routes should NOT be registered when unconfigured
        $this->assertFalse(Route::has('auth.google.redirect'));
        $this->assertFalse(Route::has('auth.google.callback'));
    }
}
