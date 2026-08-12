<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Enums\UserRole;
use App\Enums\UserStatus;

class EnrollmentGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fixeduser = User::query()
            ->where('email', 'student@certify-lms.test')
            ->where('role', UserRole::Student->value)
            ->firstOrFail();

        $demoStudents = User::query()
            ->where('role', UserRole::Student->value)
            ->where('status', UserStatus::InProgress->value)
            ->whereNotIn('email', ['student@certify-lms.test', 'student-noquota@certify-lms.test'])
            ->get();

        $enrollmentBasic = $fixeduser->enrollments()->firstOrFail();

        $enrollmentAdvance = $fixeduser
        ->enrollments()
        ->whereHas('certification', function ($query) {
            $query->where('name', '応用情報技術者試験');
        })->first();

        $enrollmentTOEIC = $fixeduser
        ->enrollments()
        ->whereHas('certification', function ($query) {
            $query->where('name', 'TOEIC L&R 800 点コース');
        })->first();

        $enrollmentBoki = $fixeduser
        ->enrollments()
        ->whereHas('certification', function ($query) {
            $query->where('name', '日商簿記 2 級');
        })->first();

        EnrollmentGoal::create([
            'enrollment_id' => $enrollmentBasic->id,
            'title' => '過去問3年分を解き切る',
            'target_date' => Carbon::today()->addDays(7),
            'description' => '1週間以内に過去問3年分を解き、わからなかった問題をまとめる。',
            'achieved_at' => null,
            'user_id' => $fixeduser->id,
        ]);

        EnrollmentGoal::create([
            'enrollment_id' => $enrollmentAdvance ->id,
            'title' => '用語を覚える',
            'target_date' => Carbon::today()->addDays(5),
            'description' => '毎日用語の復習をする',
            'achieved_at' => null,
            'user_id' => $fixeduser->id,
        ]);

        EnrollmentGoal::create([
            'enrollment_id' => $enrollmentTOEIC ->id,
            'title' => '過去問で700点を超える',
            'target_date' => Carbon::today()->addDays(15),
            'description' => '2週間後に過去問を解き、700点越えを目指す',
            'achieved_at' => null,
            'user_id' => $fixeduser->id,
        ]);

        EnrollmentGoal::create([
            'enrollment_id' => $enrollmentBoki->id,
            'title' => '仕分けのやり方を覚える',
            'target_date' => Carbon::today()->addDays(7),
            'description' => '仕分けになれる',
            'achieved_at' => Carbon::today()->addDays(3),
            'user_id' => $fixeduser->id,
        ]);

        //demo
    foreach ($demoStudents as $student) {

        $enrollmentBasic = $student->enrollments()
            ->whereHas('certification', function ($query) {
                $query->where('name', '基本情報技術者試験');
            })
            ->first();

        $enrollmentAdvance = $student->enrollments()
            ->whereHas('certification', function ($query) {
                $query->where('name', '応用情報技術者試験');
            })
            ->first();

        $enrollmentBoki = $student->enrollments()
            ->whereHas('certification', function ($query) {
                $query->where('name', '日商簿記 2 級');
            })
            ->first();

        if ($enrollmentBasic) {
            EnrollmentGoal::create([
                'enrollment_id' => $enrollmentBasic->id,
                'title' => 'B問題の傾向になれる',
                'target_date' => Carbon::today()->addDays(7),
                'description' => '過去3年分のB問題を解く',
                'achieved_at' => null,
                'user_id' => $student->id,
            ]);
        }

        if ($enrollmentAdvance) {
            EnrollmentGoal::create([
                'enrollment_id' => $enrollmentAdvance->id,
                'title' => 'セキュリティ分野を克服する',
                'target_date' => Carbon::today()->addDays(7),
                'description' => 'セキュリティ分野の問題を50問以上解く',
                'achieved_at' => Carbon::today()->addDays(3),
                'user_id' => $student->id,
            ]);
        }

        if ($enrollmentBoki) {
            EnrollmentGoal::create([
                'enrollment_id' => $enrollmentBoki->id,
                'title' => '損益計算書の作成に慣れる',
                'target_date' => Carbon::today()->addDays(7),
                'description' => '損益計算書の作成に慣れるために問題集を1周する',
                'achieved_at' => Carbon::today()->addDays(3),
                'user_id' => $student->id,
            ]);
        }
    }

    }
}
