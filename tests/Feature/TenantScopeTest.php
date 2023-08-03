<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use App\Models\Tenant, App\Models\User;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test  */
    public function a_model_has_a_tenant_id_on_the_migration(){
        $now = now();
        
        //make migration file
        $this->artisan('make:migration create_tests_table');

        $filename = $now->year . '_' . $now->format('m') . '_' . $now->format('d') . '_' .  $now->format('h'). $now->format('i') . $now->format('s') . '_create_tests_table.php';

        $file_path = database_path("migrations/$filename");

        $this->assertFileExists($file_path);

        $this->assertStringContainsString('$table->unsignedBigInteger(\'tenant_id\')->index()', File::get($file_path));

        File::delete($file_path);

        $this->assertFileDoesNotExist($file_path);
    }


    /** @test */
    public function a_user_can_only_see_users_in_the_same_tenant(){
        //create a tenant with users
        $firstTenant = Tenant::factory()->create();
        $users1 = User::factory()->count(5)->create(['tenant_id' => $firstTenant->id]);

        $secondTenant = Tenant::factory()->create();
        User::factory()->count(5)->create(['tenant_id' => $secondTenant->id]);

        auth()->login($users1[0]);
        
        $this->assertEquals(5, User::count());
    }

    /** @test */
    public function a_user_can_only_create_a_user_in_his_tenant(){
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->create([
            'tenant_id' => $tenant1->id
        ]);

        auth()->login($user1);

        //create the user
        $user = User::factory()->create();

        $this->assertTrue($user->tenant_id === $user1->tenant_id);
    }

    /** @test */
    public function a_user_can_only_create_a_user_in_his_tenant_even_if_another_tenant_is_provided(){
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->create([
            'tenant_id' => $tenant1->id
        ]);

        auth()->login($user1);

        //create the user
        $user = User::factory()->make();
        $user->tenant_id = $tenant2->tenant_id;
        $user->save();

        $this->assertTrue($user->tenant_id === $user1->tenant_id);
    }
}
