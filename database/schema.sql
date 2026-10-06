CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(80) NOT NULL DEFAULT 'Admin',
  department VARCHAR(100) NULL,
  note VARCHAR(255) NULL,
  photo_data LONGTEXT NULL,
  status ENUM('active','passive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_state (
  id TINYINT UNSIGNED PRIMARY KEY,
  state_json LONGTEXT NOT NULL,
  updated_by BIGINT UNSIGNED NULL,
  revision BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_state_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id VARCHAR(120) NULL,
  payload_json LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created(created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS file_registry (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(80) NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'general',
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  relative_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(120) NULL,
  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  drive_file_id VARCHAR(255) NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_file_request(request_no),
  CONSTRAINT fk_file_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V1 keeps the V26 frontend model in app_state for exact compatibility.
-- These normalized core tables are prepared for the next migration phase.
CREATE TABLE IF NOT EXISTS parties (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  party_type ENUM('customer','supplier','public','private','customs','freight','service') NOT NULL,
  party_key VARCHAR(80) NULL,
  name VARCHAR(220) NOT NULL,
  tax_no VARCHAR(80) NULL,
  contact_name VARCHAR(150) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(80) NULL,
  country VARCHAR(100) NULL,
  address TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_party_key(party_key), INDEX idx_party_type(party_type), INDEX idx_party_name(name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(80) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  party_id BIGINT UNSIGNED NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  status VARCHAR(80) NOT NULL DEFAULT 'open',
  deadline DATE NULL,
  payload_json LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_request_party FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  party_id BIGINT UNSIGNED NULL,
  currency CHAR(3) NOT NULL,
  txn_date DATE NOT NULL,
  reference_no VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  debit DECIMAL(18,2) NOT NULL DEFAULT 0,
  credit DECIMAL(18,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_txn_party FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS cash_accounts (
  id BIGINT UNSIGNED PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  account_type ENUM('Banka','Kasa') NOT NULL DEFAULT 'Banka',
  currency CHAR(3) NOT NULL,
  balance DECIMAL(18,2) NOT NULL DEFAULT 0,
  iban VARCHAR(120) NULL,
  swift VARCHAR(50) NULL,
  payload_json LONGTEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cash_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  txn_date DATE NOT NULL,
  account_name VARCHAR(190) NOT NULL,
  request_no VARCHAR(100) NULL,
  party_name VARCHAR(220) NULL,
  txn_type ENUM('Giriş','Çıkış') NOT NULL,
  currency CHAR(3) NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cash_date(txn_date), INDEX idx_cash_party(party_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V3.0 production hardening
ALTER TABLE file_registry ADD COLUMN IF NOT EXISTS request_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE file_registry ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER category;
ALTER TABLE file_registry ADD COLUMN IF NOT EXISTS version_no INT UNSIGNED NOT NULL DEFAULT 1 AFTER description;
ALTER TABLE file_registry ADD COLUMN IF NOT EXISTS extension VARCHAR(20) NULL AFTER mime_type;
ALTER TABLE file_registry ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER created_at;

CREATE TABLE IF NOT EXISTS finance_journal (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_uuid CHAR(36) NOT NULL,
  action_type VARCHAR(80) NOT NULL,
  request_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  party_name VARCHAR(220) NULL,
  cash_account_id BIGINT UNSIGNED NULL,
  currency CHAR(3) NOT NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  reversal_of BIGINT UNSIGNED NULL,
  payload_json LONGTEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fin_group(group_uuid), INDEX idx_fin_order(order_id), INDEX idx_fin_request(request_id),
  CONSTRAINT fk_fin_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entity_locks (
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(120) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  token CHAR(36) NOT NULL,
  expires_at DATETIME NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(entity_type,entity_id),
  INDEX idx_lock_expires(expires_at),
  CONSTRAINT fk_lock_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_id BIGINT UNSIGNED NOT NULL,
 source_item_id BIGINT UNSIGNED NOT NULL,
 item_name VARCHAR(255) NOT NULL,
 qty DECIMAL(18,4) NOT NULL DEFAULT 0,
 unit VARCHAR(40) NULL,
 brand_model VARCHAR(190) NULL,
 specification TEXT NULL,
 payload_json LONGTEXT NULL,
 UNIQUE KEY uq_request_source_item(request_id,source_item_id),
 INDEX idx_req_item_request(request_id),
 INDEX idx_req_item_source(source_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_quotes (
 id BIGINT UNSIGNED PRIMARY KEY,
 request_id BIGINT UNSIGNED NOT NULL,
 supplier_name VARCHAR(220) NOT NULL,
 reference_no VARCHAR(120) NULL,
 currency CHAR(3) NULL,
 total DECIMAL(18,2) NOT NULL DEFAULT 0,
 payload_json LONGTEXT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_sq_request(request_id), INDEX idx_sq_supplier(supplier_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_quotes (
 request_id BIGINT UNSIGNED PRIMARY KEY,
 quote_no VARCHAR(120) NULL,
 revision_no INT NOT NULL DEFAULT 0,
 status VARCHAR(80) NULL,
 currency CHAR(3) NULL,
 cost_total DECIMAL(18,2) NOT NULL DEFAULT 0,
 sales_total DECIMAL(18,2) NOT NULL DEFAULT 0,
 profit DECIMAL(18,2) NOT NULL DEFAULT 0,
 payload_json LONGTEXT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_orders (
 id BIGINT UNSIGNED PRIMARY KEY,
 request_id BIGINT UNSIGNED NOT NULL,
 order_no VARCHAR(120) NULL,
 customer_name VARCHAR(220) NULL,
 currency CHAR(3) NULL,
 total DECIMAL(18,2) NOT NULL DEFAULT 0,
 customer_paid DECIMAL(18,2) NOT NULL DEFAULT 0,
 customer_due DECIMAL(18,2) NOT NULL DEFAULT 0,
 status VARCHAR(80) NULL,
 payload_json LONGTEXT NULL,
 INDEX idx_so_request(request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_orders (
 id BIGINT UNSIGNED PRIMARY KEY,
 sales_order_id BIGINT UNSIGNED NOT NULL,
 po_no VARCHAR(120) NULL,
 supplier_name VARCHAR(220) NULL,
 status VARCHAR(80) NULL,
 total DECIMAL(18,2) NOT NULL DEFAULT 0,
 paid DECIMAL(18,2) NOT NULL DEFAULT 0,
 due DECIMAL(18,2) NOT NULL DEFAULT 0,
 payload_json LONGTEXT NULL,
 INDEX idx_po_order(sales_order_id), INDEX idx_po_supplier(supplier_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_state (
  module_key VARCHAR(80) PRIMARY KEY,
  payload_json LONGTEXT NOT NULL,
  revision BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_module_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS operation_idempotency (
  idempotency_key CHAR(64) PRIMARY KEY,
  action_type VARCHAR(80) NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_idem_action(action_type),
  CONSTRAINT fk_idem_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commercial_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  document_uid VARCHAR(100) NOT NULL,
  request_id BIGINT UNSIGNED NULL,
  document_type VARCHAR(40) NOT NULL,
  document_no VARCHAR(100) NOT NULL,
  revision_no INT NOT NULL DEFAULT 0,
  payload_json LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_commercial_doc_uid(document_uid),
  UNIQUE KEY uq_commercial_doc_no_rev(document_no,revision_no),
  INDEX idx_commercial_doc_request(request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_batches (
  id BIGINT UNSIGNED PRIMARY KEY,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  batch_no VARCHAR(140) NOT NULL,
  status VARCHAR(60) NOT NULL,
  planned_date DATE NULL,
  delivered_date DATE NULL,
  customer_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  customer_collected DECIMAL(18,2) NOT NULL DEFAULT 0,
  customer_due DECIMAL(18,2) NOT NULL DEFAULT 0,
  payload_json LONGTEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_delivery_batch_no(batch_no),
  INDEX idx_delivery_order(sales_order_id),
  CONSTRAINT fk_delivery_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_batch_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  request_item_id BIGINT UNSIGNED NULL,
  item_name VARCHAR(255) NOT NULL,
  qty DECIMAL(18,4) NOT NULL DEFAULT 0,
  unit VARCHAR(40) NULL,
  supplier_name VARCHAR(220) NULL,
  po_id BIGINT UNSIGNED NULL,
  sale_total DECIMAL(18,2) NOT NULL DEFAULT 0,
  cost_base DECIMAL(18,2) NOT NULL DEFAULT 0,
  payload_json LONGTEXT NULL,
  INDEX idx_batch_item_batch(batch_id),
  INDEX idx_batch_item_request(request_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS login_attempts (
  attempt_key CHAR(64) PRIMARY KEY,
  email_hash CHAR(64) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  failures INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_attempt DATETIME NOT NULL,
  INDEX idx_login_locked(locked_until),
  INDEX idx_login_last(last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(40) PRIMARY KEY,
  applied_at DATETIME NOT NULL,
  applied_by BIGINT UNSIGNED NULL,
  note VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
