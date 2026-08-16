<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Announcement::create([
            'title'=>'アクセス制限のお知らせ',
            'body'=>'システムメンテナンスのため、2026.09.10の13:00~15:00はアクセスが制限されます',
        ]);
    }
}
