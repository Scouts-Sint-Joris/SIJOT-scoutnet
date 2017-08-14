<?php

namespace Tests\Feature;

use Sijot\Role;
use Sijot\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LeaseInfoTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    /**
     * @test
     */
    public function testShowLeaseValidId()
    {
        // TODO: Write test
    }

    /**
     * @test
     */
    public function testShowLeaseInvalidId()
    {
        $user      = factory(User::class)->create();
        $leaseRole = factory(Role::class)->create(['name' => 'verhuur']);

        $leaseUser = User::findOrfail($user->id);
        $leaseUser->assignRole($leaseRole->name);

        $this->assertTrue($leaseUser->hasRole('verhuur'));

        $this->actingAs($leaseUser)
            ->seeIsAuthenticatedAs($leaseUser)
            ->get(route('lease.info.show', ['id' => 1000]))
            ->assertSessionHas(['flash_notification.0.message'   => 'Wij konden de verhuring niet vinden in het systeem.'])
            ->assertStatus(302);
    }

    /**
     * @test
     */
    public function testUpdateLeaseInvalid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testUpdateLeaseValid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testMakeLeaseAdminValid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testMakeLeaseAdminInvalid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testDeleteLeaseAdminInvalid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testDeleteAdminLeaseAdminValid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testDeleteNotitionInvalid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testDeleteNotitionValid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testCreateNotitionValid()
    {
        // TODO: write test.
    }

    /**
     * @test
     */
    public function testCreateNotitionInvalid()
    {
        // TODO: write test.
    }
}
