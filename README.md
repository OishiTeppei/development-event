# READMEしかない状態からの最小起動手順

このリポジトリは、PHP, Nginx, MySQL, Webpack の設定がそろって初めて動きます。README しかない新規リポジトリでは、まず「起動に必要な最小ファイル」を作ってから、依存関係を入れて Docker を立ち上げます。チームで2人目以降に参加する人は、ファイルを作る代わりに git clone するだけで済みます。

パスワードなどの秘密情報は .env に切り出し、`.gitignore` で除外することで、`git add / commit / push` してプルリクエストを作成してもGitHub上に平文のパスワードが公開されない構成にしています。

## 1. 前提条件

- Docker Desktop がインストールされ、**起動していること**（インストールされているだけでは docker compose コマンドは失敗します）
- Node.js（18以上推奨）と npm
- Git
- （ローカルで composer コマンドを直接使いたい場合のみ）PHP 8.1 以上と Composer。Docker完結で進める場合は必須ではありません

## 2. リポジトリを用意する

- **すでにチームのリポジトリがある場合**: git clone <リポジトリのURL> して、できたフォルダに移動する。以降の「3. まず作るファイル」はスキップして「6. 依存関係の入れ方」に進んでよい。
- **まだ何もない新規リポジトリの場合**: 以下の「3. まず作るファイル」から順に作成する。

## 3. まず作るファイル

最低限、次のファイルとディレクトリを用意します。

- package.json
- webpack.config.js
- `postcss.config.js`（`.scss` をビルドするために postcss-loader が必要とする設定）
- `babel.config.json`（`.js` をビルドするために babel-loader が必要とする設定）
- docker-compose.yml
- .env.example
- `.env`（`.gitignore` で除外、コミットしない）
- .gitignore
- src/composer.json
- src/index.php
- src/dbconnect.php
- src/index.html
- docker/php/Dockerfile
- docker/php/php.ini
- docker/nginx/Dockerfile
- docker/nginx/default.conf
- docker/mysql/init.sql
- src/styles/common.scss
- src/scripts/common.js
- src/assets/ 配下の画像やフォント

ディレクトリだけ先に作るなら、次の構成にします。

- src/
- src/styles/
- src/scripts/
- src/assets/
- src/assets/fonts/
- src/assets/img/
- docker/
- docker/php/
- docker/nginx/
- docker/mysql/

## 4. ファイルごとの役割

- package.json
  - Node の依存関係とビルドコマンドを定義します。
  - `webpack`, `sass`, postcss 系が必要です。

- webpack.config.js
  - src/scripts/common.js を読み込んで assets/scripts/main.js に、`src/styles/common.scss` を読み込んで assets/styles/common.css に出力します。
  - src/index.html はこの assets/ 配下のビルド成果物を読み込んで表示します。

- postcss.config.js / babel.config.json
  - Webpack の postcss-loader / babel-loader が動くために必要な設定ファイルです。これがないとビルドがエラーになります。

- docker-compose.yml
  - PHP, Nginx, MySQL, phpMyAdmin, MailHog をまとめて起動します。
  - ここがないと、複数コンテナ前提の環境は再現できません。
  - DB のパスワードなどは直書きせず、`.env` の値を ${MYSQL_ROOT_PASSWORD} のように変数参照します。
  - php サービスには DB_HOST / DB_NAME / DB_USER / DB_PASSWORD を環境変数として渡し、`src/dbconnect.php` から読み取れるようにします。
  - nginx サービスは ./src:/var/www/html の1本のマウントだけで index.php と index.html の両方をコンテナに渡します。以前は ./index.html:/var/www/html/index.html を別マウントとして重ねていましたが、`/var/www/html` という同じ場所に2つの別々のホスト実体（`src/` フォルダと単独の index.html ファイル）を重ねてマウントする形になっており、Windows の Docker Desktop 上で意図しない空の src/index.html がホスト側に自動生成される不具合がありました。`index.html` を src/ の中に置き、マウントを1本にまとめることで解消しています。

- .env.example
  - docker-compose.yml が参照する環境変数のテンプレートです。値は空、または機密でないデフォルト値のみを入れてコミットします。

