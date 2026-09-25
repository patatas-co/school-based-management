ALTER TABLE roles
  ADD COLUMN status ENUM('enabled', 'disabled') NOT NULL DEFAULT 'enabled' AFTER is_system;
