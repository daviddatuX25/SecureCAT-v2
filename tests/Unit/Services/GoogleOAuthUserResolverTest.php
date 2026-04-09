<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserCredential;
use App\Services\GoogleOAuthUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOAuthUserResolverTest extends TestCase
{
    use RefreshDatabase;

    private function makeSocialiteUser(string $sub, string $email): SocialiteUser
    {
        $mock = Mockery::mock(SocialiteUser::class);
        $mock->shouldReceive('getId')->andReturn($sub);
        $mock->shouldReceive('getEmail')->andReturn($email);
        return $mock;
    }

    public function test_returns_user_when_credential_already_exists(): void
    {
        $user = User::factory()->create(['email' => 'john@gmail.com']);
        UserCredential::create([
            'user_id'    => $user->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-abc-123',
        ]);

        $socialUser = $this->makeSocialiteUser('sub-abc-123', 'john@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
        $this->assertCount(1, UserCredential::all());
    }

    public function test_creates_credential_and_returns_user_on_first_google_signin(): void
    {
        $user = User::factory()->create(['email' => 'jane@gmail.com']);

        $socialUser = $this->makeSocialiteUser('sub-xyz-456', 'jane@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);

        $credential = UserCredential::where('user_id', $user->id)
            ->where('provider', UserCredential::PROVIDER_GOOGLE)
            ->first();
        $this->assertNotNull($credential);
        $this->assertEquals('sub-xyz-456', $credential->identifier);
    }

    public function test_email_match_is_case_insensitive(): void
    {
        $user = User::factory()->create(['email' => 'Staff@Gmail.com']);

        $socialUser = $this->makeSocialiteUser('sub-999', 'staff@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_returns_null_when_no_matching_user(): void
    {
        $socialUser = $this->makeSocialiteUser('sub-unknown', 'unknown@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNull($result);
        $this->assertCount(0, UserCredential::all());
    }
}