- .env
  - .env.example をコピーして作る、実際のパスワードを入れるファイルです。
  - .gitignore で除外し、GitHub には絶対にコミットしません。
  - **チームでの運用**: .env はローカルのDockerコンテナ内でしか使われないため、パスワードの値自体はチームで揃える必要はなく、各自が .env.example をコピーして自由に決めて構いません。ただし MYSQL_DATABASE や DB_HOST のようにアプリケーションの前提になっているキー・値（`.env.example` のデフォルト）は変更しないでください。

- .gitignore
  - `node_modules/`, `vendor/`, ビルド成果物（`assets/scripts/`, `assets/styles/`）に加えて .env を除外します。

- src/composer.json
  - PHP 側の名前空間設定を置きます。
  - 現時点では require するパッケージがなく最小限の内容です。将来 PHP のパッケージ（ロガーやテストツールなど）を追加したときにすぐ使える状態にしておく位置づけで、今すぐ何かを解決するファイルではありません。

- docker/php/Dockerfile
  - pdo_mysql など、PHP から MySQL に接続するための拡張を入れます。

- docker/nginx/default.conf
  - ブラウザから来たアクセスを src/index.php または src/index.html に振り分けます。

- docker/mysql/init.sql
  - 初回起動時の DB 作成や初期データ投入を行います。

- src/index.php
  - ブラウザで最初に見る PHP の入口です。
  - DB 接続確認をここに置くと、環境構築の成否をすぐ見られます。

- src/dbconnect.php
  - MySQL 接続テスト用です。
  - 接続情報はハードコードせず、`getenv('DB_HOST')` / getenv('DB_NAME') / getenv('DB_USER') / getenv('DB_PASSWORD') で docker-compose.yml 経由の環境変数から読み取ります。

- src/index.html
  - 静的ページの入口です。
  - このリポジトリではトップページとして使われています。

## 5. 最小の作成順

1. 空のリポジトリに移動する。
2. 上のディレクトリを先に作る。
3. `package.json`, `webpack.config.js`, `postcss.config.js`, `babel.config.json`, `docker-compose.yml`, `.env.example`, `.gitignore`, src/composer.json を作る。
4. `docker/php/Dockerfile`, `docker/nginx/Dockerfile`, `docker/nginx/default.conf`, docker/mysql/init.sql を作る。
5. `src/index.php`, `src/dbconnect.php`, `src/index.html`, `src/styles/common.scss`, src/scripts/common.js を作る。
6. 画像やフォントなどの静的ファイルを src/assets/ に置く。
7. .env.example をコピーして .env を作り、パスワードなど実際の値を入れる。

## 6. 依存関係の入れ方

Docker Desktop を起動する:

1. タスクバーや Dockerアイコンで Docker Desktop が起動済みか確認する（起動していない場合は先に起動し、鯨アイコンが安定するまで待つ）。

環境変数:

1. .env をコピーして作る。
   - PowerShell / Git Bash / macOS・Linux: cp .env.example .env
   - Windows の `cmd.exe`（`cp` コマンドが存在しない）: copy .env.example .env
2. .env の MYSQL_ROOT_PASSWORD / MYSQL_USER / MYSQL_PASSWORD に実際の値を入れる。

Node 側:

1. npm install
2. npm run build でビルド確認（`npm run dev` は監視モードのため終了しません。継続的に編集しながら確認したいときに使います）

PHP 側:

1. `composer install`（現状パッケージは空ですが、`composer` コマンド自体が動くことの確認になります）

Docker 側:

1. docker compose up -d --build

## 7. 起動確認

### サービスのアクセスURL

| サービス | URL | 用途 |
|---|---|---|
| Webアプリ（Nginx） | http://localhost:8080/ | アプリ本体 |
| phpMyAdmin | http://localhost:8081/ | DBの中身を直接確認したい時 |
| MailHog | http://localhost:8025/ | 送信メールの確認画面（現状メール送信機能は未実装） |

### アプリ内の各画面URL

