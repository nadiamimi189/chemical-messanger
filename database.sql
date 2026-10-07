-- =========================================================
-- Chemical Connect - Database Schema
-- A moderated social platform for a chemical industry
-- community (public member/Admin feed + private
-- per-user comment threads + private user uploads +
-- 1-to-1 chat with Admin).
-- =========================================================

CREATE DATABASE IF NOT EXISTS chemical_connect
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE chemical_connect;

-- ---------------------------------------------------------
-- Users (Admin + Members). Only one Admin is expected,
-- matching the "single Admin persona" in the UI reference.
-- ---------------------------------------------------------
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100)  NOT NULL,
  email       VARCHAR(150)  NOT NULL UNIQUE,
  password    VARCHAR(255)  NOT NULL,
  role        ENUM('admin','user') NOT NULL DEFAULT 'user',
  avatar      VARCHAR(255)  DEFAULT NULL,
  status      ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Public community posts created by Admin.
-- Visible in every member's feed.
-- ---------------------------------------------------------
CREATE TABLE admin_posts (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT NOT NULL,
  content     TEXT,
  media_path  VARCHAR(255) DEFAULT NULL,
  media_type  ENUM('image','video') DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_admin_posts_admin
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Private posts/photos/videos uploaded by a member.
-- Visible ONLY to that member and the Admin.
-- Other members never see this table's rows.
-- ---------------------------------------------------------
CREATE TABLE user_posts (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  content     TEXT,
  media_path  VARCHAR(255) DEFAULT NULL,
  media_type  ENUM('image','video') DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_posts_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Comments on an admin_post.
-- Every comment belongs to a private "thread" identified by
-- (post_id, owner_user_id) -- owner_user_id is the member who
-- owns that thread. author_id is whoever actually wrote the
-- comment: the member (author_id = owner_user_id) or the
-- Admin replying privately into that same thread.
-- A member only ever queries rows where owner_user_id = self,
-- so members can never see each other's comments -- only
-- their own conversation with Admin underneath the public post.
-- ---------------------------------------------------------
CREATE TABLE post_comments (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  post_id        INT NOT NULL,
  owner_user_id  INT NOT NULL,
  author_id      INT NOT NULL,
  comment        TEXT NOT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_post
    FOREIGN KEY (post_id) REFERENCES admin_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_owner
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_author
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_post_owner (post_id, owner_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Simple "like" toggle on public admin posts.
-- ---------------------------------------------------------
CREATE TABLE post_likes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  post_id     INT NOT NULL,
  user_id     INT NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES admin_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_like (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Private 1-to-1 direct messages between a member and Admin
-- (the "Chat with Admin" widget). A reply from Admin is only
-- ever delivered to the one member it was sent to.
-- ---------------------------------------------------------
CREATE TABLE messages (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  sender_id    INT NOT NULL,
  receiver_id  INT NOT NULL,
  message      TEXT NOT NULL,
  is_read      TINYINT(1) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_conversation (sender_id, receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Seed data
-- =========================================================

-- Default Admin account
--   email:    admin@chemicalconnect.com
--   password: admin123
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@chemicalconnect.com', '$2y$10$X2Nyd/vrCBc3ld/bdxdJVO8RMh3QHmo/fV4vkfxK53aNCUs6eOfea', 'admin');

-- Optional demo member account
--   email:    demo@chemicalconnect.com
--   password: user1234
INSERT INTO users (name, email, password, role) VALUES
('Demo Member', 'demo@chemicalconnect.com', '$2y$10$sF6XOdObUYbDarZHFdSBI.kG9Tw1bRma12vJzHEk3BgHppFXiU.DC', 'user');

-- Sample welcome post from Admin
INSERT INTO admin_posts (admin_id, content) VALUES
(1, 'Welcome to Chemical Connect! Please follow all safety guidelines and company policy. Feel free to comment below if you have any questions -- only you and the Admin team will see your comment.');
