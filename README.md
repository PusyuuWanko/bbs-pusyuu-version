# bbs-pusyuu-version

bbs(プシューバージョン)では鮮麗されたデザインで、スレッドを選択したときその場での操作を実現、等を行っています。

userデータベースを作成し、
SQL文
`CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(64) NOT NULL
);

ALTER TABLE users ADD COLUMN username VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE users ADD account_created_time DATETIME;
`
を実行 