<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\QaReply;
use App\Models\User;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Carbon\Carbon;

class QaReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fixedStudent = User::where('name','受講生花子')->first();
        $students = User::where('role', UserRole::Student)
                ->where('status', UserStatus::InProgress)
                ->where('id', '!=', $fixedStudent->id)
                ->get();
        $graduatedStudents =User::where('role', UserRole::Student)
                ->where('status', UserStatus::Graduated)
                ->where('id', '!=', $fixedStudent->id)
                ->get();
        $withdrawnStudents = User::withTrashed()
                ->where('role', UserRole::Student)
                ->where('status', UserStatus::Withdrawn)
                ->where('id', '!=', $fixedStudent->id)
                ->get();
        $ITCoach =User::where('role', UserRole::Coach)
                ->where('email', 'coach@certify-lms.test')
                ->first();
        $businessCoach =User::where('role', UserRole::Coach)
                ->where('email', 'coach2@certify-lms.test')
                ->first();

        //受講生花子
        $thread1 = QaThread::where('title','公開鍵暗号方式と共通鍵暗号方式の違いが分かりません')->first();
        $replyCreatedAt7 = $thread1->created_at->copy()->addDays(3);
        QaReply::create([
            'user_id' => $fixedStudent->id,
            'body' => '私も最初は混乱していましたが、**「鍵が1つか2つか」**で覚えるようにしたら理解しやすくなりました。* **共通鍵暗号方式**：1つの鍵を送る側と受け取る側で共有する* **公開鍵暗号方式**：公開鍵と秘密鍵の2つの鍵を使う試験では、「共通鍵は速いけど鍵を安全に渡すのが難しい」「公開鍵は安全に鍵を渡せるけど処理は遅い」と覚えておくと解きやすいです。HTTPSでは、最初に公開鍵暗号方式で安全に鍵をやり取りし、その後は処理が速い共通鍵暗号方式で通信します。この流れも一緒に覚えておくと試験で役立ちます。',
            'qa_thread_id' => $thread1->id,
            'created_at' => $replyCreatedAt7,
            'updated_at' => $replyCreatedAt7,
        ]);

        $thread2 = QaThread::where('title','データベース設計問題の解き方が分かりません')->first();
        $replyCreatedAt8 = $thread2->created_at->copy()->addDays(1);
        QaReply::create([
            'user_id' => $fixedStudent->id,
            'body' => '私もデータベース問題は苦手でしたが、ER図を紙に書きながら問題を解くようにしたら理解しやすくなりました。最初は時間がかかっても構わないので、テーブル同士の関係を整理してから設問を読むようにすると、解答までの流れが見えやすくなりました。',
            'qa_thread_id' => $thread2->id,
            'created_at' => $replyCreatedAt8,
            'updated_at' => $replyCreatedAt8,
        ]);

        //受講中ユーザー
        $thread3 = QaThread::where('title','IPアドレスとサブネットマスクの計算方法が分かりません')->first();
        $replyCreatedAt1 = $thread3->created_at->copy()->addDays(10);
        QaReply::create([
            'user_id' => $students ->where('id', '!=', $thread1->user_id)->random()->id,
            'body' => '私も最初は苦手でしたが、問題を解くたびにIPアドレスとサブネットマスクを2進数に書き直すようにしたら理解できるようになりました。慣れるまでは時間がかかりますが、繰り返し計算しているうちに「どこがネットワーク部なのか」が自然と分かるようになるのでおすすめです。',
            'qa_thread_id' => $thread3->id,
            'created_at' => $replyCreatedAt1,
            'updated_at' => $replyCreatedAt1,
        ]);

        $thread4 = QaThread::where('title','仕訳問題で借方と貸方を間違えてしまいます')->first();
        $replyCreatedAt11 = $thread4->created_at->copy()->addDays(4);
        QaReply::create([
            'user_id' => $students ->where('id', '!=', $thread2->user_id)->random()->id,
            'body' => '私も最初は借方と貸方を毎回間違えていました。
                        勘定科目を暗記するよりも、「何が増えたか・何が減ったか」を考えてから仕訳を書くようにしたら、
                        少しずつ間違いが減りました。
                        毎日10問程度でも継続して解くと、自然と判断できるようになります。',
            'qa_thread_id' => $thread4->id,
            'created_at' => $replyCreatedAt11,
            'updated_at' => $replyCreatedAt11,
        ]);

        //卒業ユーザー
        $thread5 = QaThread::where('title','SQLのINNER JOINが苦手です')->first();
        $replyCreatedAt2 = $thread5->created_at->copy()->addDays(1);
        QaReply::create([
            'user_id' => $graduatedStudents ->where('id', '!=', $thread3->user_id)->random()->id,
            'body' => '私も最初は問題文を見ても、どのテーブルを結合すればよいのか分からず苦戦していました。
                        まずは「取得したい情報はどのテーブルにあるのか」を整理してから、
                        共通するキー（idやuser_idなど）を探すようにしたところ、SQLを書きやすくなりました。
                        ER図を見ながら練習すると理解が深まると思います。',
            'qa_thread_id' => $thread5->id,
            'created_at' => $replyCreatedAt2,
            'updated_at' => $replyCreatedAt2,
        ]);

        //退会ユーザー
        $thread6 = QaThread::where('title','貸借対照表と損益計算書の違いが分かりません')->first();
        $replyCreatedAt5 = $thread6->created_at->copy()->addDays(6);
        QaReply::create([
            'user_id' => $withdrawnStudents->where('id', '!=', $thread4->user_id)->random()->id,
            'body' => '私も最初はB/SとP/Lの違いが分からず混乱していましたが、
                        「B/Sはある時点の会社の財産」「P/Lは一定期間の会社の成績」と覚えたら理解しやすくなりました。
                        仕訳を解いた後に、それぞれの勘定科目が最終的にどちらへ反映されるのかを確認すると、
                        自然と違いが身に付きました。',
            'qa_thread_id' => $thread6->id,
            'created_at' => $replyCreatedAt5,
            'updated_at' => $replyCreatedAt5,
        ]);

        //コーチ
        $thread7 = QaThread::where('title','IPアドレスとサブネットマスクの計算方法が分かりません')->first();
        $replyCreatedAt1 = $thread7->created_at->copy()->addDays(7);
        QaReply::create([
            'user_id' => $ITCoach->id,
            'body' => 'サブネットマスクは「ネットワーク部」と「ホスト部」を区別するためのものです。まずはIPアドレスとサブネットマスクを2進数に変換し、AND演算を行うことでネットワークアドレスを求められます。試験では「255.255.255.0」や「255.255.255.128」など、よく出るサブネットマスクを暗記しておくと計算がスムーズになります。',
            'qa_thread_id' => $thread7->id,
            'created_at' => $replyCreatedAt1,
            'updated_at' => $replyCreatedAt1,
        ]);

        $thread8 = QaThread::where('title','TOEICのリスニングが聞き取れません')->first();
        $replyCreatedAt4 = $thread8->created_at->copy()->addDays(3);
        QaReply::create([
            'user_id' => $businessCoach->id,
            'body' => '550点から700点を目指すのであれば、まずは「音を正確に聞き取る力」と「英語を英語のまま理解する力」を身に付けることが大切です。
                        おすすめの順番は、①スクリプトを確認して内容を理解する、②音声を聞きながらオーバーラッピングを行う、
                        ③シャドーイングを繰り返す、④最後にスクリプトを見ずに聞き直す、という流れです。
                        ディクテーションは苦手な部分だけに取り入れると効率的です。
                        また、Part3・4では設問と選択肢を先読みする習慣を付けることで、
                        聞くべきポイントを意識できるようになります。
                        聞き逃した問題は無理に追わず、次の問題へ気持ちを切り替えることも大切です。
                        毎日30分程度でも継続して取り組めば、リスニング力は着実に伸びます。
                        公式問題集を繰り返し活用し、同じ音声を何度も聞いて「聞こえなかった音」が
                        自然に聞き取れるようになるまで練習してみてください。',
            'qa_thread_id' => $thread8->id,
            'created_at' => $replyCreatedAt4,
            'updated_at' => $replyCreatedAt4,
        ]);

        $thread9 = QaThread::where('title','貸借対照表と損益計算書の違いが分かりません')->first();
        $replyCreatedAt5 = $thread9->created_at->copy()->addDays(5);
        QaReply::create([
            'user_id' => $businessCoach->id,
            'body' => '覚え方としては、「B/Sは会社の財産の写真」「P/Lは会社の成績表」とイメージすると分かりやすいです。
                        B/S（貸借対照表）は、決算日時点で会社がどれだけの資産・負債・純資産を持っているかを表します。
                        一方、P/L（損益計算書）は、一定期間でどれだけ利益や損失が出たかを表す書類です。
                        仕訳問題を解く際には、「この勘定科目は財産に関係するのか、
                        それとも収益・費用に関係するのか」を意識すると、どちらの財務諸表に反映されるか判断しやすくなります。
                        繰り返し仕訳と財務諸表を結び付けて学習すると理解が深まります。',
            'qa_thread_id' => $thread9->id,
            'created_at' => $replyCreatedAt5,
            'updated_at' => $replyCreatedAt5,
        ]);

        $thread10 = QaThread::where('title','WBSの作成手順がよく分かりません')->first();
        $replyCreatedAt13 = $thread10->created_at->copy()->addDays(6);
        QaReply::create([
            'user_id' => $businessCoach->id,
            'body' => 'WBSは「作業を分解する」のではなく、「成果物を分解する」と考えると理解しやすくなります。
                        粒度に正解はありませんが、担当者が明確になり、工数を見積もれて、進捗を管理できる単位まで分解するのが一般的です。
                        システム開発であれば、「要件定義」だけでは大きすぎるため、「画面設計」「API設計」「単体テスト」のように、
                        実際に担当者へ割り当てられるレベルまで細分化すると管理しやすくなります。
                        また、WBSを作成した後は、作業の前後関係を整理してネットワーク図を作成し
                        、その情報を基にスケジュール化したものがガントチャートです。
                        この流れをセットで理解すると、それぞれの役割がイメージしやすくなります。
                        試験対策としては過去問も重要ですが、理解を深めるためには、
                        小規模なシステムやイベントを題材に実際にWBSを作成してみることをおすすめします。
                        自分で作成と修正を繰り返すことで、100％ルールや適切な粒度も自然と身に付いてきます。',
            'qa_thread_id' => $thread10->id,
            'created_at' => $replyCreatedAt13,
            'updated_at' => $replyCreatedAt13,
        ]);

        $thread11 = QaThread::where('title','データベース設計問題の解き方が分かりません')->first();
        $replyCreatedAt8 = $thread11->created_at->copy()->addDays(3);
        QaReply::create([
            'user_id' => $ITCoach->id,
            'body' => 'データベースの午後問題では、設問より先にER図やテーブル構成を確認し、それぞれの役割を把握することが大切です。
                        正規化の問題では、「データの重複がないか」「1つのテーブルに情報を詰め込みすぎていないか」
                        という視点で考えると判断しやすくなります。
                        また、SQLが出題される問題では、SELECT文を実際に書きながら解説を読むことで理解が深まります。
                        過去問を繰り返し解き、頻出パターンに慣れることが合格への近道です。',
            'qa_thread_id' => $thread11->id,
            'created_at' => $replyCreatedAt8,
            'updated_at' => $replyCreatedAt8,
        ]);

        $thread12 = QaThread::where('title','情報セキュリティの午後問題が苦手です')->first();
        $replyCreatedAt10 = $thread12->created_at->copy()->addDays(1);
        QaReply::create([
            'user_id' => $ITCoach->id,
            'body' => '情報セキュリティの午後問題では、攻撃手法を暗記するだけでなく、「なぜその対策が有効なのか」を理解することが大切です。
                        問題を解く際は、最初に設問を確認して何を問われているのかを把握し、その後で本文を読むと必要な情報を見つけやすくなります。
                        また、過去問を繰り返し解き、標的型攻撃やSQLインジェクション、クロスサイトスクリプティングなどの頻出テーマを整理しておくと、本番でも落ち着いて対応できます。
                        解説を読む際は、正解だけでなく他の選択肢が誤りである理由まで確認すると理解が深まります。',
            'qa_thread_id' => $thread12->id,
            'created_at' => $replyCreatedAt10,
            'updated_at' => $replyCreatedAt10,
        ]);
           
        $thread13 = QaThread::where('title','仕訳問題で借方と貸方を間違えてしまいます')->first();
        $replyCreatedAt11 = $thread13->created_at->copy()->addDays(3);
         QaReply::create([
            'user_id' => $businessCoach->id,
            'body' => '借方・貸方を覚えるコツは、勘定科目を丸暗記するのではなく、「資産・負債・純資産・収益・費用」の5つに分類して考えることです。
                        例えば、資産が増えれば借方、資産が減れば貸方というように、勘定科目の性質を理解すると迷いにくくなります。
                        また、仕訳を書いた後に貸借が一致しているか確認する習慣を付けることも大切です。
                        間違えた問題は解き直しを行い、「なぜその仕訳になるのか」を説明できるようになるまで復習すると、
                        本番でも安定して解けるようになります。',
            'qa_thread_id' => $thread13->id,
            'created_at' => $replyCreatedAt11,
            'updated_at' => $replyCreatedAt11,
        ]);

        $thread14 = QaThread::where('title','スタックとキューの違いについて教えてください')->first();
        $replyCreatedAt12 = $thread14->created_at->copy()->addDays(5);
        QaReply::create([
            'user_id' => $ITCoach->id,
            'body' => 'LIFOとFIFOの意味を覚えるだけでなく、「実際にどんな場面で使われるか」をイメージすると理解しやすくなります。
                        スタック（LIFO）は「最後に入れたものを最初に取り出す」構造で、ブラウザの「戻る」機能や、関数の呼び出しなどで利用されています。
                        一方、キュー（FIFO）は「最初に入れたものを最初に取り出す」構造で、プリンターの印刷待ちや受付の順番待ちなどが代表例です。
                        試験では利用例とセットで問われることが多いため、「スタック＝戻る」「キュー＝順番待ち」と関連付けて覚えるのがおすすめです。
                        また、実際にデータが追加・削除される流れを紙に書きながら確認すると、問題でも迷いにくくなります。',
            'qa_thread_id' => $thread14->id,
            'created_at' => $replyCreatedAt12,
            'updated_at' => $replyCreatedAt12,
        ]);

        $thread15 = QaThread::where('title','クリティカルパスの求め方について教えてください')->first();
        $replyCreatedAt13 = $thread15->created_at->copy()->addDays(3);
        QaReply::create([
            'user_id' => $businessCoach->id,
            'body' => 'クリティカルパスの問題は、計算方法を暗記するだけではなく、「前から計算する」「後ろから計算する」という流れを意識すると間違えにくくなります。
                        まず、最早開始時刻（ES）は開始地点から順番に足し算をしながら求め、最遅開始時刻（LS）は終了地点から逆向きに引き算をしながら求めます。
                        複数の経路がある場合は、最早開始時刻は大きい値、最遅開始時刻は小さい値を採用することがポイントです。
                        試験対策では、ネットワーク図を自分で書きながら過去問を繰り返し解くことをおすすめします。
                        計算を省略せず、途中の数値を書き込む習慣を付けることでミスが減り、クリティカルパスも見つけやすくなります。',
            'qa_thread_id' => $thread15->id,
            'created_at' => $replyCreatedAt13,
            'updated_at' => $replyCreatedAt13,
        ]);

    }
}
