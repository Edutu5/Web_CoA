CREATE DATABASE IF NOT EXISTS web_coa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_coa;

CREATE TABLE IF NOT EXISTS disaster_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO disaster_types (code, name) VALUES
    ('EQ', 'Cutremur'),
    ('FIRE', 'Incendiu'),
    ('FLOOD', 'Inundație')
ON DUPLICATE KEY UPDATE name = VALUES(name);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','authority','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    urgency ENUM('Immediate','Expected','Future','Past','Unknown') NOT NULL DEFAULT 'Immediate',
    status ENUM('active','resolved') NOT NULL DEFAULT 'active',
    edit_count INT NOT NULL DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES disaster_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shelters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    disaster_type_id INT,
    address VARCHAR(300),
    FOREIGN KEY (disaster_type_id) REFERENCES disaster_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    cap_identifier VARCHAR(100) NOT NULL UNIQUE,
    cap_xml TEXT NOT NULL,
    msg_type ENUM('Alert','Update','Cancel') NOT NULL DEFAULT 'Alert',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS earthquakes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country VARCHAR(100) DEFAULT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    magnitude DECIMAL(5,2) NOT NULL,
    depth DECIMAL(8,2) DEFAULT NULL,
    occurred_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela pentru stocarea rutelor de evacuare calculate automat

CREATE TABLE IF NOT EXISTS evacuation_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    shelter_id INT NOT NULL,
    distance_km DECIMAL(6,2),
    duration_min INT,
    geometry TEXT,
    steps_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (shelter_id) REFERENCES shelters(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;