-- 初回起動時に実行される初期化スクリプト
-- データベース自体は docker-compose.yml の MYSQL_DATABASE 環境変数で作成されるため、
-- ここではアプリケーションに必要なテーブルのみ用意する。

-- クライアントの文字コード設定に関わらず、日本語のダミーデータが文字化けしないようにする。
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login_id VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 各メンバーの言語ごとの理解度(1〜10)
CREATE TABLE IF NOT EXISTS language_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    language VARCHAR(30) NOT NULL,
    level TINYINT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_language (user_id, language),
    CONSTRAINT fk_language_skills_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_language_skills_level CHECK (level BETWEEN 1 AND 10)
);

-- 各メンバーが持っているタスクと、その状態(1〜5)・質問の有無
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    status TINYINT NOT NULL DEFAULT 1,
    has_question TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_tasks_status CHECK (status BETWEEN 1 AND 5)
);

-- ここから動作確認用のダミーデータ。
-- ログインIDはすべてパスワード "password1234" です(README参照)。
-- 本番運用でこのデータが不要な場合は、以下を削除してください。

INSERT IGNORE INTO users (login_id, display_name, password_hash) VALUES
    ('yamada', '山田 太郎', '$2y$12$/pRWbdtn7S1SjahP.gmSz.cadmg29SQvhX8biXqGydEAVfQ.QAywm'),
    ('sato', '佐藤 花子', '$2y$12$J1MD5b11zBhhzrcpFOEl2uzRBhHyfIMjYejRHHKxEFz/eYhPH69LO'),
    ('suzuki', '鈴木 一郎', '$2y$12$jS1jCtg3pUWQvPDHqjtgUOlBcs46MQtrm/ZpK9Lg9pR48SoL0ieWC'),
    ('tanaka', '田中 美咲', '$2y$12$fLU5JyXk34683NTQy6hZ7.SOUErFEwqYCjqYLb9Muk1ZSXqO71UPu');

INSERT IGNORE INTO language_skills (user_id, language, level)
SELECT u.id, v.language, v.level
FROM users u
JOIN (
    SELECT 'yamada' AS login_id, 'html' AS language, 8 AS level UNION ALL
    SELECT 'yamada', 'css', 7 UNION ALL
    SELECT 'yamada', 'javascript', 6 UNION ALL
    SELECT 'yamada', 'mysql', 5 UNION ALL
    SELECT 'yamada', 'php', 4 UNION ALL
    SELECT 'sato', 'html', 9 UNION ALL
    SELECT 'sato', 'css', 8 UNION ALL
    SELECT 'sato', 'javascript', 9 UNION ALL
    SELECT 'sato', 'mysql', 7 UNION ALL
    SELECT 'sato', 'php', 6 UNION ALL
    SELECT 'suzuki', 'html', 5 UNION ALL
    SELECT 'suzuki', 'css', 4 UNION ALL
    SELECT 'suzuki', 'javascript', 3 UNION ALL
    SELECT 'suzuki', 'mysql', 6 UNION ALL
    SELECT 'suzuki', 'php', 8 UNION ALL
    SELECT 'tanaka', 'html', 3 UNION ALL
    SELECT 'tanaka', 'css', 3 UNION ALL
    SELECT 'tanaka', 'javascript', 2 UNION ALL
    SELECT 'tanaka', 'mysql', 2 UNION ALL
    SELECT 'tanaka', 'php', 1
) v ON v.login_id = u.login_id;

INSERT INTO tasks (user_id, title, status, has_question)
SELECT u.id, v.title, v.status, v.has_question
FROM users u
JOIN (
    SELECT 'yamada' AS login_id, 'ログイン機能の実装' AS title, 5 AS status, 1 AS has_question UNION ALL
    SELECT 'yamada', 'マイページのレイアウト調整', 2, 0 UNION ALL
    SELECT 'sato', 'Topページのメンバーカード表示', 3, 1 UNION ALL
    SELECT 'sato', 'DB接続まわりのリファクタ', 1, 0 UNION ALL
    SELECT 'suzuki', 'タスク一覧の設計', 4, 1 UNION ALL
    SELECT 'suzuki', 'Nginx設定の見直し', 2, 0 UNION ALL
    SELECT 'tanaka', 'PHP未経験のためオンボーディング中', 3, 1 UNION ALL
    SELECT 'tanaka', 'CSS設計の学習', 2, 0
) v ON v.login_id = u.login_id;
