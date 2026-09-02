<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class QaThreadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $basic = Certification::where('name', '基本情報技術者試験')->first();
        $advance = Certification::where('name', '応用情報技術者試験')->first();
        $toeic = Certification::where('name', 'TOEIC L&R 800 点コース')->first();
        $bookkeeping = Certification::where('name', '日商簿記 2 級')->first();
        $PMP = Certification::where('name', 'PMP')->first();

        $fixedStudent = User::where('name', '受講生花子')->first();
        $students = User::where('role', UserRole::Student)
            ->where('status', UserStatus::InProgress)
            ->where('id', '!=', $fixedStudent->id)
            ->get();
        $graduatedStudents = User::where('role', UserRole::Student)
            ->where('status', UserStatus::Graduated)
            ->where('id', '!=', $fixedStudent->id)
            ->get();
        $withdrawnStudents = User::withTrashed()
            ->where('role', UserRole::Student)
            ->where('status', UserStatus::Withdrawn)
            ->where('id', '!=', $fixedStudent->id)
            ->get();

        // 固定受講生（受講生花子）の初期データ　
        // bodyの文章の長さにばらつきができるように字数をコメントアウトで記す

        // 50字程度　
        $threadCreatedAt1 = Carbon::today()->subDays(15);
        QaThread::create([
            'certification_id' => $basic->id,
            'user_id' => $fixedStudent->id,
            'title' => 'IPアドレスとサブネットマスクの計算方法が分かりません',
            'body' => 'サブネットマスクの計算方法が分かりません。ネットワークアドレスの求め方を教えてください。',
            'status' => QaThreadStatus::Open,
            'created_at' => $threadCreatedAt1,
            'updated_at' => $threadCreatedAt1,
        ]);

        // 100字程度
        $threadCreatedAt2 = Carbon::today()->subDays(10);
        QaThread::create([
            'certification_id' => $basic->id,
            'user_id' => $fixedStudent->id,
            'title' => 'SQLのINNER JOINが苦手です',
            'body' => 'INNER JOINを使ったSQLの書き方が苦手です。テーブルを結合する考え方は分かるのですが、問題文からSQLを書くのが難しいです。基本的な考え方や練習方法があれば教えてください。',
            'status' => QaThreadStatus::Resolved,
            'created_at' => $threadCreatedAt2,
            'updated_at' => $threadCreatedAt2,
        ]);

        $threadCreatedAt3 = Carbon::today()->subDays(20);
        QaThread::create([
            'certification_id' => $advance->id,
            'user_id' => $fixedStudent->id,
            'title' => '午後問題の勉強方法が分かりません',
            'body' => '応用情報技術者試験の勉強を始めましたが、午後問題の対策方法が分かりません。午前問題は過去問を繰り返し解いていますが、午後問題は文章量が多く、
                        どこから読み始めればよいのか迷ってしまいます。また、記述式の解答にも慣れておらず、模範解答を見ても「なぜその答えになるのか」が理解できないことがあります。過去問を何年分くらい解けばよいのか、効率的な勉強方法やおすすめの進め方があれば教えていただきたいです。',
            'status' => QaThreadStatus::Open,
            'created_at' => $threadCreatedAt3,
            'updated_at' => $threadCreatedAt3,
        ]);

        // 500字程度
        $threadCreatedAt4 = Carbon::today()->subDays(15);
        QaThread::create([
            'certification_id' => $toeic->id,
            'user_id' => $fixedStudent->id,
            'title' => 'TOEICのリスニングが聞き取れません',
            'body' => 'TOEICのリスニングを勉強していますが、特にPart 3とPart 4になると話すスピードについていけず、内容を最後まで理解できません。
                        Part 1やPart 2はある程度正解できるのですが、会話や説明文が長くなると途中で聞き逃してしまい、その後の内容も分からなくなってしまいます。
                        また、選択肢を読んでいる間に音声が進んでしまい、焦ってしまうことも多いです。
                        現在のスコアは550点ほどで、次回の試験では700点を目標にしています。
                        シャドーイングやディクテーションが効果的だと聞いたことがありますが、どちらから始めるべきなのか、毎日どのくらいの時間取り組めばよいのか分かりません。
                        音読も取り入れた方が良いと聞きますが、どの順番で学習すると効率よくリスニング力を伸ばせるのでしょうか。
                        通勤時間などの隙間時間も活用して勉強したいと考えています。
                        おすすめの教材やアプリ、実際に550点前後から700点以上までスコアを伸ばした方が実践した勉強方法があれば、ぜひ教えていただきたいです。
                        また、本番で問題を先読みするコツや、聞き取れなかったときの立て直し方についてもアドバイスをいただけると嬉しいです。',
            'status' => QaThreadStatus::Open,
            'created_at' => $threadCreatedAt4,
            'updated_at' => $threadCreatedAt4,
        ]);

        // 30字程度
        $threadCreatedAt5 = Carbon::today()->subDays(30);
        QaThread::create([
            'certification_id' => $bookkeeping->id,
            'user_id' => $fixedStudent->id,
            'title' => '貸借対照表と損益計算書の違いが分かりません',
            'body' => '貸借対照表と損益計算書の違いが理解できません。覚え方を教えてください。',
            'status' => QaThreadStatus::Resolved,
            'created_at' => $threadCreatedAt5,
            'updated_at' => $threadCreatedAt5,
        ]);

        // 1100字程度
        $threadCreatedAt6 = Carbon::today()->subDays(7);
        QaThread::create([
            'certification_id' => $PMP->id,
            'user_id' => $fixedStudent->id,
            'title' => 'WBSの作成手順がよく分かりません',
            'body' => 'PMPの勉強を始めてまだ日が浅く、現在はプロジェクトスコープマネジメントの分野を中心に学習しています。
                        その中でもWBS（Work Breakdown Structure）の考え方がなかなか理解できず、実際のプロジェクトでどのように作成すればよいのかイメージが湧きません。
                        WBSはプロジェクト全体を細かい作業に分解していくためのものだということは理解しています。
                        しかし、どのレベルまで作業を分解すれば十分なのかが分からず、細かくしすぎても管理が大変になりそうですし、逆に大まかすぎると進捗管理が難しくなってしまうように感じています。
                        例えば、システム開発プロジェクトを例にすると、「要件定義」「基本設計」「詳細設計」「実装」「テスト」といったレベルで止めるべきなのか、それとも「ログイン画面作成」「会員登録API作成」「単体テスト」「結合テスト」など、さらに細かい単位まで分解するべきなのか判断できません。
                        また、プロジェクトの規模やメンバー数によって適切な粒度は変わるものなのでしょうか。
                        教材では100％ルールや成果物ベースで分解することが重要だと説明されていますが、実際に問題を解いてみると、どこまで分解すれば100％ルールを満たしているのか自信がありません。
                        試験問題では何となく正解できることもありますが、実務でWBSを作成する場面を想像すると、「これで漏れはないだろうか」「作業が重複していないだろうか」と不安になります。
                        また、WBSを作成した後にガントチャートやスケジュール表へどのようにつなげていくのかもよく理解できていません。WBS・ネットワーク図・ガントチャートの役割の違いや、それぞれをどの順番で作成するのかが曖昧です。
                        参考書を読んでも用語の意味は理解できるのですが、実際の流れをイメージできず、知識が点で止まってしまっています。
                        現在は市販の参考書と問題集を使って学習していますが、知識を暗記するだけでは限界があると感じています。実際に架空のプロジェクトを題材にWBSを作ってみる練習をした方が良いのか、それとも試験対策としては過去問を繰り返し解く方が効率的なのでしょうか。
                        もしおすすめの教材や、初心者向けの演習方法、理解しやすい動画などがあれば教えていただきたいです。
                        実務でプロジェクトマネージャーやリーダーを経験された方がいらっしゃれば、WBSを作成するときに意識しているポイントや、作業を分解する際の判断基準、初心者が陥りやすい失敗例なども教えていただけると嬉しいです。
                        また、PMP試験の学習を進めるうえで、WBSを効率よく理解するための勉強方法や、おすすめの順番があればぜひ参考にしたいと考えています。よろしくお願いいたします。',
            'status' => QaThreadStatus::Resolved,
            'created_at' => $threadCreatedAt6,
            'updated_at' => $threadCreatedAt6,
        ]);

        // 受講中ユーザーの初期データ
        $threadCreatedAt7 = Carbon::today()->subDays(40);
        QaThread::create([
            'certification_id' => $basic->id,
            'user_id' => $students->random()->id,
            'title' => '公開鍵暗号方式と共通鍵暗号方式の違いが分かりません',
            'body' => '公開鍵暗号方式と共通鍵暗号方式の違いが理解できません。
                        参考書では仕組みは説明されていますが,
                        「どのような場面で使い分けるのか」がイメージできず混乱しています。
                        また、SSL/TLSやHTTPSでは両方の方式が使われていると聞きましたが、
                        実際にどのような流れで利用されるのかもよく分かりません。
                        試験で解くときの覚え方や、それぞれの特徴・メリットを分かりやすく教えていただきたいです。',
            'status' => QaThreadStatus::Resolved,
            'created_at' => $threadCreatedAt7,
            'updated_at' => $threadCreatedAt7,
        ]);

        $threadCreatedAt8 = Carbon::today()->subDays(5);
        QaThread::create([
            'certification_id' => $advance->id,
            'user_id' => $students->random()->id,
            'title' => 'データベース設計問題の解き方が分かりません',
            'body' => '応用情報技術者試験のデータベース分野を勉強していますが、ER図や正規化の問題が苦手です。
                        参考書を読めば内容は理解できるのですが、午後問題になるとどのように考えればよいのか分からず、
                        いつも時間がかかってしまいます。
                        設問の読み方や、効率よく解くためのコツがあれば教えていただきたいです。',
            'status' => QaThreadStatus::Open,
            'created_at' => $threadCreatedAt8,
            'updated_at' => $threadCreatedAt8,
        ]);

        $threadCreatedAt9 = Carbon::today()->subDays(8);
        QaThread::create([
            'certification_id' => $advance->id,
            'user_id' => $students->random()->id,
            'title' => 'ネットワーク分野の勉強方法について教えてください',
            'body' => '応用情報技術者試験のネットワーク分野を勉強していますが、TCP/IPやルーティング、VPNなど覚えることが多く、知識が整理できません。
                        基本情報では用語を覚えるだけでもある程度対応できましたが、応用情報の午後問題ではネットワーク構成図やログを読み取る問題が多く、どこに注目すればよいのか分からなくなってしまいます。
                        ネットワーク分野を効率よく学習する方法や、午後問題を解く際に意識しているポイントがあれば教えていただきたいです。',
            'status' => QaThreadStatus::Open,
            'created_at' => $threadCreatedAt9,
            'updated_at' => $threadCreatedAt9,
        ]);

        $threadCreatedAt10 = Carbon::today()->subDays(45);
        QaThread::create([
            'certification_id' => $advance->id,
            'user_id' => $students->random()->id,
            'title' => '情報セキュリティの午後問題が苦手です',
            'body' => '応用情報技術者試験の午後問題では、情報セキュリティ分野を選択する予定です。
                        しかし、問題文が長く、攻撃手法や対策を整理しながら読むことができません。
                        また、設問では「最も適切な対策」を選ぶ問題が多く、どのような視点で判断すればよいのか迷ってしまいます。
                        セキュリティ分野を効率よく学習する方法や、午後問題を解くときに意識しているポイントがあれば教えていただきたいです。',
            'status' => QaThreadStatus::Open,
            'created_at' => $threadCreatedAt10,
            'updated_at' => $threadCreatedAt10,
        ]);

        $threadCreatedAt11 = Carbon::today()->subDays(10);
        QaThread::create([
            'certification_id' => $bookkeeping->id,
            'user_id' => $students->random()->id,
            'title' => '仕訳問題で借方と貸方を間違えてしまいます',
            'body' => '仕訳問題になると、借方と貸方を逆に書いてしまうことがよくあります。
                        勘定科目は覚えてきたのですが、本番になると混乱してしまいます。
                        借方・貸方を正しく判断するコツや、おすすめの勉強方法があれば教えてください。',
            'status' => QaThreadStatus::Resolved,
            'created_at' => $threadCreatedAt11,
            'updated_at' => $threadCreatedAt11,
        ]);

        // 卒業ユーザーの初期データ
        $threadCreatedAt12 = Carbon::today()->subDays(60);
        QaThread::create([
            'certification_id' => $basic->id,
            'user_id' => $graduatedStudents->random()->id,
            'title' => 'スタックとキューの違いについて教えてください',
            'body' => 'スタックとキューの違いが覚えられません。
                        LIFOとFIFOの意味は理解していますが、問題になるとどちらを使うべきか迷ってしまいます。
                        実際の利用例も合わせて教えていただけると嬉しいです。',
            'status' => QaThreadStatus::Open,
            'created_at' => $threadCreatedAt12,
            'updated_at' => $threadCreatedAt12,
        ]);

        // 退会ユーザーの初期データ
        $threadCreatedAt13 = Carbon::today()->subDays(50);
        QaThread::create([
            'certification_id' => $PMP->id,
            'user_id' => $withdrawnStudents->random()->id,
            'title' => 'クリティカルパスの求め方について教えてください',
            'body' => 'クリティカルパスの問題を解いていますが、最早開始時刻や最遅開始時刻の計算でいつも間違えてしまいます。
                        試験で確実に解けるようになるための勉強方法や、計算するときのポイントがあれば教えていただきたいです。',
            'status' => QaThreadStatus::Resolved,
            'created_at' => $threadCreatedAt13,
            'updated_at' => $threadCreatedAt13,
        ]);
    }
}
