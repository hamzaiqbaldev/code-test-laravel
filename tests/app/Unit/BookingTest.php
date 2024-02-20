<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use DTApi\Helpers\TeHelper;
namespace DTApi\Repository\UserRepository;
use DTApi\Repository\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;


class BookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    public function test_helper_for_duetime_calculation()
    {
        $dueTimePlus90 = Carbon::createFromTimestamp(strtotime('+91 hours'));
        $dueTimeLess90 = Carbon::createFromTimestamp(strtotime('+40 hours'));

        $response1 = TeHelper::willExpireAt($dueTimePlus90->format('Y-m-d H:i:s'), date('Y-m-d H:i:s'));
        $response2 = TeHelper::willExpireAt($dueTimeLess90->format('Y-m-d H:i:s'), date('Y-m-d H:i:s'));

        $this->assertEquals(($dueTimePlus90->diffInHours() - 48), Carbon::createFromDate($response1)->diffInHours());
        $this->assertEquals($dueTimeLess90->diffInHours(), Carbon::createFromDate($response2)->diffInHours());
    }

    public function test_that_user_function_creates_and_updates_model() {
        $userData = array('role' => 1, 'name' => 'Test', 
        'company_id' => 0, 'department_id' => 0, 'email' => 'test@test.com',
        'dob_or_orgid' => '123', 'phone' => '+92321123456', 'mobile' => '+92123456789');
        
        $responseCreate = UserRepository::createOrUpdate($id = null, $userData);

        $this->assertDatabaseHas('users', [
            'test@test.com',
        ]);
        if(!empty($responseCreate->id)) {
            $userData['email'] = 'test123@test.com';
            $responseUpdate = UserRepository::createOrUpdate($responseCreate->id, $userData);
            $this->assertDatabaseHas('users', [
                'test123@test.com',
            ]);
        }
    }
}
