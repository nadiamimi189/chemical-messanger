CREATE TABLE IF NOT EXISTS products (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT NOT NULL,
  name        VARCHAR(150) NOT NULL,
  description TEXT,
  media_path  VARCHAR(255) NOT NULL,
  media_type  ENUM('image','video') NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_admin
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_products_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;