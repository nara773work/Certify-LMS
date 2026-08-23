<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\GoogleCalendarToken;
use App\Models\User;
use Illuminate\Database\Seeder;

class GoogleCalendarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GoogleCalendarToken::create([
            'user_id' => User::where('email','coach@certify-lms.test')->first()->id,
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'expires_at' => now()->addHour(),
            'refresh_token_expires_at' => now()->addDays(7),
        ]);
    }
}
