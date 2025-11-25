CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) UNIQUE NOT NULL, -- mysite → mysite.yourdomain.com
  template_id INT DEFAULT 1,
  is_published TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Один блок-страница с конфигом JSON (герой, товары, контакты)
CREATE TABLE site_blocks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  site_id INT NOT NULL,
  block_type VARCHAR(50) NOT NULL, -- 'page'
  config JSON NOT NULL,
  FOREIGN KEY (site_id) REFERENCES sites(id)
);

CREATE TABLE analytics_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  site_id INT NOT NULL,
  event_type ENUM('view', 'click', 'lead') NOT NULL,
  event_data JSON,
  ip VARCHAR(45),
  user_agent VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (site_id) REFERENCES sites(id)
);
