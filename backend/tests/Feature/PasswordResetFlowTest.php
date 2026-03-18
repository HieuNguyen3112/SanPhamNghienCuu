<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /** @test */
    public function forgot_password_returns_neutral_success_and_sends_email_for_existing_account()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'lecturer@example.com',
        ]);

        $this->postJson('/password/forgot', [
            'email' => 'lecturer@example.com',
        ])->assertOk()
            ->assertJson([
                'code' => 'PASSWORD_RESET_LINK_SENT_IF_ACCOUNT_EXISTS',
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /** @test */
    public function forgot_password_returns_same_neutral_success_for_unknown_account()
    {
        Notification::fake();

        $this->postJson('/password/forgot', [
            'email' => 'missing@example.com',
        ])->assertOk()
            ->assertJson([
                'code' => 'PASSWORD_RESET_LINK_SENT_IF_ACCOUNT_EXISTS',
            ]);

        Notification::assertNothingSent();
    }

    /** @test */
    public function reset_notification_uses_frontend_spa_link()
    {
        Notification::fake();

        config()->set('app.frontend_url', 'http://frontend.test');

        $user = User::factory()->create([
            'email' => 'lecturer@example.com',
        ]);

        $this->postJson('/password/forgot', [
            'email' => 'lecturer@example.com',
        ])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mailMessage = $notification->toMail($user);
            $actionUrl = (string) ($mailMessage->actionUrl ?? '');

            return str_contains($actionUrl, 'http://frontend.test/reset-password?token=')
                && str_contains($actionUrl, 'email=lecturer%40example.com');
        });
    }

    /** @test */
    public function reset_password_updates_password_clears_flag_and_revokes_tokens()
    {
        $user = User::factory()->create([
            'email' => 'lecturer@example.com',
            'password' => bcrypt('old-password'),
            'must_change_password' => true,
        ]);

        $token = Password::broker()->createToken($user);
        $personalToken = $user->createToken('existing-device');

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => explode('|', $personalToken->plainTextToken)[0],
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);

        $this->postJson('/password/reset', [
            'token' => $token,
            'email' => 'lecturer@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk()
            ->assertJson([
                'code' => 'PASSWORD_RESET_SUCCESS',
            ]);

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(password_verify('new-password-123', $user->password));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /** @test */
    public function reset_password_rejects_invalid_token_with_user_friendly_code()
    {
        $user = User::factory()->create([
            'email' => 'lecturer@example.com',
        ]);

        $this->postJson('/password/reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422)
            ->assertJson([
                'code' => 'INVALID_RESET_TOKEN',
            ]);
    }
}
