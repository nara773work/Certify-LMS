<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EnrollmentNote;
use App\Models\Enrollment;
use App\Models\certification;

class EnrollmentNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student1 = User::where('email', 'student@certify-lms.test')->firstOrFail();
        $student2 = User::where('email', 'student-noquota@certify-lms.test')->firstOrFail();

        $coach = User::where('email', 'coach@certify-lms.test')->firstOrFail();
        $coach2 = User::where('email', 'coach2@certify-lms.test')->firstOrFail();
        $admin = User::where('email', 'admin@certify-lms.test')->firstOrFail();

        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->firstOrFail();
        $basic = Certification::where('name', '基本情報技術者試験')->firstOrFail();
        $boki = Certification::where('name', '日商簿記 2 級')->firstOrFail();

        $student1Toeic = Enrollment::where('user_id', $student1->id)
            ->where('certification_id', $toeic->id)
            ->firstOrFail();;

        $student1Boki = Enrollment::where('user_id', $student1->id)
            ->where('certification_id', $boki->id)
            ->firstOrFail();

        $student1Basic = Enrollment::where('user_id', $student1->id)
            ->where('certification_id', $basic->id)
            ->firstOrFail();

        $student2Basic = Enrollment::where('user_id', $student2->id)
            ->where('certification_id', $basic->id)
            ->firstOrFail();

        //TOIEC
        EnrollmentNote::create([
            'user_id'=>$coach->id,
            'enrollment_id'=>$student1Toeic->id,
            'body'=>'課題の提出を確認。次回は苦手分野を中心に復習予定。'
        ]);

        EnrollmentNote::create([
            'user_id'=>$coach2->id,
            'enrollment_id'=>$student1Toeic->id,
            'body'=>'今月の学習状況について面談を行い、現在の進捗と今後の学習方針を確認した。
            全体としては当初の予定に沿って学習を進めることができており、特に平日の学習時間が安定してきている。
            以前は予定していた学習を後回しにしてしまうこともあったが、最近は毎日の学習時間をある程度固定することで、継続して取り組めるようになってきたとのこと。
            本人も学習習慣が身についてきたことを実感しているため、今後も現在のペースを大きく崩さずに継続することを勧めた。
            一方で、問題演習の結果を見ると、基礎的な問題については正答率が上がっているものの、複数の知識を組み合わせて考える必要がある問題では、まだ不安定な部分が見られる。特に間違えた問題について、解説を読んで理解したつもりになってしまい、時間を空けて再度解いた際に同じ問題を間違えるケースがある。
            そのため、今後は単純に問題数を増やすのではなく、間違えた問題を記録し、数日後にもう一度解き直す方法を取り入れることとした。
            また、学習内容について本人からいくつか質問があり、理解が曖昧になっている分野を確認した。
            現時点では大きく遅れているわけではないため、未習範囲を急いで進めるよりも、これまで学習した内容の定着を優先する方針とした。
            次回面談までには、現在取り組んでいる範囲を一通り終えたうえで、苦手分野の問題演習を重点的に行ってもらう予定。余裕があれば模擬問題にも取り組み、実際の試験形式で時間配分や理解度を確認する。
            今後の面談では、学習時間だけを見るのではなく、問題演習の正答率や間違えた問題の傾向も確認する。
            本人が無理なく学習を継続できる状態を維持しながら、苦手分野を少しずつ減らしていくことを目標とする。現状では学習ペースに大きな問題はないため、過度に学習量を増やすのではなく、復習と定着を意識した学習に切り替えていくことが適切と判断した。'
        ]);

        //基本情報
        EnrollmentNote::create([
            'user_id'=>$coach->id,
            'enrollment_id'=>$student1Basic->id,
            'body'=>'学習進捗が予定より2週間程度進んでいる',
        ]);

        EnrollmentNote::create([
            'user_id'=>$coach->id,
            'enrollment_id'=>$student2Basic->id,
            'body'=>'今月の学習状況を確認したところ、全体としては計画に沿って進められている。
            特に平日の学習時間が安定しており、以前よりも継続して取り組めるようになってきた点は良い傾向。
            問題演習についても、基本的な内容は理解できているが、応用問題になると正答率が下がる傾向が見られる。
            本人も苦手分野を把握しているため、今後は間違えた問題をそのままにせず、なぜ間違えたのかを確認する習慣をつけてもらう。
            次回の面談までに苦手分野の問題を中心に復習し、可能であれば一度模擬問題にも取り組んでもらう予定。
            学習量を無理に増やすよりも、現在のペースを維持しながら理解度を高めることを優先する。
            次回は学習時間だけでなく、苦手分野の理解度と問題演習の結果も合わせて確認する。'
        ]);

        //簿記
        EnrollmentNote::create([
            'user_id'=>$coach2->id,
            'enrollment_id'=>$student1Boki->id,
            'body'=>'前回よりも学習時間が安定してきた。苦手分野についてはまだ理解が曖昧な部分があるため、次回までにテキストを再確認してもらう。面談では具体的なつまずきも確認する。'
        ]);

        EnrollmentNote::create([
            'user_id'=>$admin->id,
            'enrollment_id'=>$student1Boki->id,
            'body'=>'面談間隔がかなり空いている。次回面談の予定を聞く。'
        ]);
    }
}