| 画面 | URL |
|---|---|
| 新規登録 | http://localhost:8080/register.php |
| ログイン | http://localhost:8080/login.php |
| ログアウト | http://localhost:8080/logout.php |
| Topページ（要ログイン） | http://localhost:8080/index.php （`http://localhost:8080/` でも同じ） |
| マイページ（要ログイン） | http://localhost:8080/mypage.php |

未ログインの状態で index.php や mypage.php にアクセスすると、自動的に login.php にリダイレクトされます。まずは register.php から新規登録してください。

### ダミーアカウントですぐ試す

`docker/mysql/init.sql` には、初回起動時に自動投入される動作確認用のダミーユーザー4名（言語理解度・タスク付き）が含まれています。新規登録をせず、以下のアカウントでそのままログインして画面を確認できます。

| ログインID | パスワード | 表示名 |
|---|---|---|
| yamada | password1234 | 山田 太郎 |
| sato | password1234 | 佐藤 花子 |
| suzuki | password1234 | 鈴木 一郎 |
| tanaka | password1234 | 田中 美咲 |

このダミーデータはローカル動作確認専用です。本番運用では `docker/mysql/init.sql` のダミーデータ部分（コメント「ここから動作確認用のダミーデータ」以降）を削除してください。

- `src/dbconnect.php`（`http://localhost:8080/index.php`）で DB 接続エラーが出ないか確認する。
  - 初回起動直後は MySQL の初期化に十数秒〜数十秒かかり、この間は Connection refused と表示されることがあります。故障ではないので、少し待ってから再読み込みしてください。`docker compose logs db` を実行し、ログに ready for connections と出ていれば準備完了のサインです。

## 8. つまずきやすい点

- Docker Desktop が起動していないと docker compose up はエラーで止まります。まず Docker Desktop 自体が起動しているか確認してください。
- MySQL のパスワードは .env の値を `docker-compose.yml`（`db` / phpmyadmin / php サービス）が共通で参照するため、`.env` の値さえ揃っていれば各ファイルで手動一致させる必要はありません。
- .env を編集しても、`php` コンテナに変更を反映するには docker compose up -d でコンテナを再作成する必要があります（イメージの再ビルドは不要）。
- 既に MySQL ボリュームがある場合、`.env` の値を変えても初期パスワードは自動更新されません（ボリュームを作り直す必要があります。「9. 停止・後片付け・ポート競合時の対処」参照）。
- docker compose up -d --build の前に、`docker` 配下の設定ファイルと .env がそろっているか確認します。
- .env は絶対にコミットしないでください。誤ってコミットした場合は .gitignore に追加するだけでなく、パスワード自体を変更してください。
- Nginx コンテナに渡すホストファイルは、同じマウント先（`/var/www/html`）に対して複数のマウント元を重ねないようにします。重ねると、環境によっては意図しない空ファイルが生成されるなど不安定な挙動になります（`src/index.html` はこの理由で src/ フォルダの中に置いています）。
- npm run dev を実行したままにするとターミナルが監視モードで専有されます。ビルド結果だけ確認したい場合は npm run build を使ってください。

## 9. 停止・後片付け・ポート競合時の対処

コンテナを止める（データは残る）:

docker compose stop

コンテナを削除する（DBのデータ用ボリュームは残る）:

docker compose down

DBのデータも含めて完全にリセットする:

docker compose down -v

ポートが競合する場合（`8080` / 8081 / 8025 / 1025 / 3306 を使用します。特に 3306 はローカルにMySQLを直接インストールしている環境と衝突しやすいです）:

- docker-compose.yml の ports: の左側（ホスト側のポート番号）だけを変更します。例えば db サービスの "3306:3306" を "3307:3306" に変えれば、ホスト側は `3307`番でアクセスするようになります。コンテナ内部同士の通信（`php` → db など）には影響しません。

## 10. いちばん小さい動作確認

もし本当に最小だけで動かすなら、次の順に絞れます。

1. src/index.html
2. src/index.php
3. src/dbconnect.php
4. docker-compose.yml
5. `.env`（`.env.example` からコピー）
6. docker/nginx/default.conf
7. docker/php/Dockerfile
8. docker/mysql/init.sql

この8点がそろえば、PHP と MySQL を含む最低限の動作確認ができます。
