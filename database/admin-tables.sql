-- Admin tables for SQLite – run: sqlite3 database/mobile_tyre.sqlite < database/admin-tables.sql

CREATE TABLE IF NOT EXISTS admin_settings (
  id VARCHAR(64) PRIMARY KEY,
  value TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
  id VARCHAR(64) PRIMARY KEY,
  service_key VARCHAR(64),
  label VARCHAR(255),
  price REAL DEFAULT 0,
  description TEXT,
  enabled INTEGER DEFAULT 1,
  seo TEXT,
  icon VARCHAR(32) DEFAULT 'wrench',
  sort_order INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id VARCHAR(64) PRIMARY KEY,
  sku VARCHAR(64),
  name VARCHAR(255),
  description TEXT,
  price REAL DEFAULT 0,
  category VARCHAR(32) DEFAULT 'Other',
  stock INTEGER DEFAULT 0,
  image_url VARCHAR(512),
  status VARCHAR(16) DEFAULT 'inactive',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_config (
  id VARCHAR(64) PRIMARY KEY,
  value TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
