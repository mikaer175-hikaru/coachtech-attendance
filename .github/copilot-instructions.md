<!--
This file provides concise, actionable guidance for AI coding agents working on the COACHTECH 勤怠管理アプリ repository.
Keep entries short and reference specific files for examples. Do not add generic advice unrelated to this repo.
-->

# Copilot / AI agent instructions — COACHTECH 勤怠管理アプリ

目的: このリポジトリは Laravel 10 / PHP 8.x を使った勤怠管理アプリです。AI エージェントは変更の影響範囲を最小化し、既存の命名規則・ロジック・ルートを尊重してください。

- プロジェクトの全体像
  - Laravel MVC アプリケーション。API ではなくサーバサイドレンダリング中心（views 配下）。エントリは `src/routes/web.php`。
  - コンテナ化：開発・テストは Docker Compose を使う。主要サービスは `app`(PHP/FPM)、`web`(nginx)、`db`(MySQL)。設定は `docker-compose.yml` と `docker/` 配下。
  - フロントは Vite + Tailwind。開発ビルドは `src/package.json` の `npm run dev`、本番ビルドは `npm run build`。

- 重要な開発ワークフロー（必ず再現するコマンド）
  - コンテナ立ち上げ: `docker compose up -d --build` （README に記載）
  - 依存インストール: `docker compose exec app composer install` と `npm install` をホストで実行
  - 環境ファイル作成: `cp src/.env.example src/.env`、`docker compose exec app php artisan key:generate`
  - マイグレーションとシード: `docker compose exec app php artisan migrate --seed`
  - テスト: `docker compose exec app php artisan test`

- リポジトリ固有の命名・パターン
  - 申請モデルはコードベースで混在した名前がある（`AttendanceCorrectRequest` と `StampCorrectionRequest`）。実装では実際に存在する `App\Models\StampCorrectionRequest` を使うケースがあり、テストやコントローラでは `AttendanceCorrectRequest` と別名で参照されていることがある。修正時はモデル定義（`app/Models`）とファクトリ（`database/factories`）の両方を確認して整合性を保つこと。
    - 参照例: コントローラ `src/app/Http/Controllers/StampCorrectionRequestController.php`、モデル `src/app/Models/StampCorrectionRequest.php`、ファクトリ `src/database/factories/AttendanceCorrectRequestFactory.php`。
  - ルート名は意図的に細かく命名されている（例: `attendance.start`, `stamp_requests.index`, 管理者は `admin.` プレフィックス）。ルートの変更はビューとテストに影響する。
  - ステータス定数を各モデルが持つ（例: `STATUS_PENDING`, `STATUS_APPROVED`）。ロジックはこれらの定数を前提にしている。

- 典型的な変更の注意点（影響範囲）
  - DB マイグレーション（`database/migrations`）に影響するモデル変更はシード・テストに広く影響するため、マイグレーションの更新または新規マイグレーションを併記すること。
  - トランザクションが使われている箇所（例: `StampCorrectionApprovalService::approve`, `StampCorrectionRequestController::store`）では、ロジックを単純に置き換えない。ロックや状態遷移（`status` フィールド）を保つ。
  - Route や view（`resources/views`）の命名やパスを変更する場合、`routes/web.php` と `tests/Feature` の両方を更新。

- テストとファクトリの利用
  - PHPUnit (phpunit 11) を使用。テスト用のファクトリは `database/factories`。既存のテストはコントローラや承認ロジックの例になる（例: `tests/Feature/StampRequests/*`）。PR ではまずテストを追加/修正すること。

- 典型的なファイルの参照例（速習用）
  - ルーティング: `src/routes/web.php`
  - 主要モデル: `src/app/Models/Attendance.php`, `src/app/Models/StampCorrectionRequest.php`, `src/app/Models/User.php`
  - サービス: `src/app/Services/StampCorrectionApprovalService.php`
  - コントローラ: `src/app/Http/Controllers/StampCorrectionRequestController.php`, `src/app/Http/Controllers/Admin/StampCorrectionApproveController.php`
  - ビルド / 開発: `src/package.json`, `src/composer.json`, `docker-compose.yml`
  - テスト: `phpunit.xml`, `tests/Feature/StampRequests` ディレクトリ

- 小さなガイドライン（実務ルール）
  - 変更は最小単位で（1 つの concern = 1 PR）。ビュー・ルート・モデル・マイグレーションのうち 1 つを変えるなら関連するテストを必ず更新。
  - 既存の status 定数やスコープ（`scopePending`, `scopeApproved` 等）を使うこと。文字列リテラルを複数箇所に散らさない。
  - トランザクションや DB ロック (`lockForUpdate`) を扱う箇所はレビューで重点的にチェック。

もしこの内容で不足・不明な点があれば、どのファイルやワークフローを詳述すべきか教えてください。
