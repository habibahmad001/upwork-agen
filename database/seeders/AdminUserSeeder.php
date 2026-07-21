<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update admin user
        User::updateOrCreate(
            ['email' => 'habibahmed001@gmail.com'],
            [
                'name' => 'habib',
                'password' => Hash::make('ha03228594463'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Admin user created/updated successfully.');
        $this->command->info('   Email: habibahmed001@gmail.com');
        $this->command->info('   Password: ha03228594463');
        $this->command->warn('   Please change the password after first login!');
    }
}
