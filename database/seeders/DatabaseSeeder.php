<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        // 🔹 User biasa
        $user = User::create([
            'name' => 'User',
            'email' => 'user@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'user'
        ]);

        // 🔹 Leave Types
        $annual = LeaveType::create([
            'name' => 'Annual Leave',
            'default_quota' => 12
        ]);

        $sick = LeaveType::create([
            'name' => 'Sick Leave',
            'default_quota' => 6
        ]);

        // 🔹 Assign balance ke semua user
        foreach ([$admin, $user] as $u) {
            foreach ([$annual, $sick] as $type) {
                LeaveBalance::create([
                    'user_id' => $u->id,
                    'leave_type_id' => $type->id,
                    'year' => date('Y'),
                    'total_quota' => $type->default_quota,
                    'used' => 0
                ]);
            }
        }
    }
}
