-- 初回起動時に実行される初期化スクリプト
-- データベース自体は docker-compose.yml の MYSQL_DATABASE 環境変数で作成されるため、
-- ここではアプリケーションに必要なテーブルのみ用意する。

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
