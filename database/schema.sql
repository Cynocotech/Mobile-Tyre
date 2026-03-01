-- Mobile Tyre Database Schema (SQLite / MySQL compatible)
-- Run: sqlite3 database/mobile_tyre.sqlite < database/schema.sql

-- Jobs (replaces jobs.json + customers.csv)
CREATE TABLE IF NOT EXISTS jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  reference VARCHAR(12) NOT NULL UNIQUE,
  session_id VARCHAR(64),
  date VARCHAR(32),
  email VARCHAR(255),
  name VARCHAR(255),
  phone VARCHAR(50),
  postcode VARCHAR(32),
  lat VARCHAR(32),
  lng VARCHAR(32),
  vrm VARCHAR(32),
  make VARCHAR(128),
  model VARCHAR(128),
  colour VARCHAR(64),
  year VARCHAR(16),
  fuel VARCHAR(32),
  tyre_size VARCHAR(64),
  wheels VARCHAR(16),
  vehicle_desc TEXT,
  estimate_total VARCHAR(32),
  amount_paid VARCHAR(32),
  currency VARCHAR(8),
  payment_status VARCHAR(32),
  assigned_driver_id VARCHAR(64),
  assigned_at DATETIME,
  payment_method VARCHAR(16),
  cash_paid_at DATETIME,
  cash_paid_by VARCHAR(64),
  proof_url VARCHAR(255),
  proof_uploaded_at DATETIME,
  job_started_at DATETIME,
  job_completed_at DATETIME,
  driver_lat VARCHAR(32),
  driver_lng VARCHAR(32),
  driver_location_updated_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_jobs_reference ON jobs(reference);
CREATE INDEX IF NOT EXISTS idx_jobs_session ON jobs(session_id);
CREATE INDEX IF NOT EXISTS idx_jobs_assigned ON jobs(assigned_driver_id);
CREATE INDEX IF NOT EXISTS idx_jobs_date ON jobs(date);

-- Drivers (replaces database/drivers.json + admin/data/drivers.json)
CREATE TABLE IF NOT EXISTS drivers (
  id VARCHAR(64) PRIMARY KEY,
  email VARCHAR(255),
  password_hash VARCHAR(255),
  pin_hash VARCHAR(255),
  name VARCHAR(255),
  phone VARCHAR(50),
  van_make VARCHAR(128),
  van_reg VARCHAR(32),
  stripe_account_id VARCHAR(64),
  stripe_onboarding_complete INTEGER DEFAULT 0,
  is_online INTEGER DEFAULT 0,
  driver_lat VARCHAR(32),
  driver_lng VARCHAR(32),
  driver_location_updated_at DATETIME,
  referral_code VARCHAR(16),
  referred_by_driver_id VARCHAR(64),
  source VARCHAR(16) DEFAULT 'connect',
  blacklisted INTEGER DEFAULT 0,
  blocked_reason TEXT,
  kyc TEXT,
  equipment TEXT,
  vehicle_data TEXT,
  notes TEXT,
  driver_rate INTEGER DEFAULT 80,
  insurance_url VARCHAR(255),
  insurance_uploaded_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_drivers_email ON drivers(email);
CREATE INDEX IF NOT EXISTS idx_drivers_referral ON drivers(referral_code);

-- Quotes
CREATE TABLE IF NOT EXISTS quotes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  data TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Driver messages (replaces driver_messages.json)
CREATE TABLE IF NOT EXISTS driver_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  driver_id VARCHAR(64) NOT NULL,
  message TEXT,
  msg_from VARCHAR(32) DEFAULT 'admin',
  read_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_dm_driver ON driver_messages(driver_id);

-- Admin settings (replaces admin/config.json)
CREATE TABLE IF NOT EXISTS admin_settings (
  id VARCHAR(64) PRIMARY KEY,
  value TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Services (replaces admin/data/services.json)
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

-- Products (replaces database/products.json)
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

-- Site config (replaces dynamic.json for admin-editable keys)
CREATE TABLE IF NOT EXISTS site_config (
  id VARCHAR(64) PRIMARY KEY,
  value TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
