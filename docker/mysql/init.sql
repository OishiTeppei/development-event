-- 初回起動時に実行される初期化スクリプト
-- データベース自体は docker-compose.yml の MYSQL_DATABASE 環境変数で作成されるため、
-- ここでは動作確認用のテーブルとデータのみ用意する。

CREATE TABLE IF NOT EXISTS sample_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO sample_items (name) VALUES ('sample item 1'), ('sample item 2');
