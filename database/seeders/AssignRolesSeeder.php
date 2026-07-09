<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AssignRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $garsonRole = Role::firstOrCreate(['name' => 'garson']);

        $users = User::all();
        //Assign admin role to user
        foreach ($users as $user) {
            if ($user->username == 'peyman') {
                $user->assignRole($adminRole);
            } elseif ($user->username == '102') {
                $user->assignRole($cashier);

            } else {
                $user->assignRole($garsonRole);
            }
        }
    }
}
