
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@foodrequest.com'],
            [
                'name' => 'System Administrator',
                'ippis_no' => 'ADMIN001',
                'password' => Hash::make('admin123'),
                'department' => 'IT',
                'role' => 'admin'
            ]
        );
    }
}
