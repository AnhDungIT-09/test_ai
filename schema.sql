CREATE DATABASE zalo_chat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE zalo_chat;

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    sender ENUM('user', 'gpt', 'staff') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
