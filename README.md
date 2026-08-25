# Certify LMS

マルチ資格対応の資格学習プラットフォームです。受講生は資格ごとの教材で学習し、演習問題・模擬試験で理解度を確かめながら、コーチの面談サポートを受けて資格取得を目指せます。

> プロジェクト構造・ドメインモデル・コードの読み進め方は [ONBOARDING.md](./ONBOARDING.md) を参照してください。

## 主な機能

| ロール | 機能 |
|---|---|
| 受講生（student） | 教材閲覧 / 演習問題・苦手分野ドリル / 模擬試験（分野別ヒートマップ・合格可能性スコア）/ 面談予約 / チャット / 学習時間・進捗・ストリーク管理 / 修了証の受領 |
| コーチ（coach） | 教材・演習問題・模試の管理 / 担当受講生の進捗フォロー / 面談対応・面談メモ / チャット |
| 管理者（admin） | ユーザー招待・管理 / 資格・資格分類マスタ管理 / 資格へのコーチ割当 / 面談回数の付与 / 全体ダッシュボード |

## 動作環境

- Docker Desktop / Docker Compose
- 開発環境は Laravel Sail で構築します（PHP コンテナ・MySQL・Mailpit・phpMyAdmin を起動）

## 環境構築手順

### 1. リポジトリの clone

```bash
git clone <このリポジトリの URL>
cd <リポジトリ名>
```

### 2. 環境変数ファイルの作成

```bash
cp .env.example .env
```

`.env.example` は Sail 向けに設定済みのため、コピーするだけでローカル開発を始められます（外部サービス連携のキーは後述）。

### 3. 依存パッケージのインストール（初回のみ）

`vendor/` がまだ無いため、初回のみ Docker 経由で Composer を実行します。

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Sail エイリアスの設定（推奨）

```bash
alias sail='./vendor/bin/sail'
```

以降のコマンドはこのエイリアス前提で記載します（未設定の場合は `./vendor/bin/sail` に読み替えてください）。

### 5. コンテナの起動

```bash
sail up -d
```

### 6. アプリケーションの初期化

```bash
sail artisan key:generate
sail artisan storage:link
sail artisan migrate:fresh --seed
```

`storage:link` は教材画像・プロフィール画像の配信に必要です。`migrate:fresh --seed` でテーブル作成とデモデータ投入が行われます（いつでも再実行してデータを初期状態に戻せます）。

### 7. フロントエンドのビルド

```bash
sail npm install
sail npm run build
```

Blade / CSS / JS を編集しながら開発する場合は、`build` の代わりに `sail npm run dev` を起動したままにしてください（Vite のホットリロードが効きます）。

### 8. 動作確認

http://localhost:8000 にアクセスし、下記の[ログインアカウント](#ログインアカウント)でログインできればセットアップ完了です。

## 開発環境 URL

| 用途 | URL |
|---|---|
| アプリケーション | http://localhost:8000 |
| phpMyAdmin（DB 確認） | http://localhost:8080 |
| Mailpit（メール確認） | http://localhost:8025 |

アプリケーションが送信するメール（招待メールなど）はすべて Mailpit に届きます。実際のメールは送信されません。

## ログインアカウント

`migrate:fresh --seed` 後、以下の固定アカウントが使えます（パスワードはすべて `password`）。

| ロール | メールアドレス | 備考 |
|---|---|---|
| 管理者 | admin@certify-lms.test | 全機能にアクセス可能 |
| コーチ | coach@certify-lms.test | IT 系資格の担当 |
| コーチ | coach2@certify-lms.test | ビジネス系資格の担当 |
| 受講生 | student@certify-lms.test | 受講中の資格・学習履歴・面談などのデモデータ付き |
| 修了生 | student-graduated@certify-lms.test | 修了生。チケットS-B-06の動作確認の際に使われる|
| 修了生2 | student-graduated@certify-lms.test | 同上 |

このほか、ライフサイクル（招待中 / 受講中 / 卒業 / 退会）を網羅したデモユーザーが投入されます。

> 本サービスは**招待制**です。公開の会員登録画面はありません。新規ユーザーを作るには、管理者でログイン → ユーザー管理から招待 → Mailpit で招待メールの URL を開く → オンボーディング登録、という流れになります。

## テスト

```bash
sail artisan test                  # 全テスト実行
sail artisan test --filter=Xxx    # クラス名・メソッド名で絞り込み
```

## コード整形

Laravel Pint を使用しています。コミット前に実行してください。

```bash
sail bin pint --dirty    # 変更ファイルのみ整形
sail bin pint --test     # 整形漏れの確認（CI 相当のチェック）
```

## 画像取得

```bash
sail artisan storage:link
```

## 使用技術

- PHP 8.5 / Laravel 10
- MySQL 8.4
- Laravel Fortify（認証）/ Laravel Sanctum（API 認証）
- Blade + Tailwind CSS + Vite（JavaScript は素の JS、フレームワーク不使用）
- PHPUnit / Laravel Pint
- league/commonmark（教材本文の Markdown レンダリング）
- Pusher（チャットのリアルタイム配信）
- Docker（Laravel Sail）

