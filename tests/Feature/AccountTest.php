<?php

namespace Tests\Feature;

use Sijot\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\{WithoutMiddleware, DatabaseTransactions, DatabaseMigrations};

/**
 * Class AccountTest
 *
 * @package Tests\Feature
 */
class AccountTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    /**
     * Test the account index route.
     *
     * @test
     * @group  all
     * @covers \Sijot\Http\Controllers\AccountController::index()
     */
    public function testAccountIndex()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user)
            ->seeIsAuthenticatedAs($user)
            ->get(route('account'))
            ->assertStatus(200);
    }

    /**
     * Test the account information update method (With validation errors)
     *
     * @test
     * @group  all
     * @covers \Sijot\Http\Controllers\AccountController::updateInfo()
     */
    public function testAccountSettingsUpdateWithValidationErr()
    {
        $user  = factory(User::class)->create();
        $input = ['user_id' => $user->id];

        $this->actingAs($user)
            ->seeIsAuthenticatedAs($user)
            ->post(route('account.info'), $input)
            ->assertStatus(200)
            ->assertSessionHasErrors()
            ->assertSessionMissing(['flash_notification.0.message' => trans('account.flash-account-info')]);
    }

    /**
     * Test the user update method for an account. (Without the validation errors)
     *
     * @test
     * @group  all
     * @covers \Sijot\Http\Controllers\AccountController::updateInfo()
     */
    public function testAccountSettingsWithoutValidationErr()
    {
        $user = factory(User::class)->create();

        $input = [
            'user_id' => $user->id,
            'theme'   => 1,
            'email'   => 'janmetdepet@gmail.com',
            'name'    => 'Jan met de pet',
        ];

        $this->actingAs($user)
            ->seeIsAuthenticatedAs($user)
            ->post(route('account.info'), $input)
            ->assertStatus(302)
            ->assertSessionHas(['flash_notification.0.message' => trans('account.flash-account-info')]);
    }

    /**
     * Test the account password update (without validation errors)
     *
     * @test
     * @group  all
     * @covers \Sijot\Http\Controllers\AccountController::updateSecurity()
     */
    public function testAccountPasswordWithoutValidationErr()
    {
        $user = factory(User::class)->create();

        $input = [
            'user_id'               => $user->id,
            'password'              => 'IkBenEenWachtwoord',
            'password_confirmation' => 'IkBenEenWachtwoord'
        ];

        $this->actingAs($user)
            ->seeIsAuthenticatedAs($user)
            ->post(route('account.security'), $input)
            ->assertStatus(302)
            ->assertSessionHas(['flash_notification.0.message' => trans('account.flash-account-password')]);
    }

    /**
     * Test the account password update (with validation errors)
     *
     * @test
     * @group  all
     * @covers \Sijot\Http\Controllers\AccountController::updateSecurity()
     */
    public function testAcccountPasswordWithValidationErr()
    {
        $user  = factory(User::class)->create();
        $input = ['user_id' => $user->id];

        $this->actingAs($user)
            ->seeIsAuthenticatedAs($user)
            ->post(route('account.security'), $input)
            ->assertStatus(200)
            ->assertSessionHasErrors()
            ->assertSessionMissing(['flash_notification.0.message' => trans('account.flash-account-password')]);
    }

    /**
     * Test if we can update an invalid user his password.
     *
     * @test
     * @group  all
     * @covers \Sijot\Http\Controllers\AccountController::updateSecurity()
     */
    public function testAccountPasswordInvalidUser()
    {
        $user  = factory(User::class)->create(['id' => 123456]);
        $input = [
            'user_id'               => 1,
            'password'              => 'IkBenEenWachtwoord',
            'password_confirmation' => 'IkBenEenWachtwoord'
        ];

        $this->actingAs($user)
            ->seeIsAuthenticatedAs($user)
            ->post('account.security', $input)
            ->assertStatus(404)
            ->assertSessionMissing(['flash_notification.0.message' => trans('account.flash-account-password')]);
    }
}
