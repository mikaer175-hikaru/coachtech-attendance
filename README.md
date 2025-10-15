# COACHTECH 勤怠管理アプリ

## 環境構築（修正版）

1. リポジトリをクローン後、環境ファイルを作成（ホスト側）
```bash
cp src/.env.example src/.env
```

2. Docker 環境用に .env を更新（Docker Compose の db サービスを使う場合）
- 必要に応じて以下を src/.env に設定してください：
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=attendance_db
DB_USERNAME=root
DB_PASSWORD=password
```

3. コンテナをビルドして起動
```bash
docker compose up -d --build
```

4. PHP 依存関係をコンテナ内でインストール
```bash
docker compose exec app composer install
```

5. アプリキーを生成
```bash
docker compose exec app php artisan key:generate
```

6. マイグレーションとシードを実行
```bash
docker compose exec app php artisan migrate --seed
```

7. フロントエンド依存（ホストで実行）
```bash
cd src
npm install
# 開発サーバ
npm run dev
# 本番ビルド
npm run build
```

8. テスト実行
```bash
docker compose exec app php artisan test
```
---

## 使用技術
- PHP 8.3.26 / Laravel 12.21.0
- MySQL 8.0
- Docker / Docker Compose
- nginx 1.29.1
- Mailhog

---

## 主要機能
- 一般ユーザー
  - 会員登録 / ログイン
  - 勤怠打刻（出勤・退勤・休憩）
  - 勤怠詳細確認・修正申請
- 管理者
  - 管理者ログイン
  - 勤怠一覧 / 詳細 / 承認機能
  - スタッフ一覧・月次勤怠確認

---

## 画面一覧
- `/register`：会員登録
- `/login`：ログイン
- `/attendance`：勤怠登録（打刻）
- `/attendance/{id}`：勤怠詳細
- `/stamp_correction_request/list`：申請一覧
- `/admin/login`：管理者ログイン
- `/admin/attendance/list`：勤怠一覧
- `/admin/attendance/staff/{id}`：スタッフ別勤怠一覧

---

## テーブル仕様（例）

### users
| カラム名 | 型 | 制約 |
| --- | --- | --- |
| id | bigint / unsigned bigInteger (PK) | PK |
| name | varchar(255) | not null |
| email | varchar(255) | unique, not null |
| email_verified_at | timestamp | nullable |
| password | varchar(255) | not null |
| remember_token | varchar(100) | nullable |
| created_at / updated_at | timestamp |  |
| is_first_login | boolean | not null |
| is_admin | boolean | not null |

### attendances
| カラム名 | 型 | 制約 |
| --- | --- | --- |
| id | bigint / unsigned bigInteger (PK) | PK |
| user_id | bigint / unsigned bigInteger | FK -> users.id, not null |
| work_date | date | not null |
| start_time | time / datetime | nullable |
| end_time | time / datetime | nullable |
| note | text | nullable |
| created_at / updated_at | timestamp |  |

---

## ER図
![ER図](er.png)

---

## テストアカウント
- 一般ユーザー
  - email: user@example.com
  - password: password123
- 管理者
  - email: admin@example.com
  - password: password123

---

## メール承認
- MailHog
  - http://localhost:8025

---

## テスト実行
```bash
docker compose exec app php artisan test
```