## 環境変数

`.env.example` をコピーするだけで、すべての機能がローカルで動作します（メールは Mailpit に配信されます）。

- `PUSHER_*` — チャットのリアルタイム配信に使用します。有効にする場合は Pusher のキーを取得して設定し、`BROADCAST_DRIVER=pusher` に変更してください。未設定（既定の `BROADCAST_DRIVER=log`）でもメッセージの送受信自体は動作し、相手画面へのリアルタイム反映のみ行われません

新しい環境変数やセットアップ手順を追加した場合は、`.env.example` と本 README に追記し、チームの誰でも環境を再現できる状態を保ってください。

※docs以下にそれぞれチケット番号のファイルがあり、中に詳細設計を記載しているので、そちらを参照してください。


## 通知機能

### 通知チャネル

以下の通知は、アプリ内通知とメールの両方で配信されます。

- お知らせ配信
- 面談リマインダー通知
  - 面談前日
  - 面談1時間前

---

### お知らせ配信

管理者がお知らせを配信すると、対象ユーザーに以下の通知が送信されます。

- アプリ内通知
- メール

---

### 面談リマインダー通知

予約済みの面談に対して、以下のタイミングで通知を送信します。

- 面談前日
- 面談1時間前

キャンセル済みの面談には通知を送信しません。

また、同一の面談・同一のタイミングのリマインダー通知は、複数回送信されません。

#### 手動実行

前日通知：

```bash
./vendor/bin/sail artisan notifications:send-meeting-reminders --window=eve
```

一時間前通知：

```bash
./vendor/bin/sail artisan notifications:send-meeting-reminders --window=one_hour_before
```

## 自動実行
Laravelのschedulerで自動実行されます

cronで1分ごとに実行してください

* * * * * cd /path/to/Certify-LMS && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1


##　メールの確認
環境開発ではMailpitを使用してメールを確認する

1.sailを起動する

```bash
./vendor/bin/sail up -d
```

2．ブラウザで以下にアクセスする

http://localhost:8025

3．メール確認

Mailpitを開き、対象ユーザーへのメールが届いていること、以下の事項をを確認します。

- メールが届いている
- メールの件名がお知らせのタイトルになっている
- メール本文にお知らせの内容が表示されている

4. 面談リマインダーメールを確認

以下のコマンドを実行してリマインダー通知を発火させます。

前日通知：

./vendor/bin/sail artisan notifications:send-meeting-reminders --window=eve

1時間前通知：

./vendor/bin/sail artisan notifications:send-meeting-reminders --window=one_hour_before

Mailpitを開き、対象の受講生にメールが届いていることを確認します

※注意事項※

- Mailpitは開発環境用のメール確認ツールです。

- 実際のメールアドレスにはメールを送信せず、Mailpit上で送信内容を確認します。

## Google Calendar API の設定

Google Calendar連携を使用する場合は、以下の環境設定が必要です。
（※Google Cloud Consoleで「Google Calendar API」を有効化する。）

- 1. Google Cloud側の設定

Google Cloud ConsoleでOAuth 2.0 Client IDを作成し、
`.env` に以下を設定してください。

```env
GOOGLE_CLIENT_ID=xxxxxxxx
GOOGLE_CLIENT_SECRET=xxxxxxxx
GOOGLE_REDIRECT_URI=http://localhost:8000/settings/google-calendar/callback
```

- Google Cloud ConsoleのOAuth設定では、以下のリダイレクトURIを登録してください。

```bash
http://localhost:8000/settings/google-calendar/callback
```

- .env を設定したあと、設定をクリアします。
```bash
./vendor/bin/sail artisan config:clear
```
- Google Calendarを連携する
ログイン後、calendarに連携してください

## Google Calendar連携の確認

### 1. 初期データ

Seeder実行後、

- coach@certify-lms.test：Google Calendar連携済み
- coach2@certify-lms.test：Google Calendar未連携

となる。

### 2. 実際のGoogle Calendar連携を確認する場合

Seederで作成されたGoogleCalendarTokenはダミー値のため、
実際のGoogle Calendar APIとの通信確認を行う場合は、
設定画面から一度Google Calendar連携を解除し、
再度GoogleアカウントとのOAuth認証を行う。

### 3. 確認項目

- 予約画面でGoogle Calendar上の予定が空き枠に反映される
- コーチのGoogle Calendar連携状態が表示される
- Google Calendar連携を解除できる
- 解除後、未連携状態になる
- 再度Google Calendarと連携できる

※ Seederで作成されるGoogleCalendarTokenは動作確認用のダミー値です。
そのため、Seeder直後は「連携済み」の状態を確認できますが、
Google Calendar APIとの実通信はできません。
実際のAPI通信を確認する場合は、ダミートークンを削除し、
設定画面からOAuth認証をやり直してください。

## AI