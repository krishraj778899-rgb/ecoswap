CREATE DATABASE IF NOT EXISTS ecoswap
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ecoswap;

-- =========================================
-- USERS TABLE
-- =========================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- ITEMS TABLE
-- =========================================

CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL,
    item_condition VARCHAR(50) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('available', 'pending', 'swapped')
        DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- =========================================
-- SWAP REQUESTS TABLE
-- =========================================

CREATE TABLE IF NOT EXISTS swap_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,

    item_id INT NOT NULL,
    requester_id INT NOT NULL,
    owner_id INT NOT NULL,

    message TEXT,

    status ENUM(
        'pending',
        'accepted',
        'rejected',
        'cancelled'
    ) DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id)
        REFERENCES items(id)
        ON DELETE CASCADE,

    FOREIGN KEY (requester_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (owner_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- =========================================
-- INDEXES
-- =========================================

CREATE INDEX idx_items_category
ON items(category);

CREATE INDEX idx_items_status
ON items(status);

CREATE INDEX idx_swap_item
ON swap_requests(item_id);

CREATE INDEX idx_swap_requester
ON swap_requests(requester_id);

CREATE INDEX idx_swap_owner
ON swap_requests(owner_id);