-- Quick Fix: Ensure all required columns exist in queue_tokens table
USE qech_queue_system;

-- Check and add missing columns if they don't exist
SET @dbname = 'qech_queue_system';
SET @tablename = 'queue_tokens';

-- Add patient_age if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'patient_age');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE queue_tokens ADD COLUMN patient_age INT NULL AFTER patient_name', 
    'SELECT "patient_age already exists" as status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add patient_phone if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'patient_phone');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE queue_tokens ADD COLUMN patient_phone VARCHAR(20) NULL AFTER patient_age', 
    'SELECT "patient_phone already exists" as status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add patient_id_number if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'patient_id_number');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE queue_tokens ADD COLUMN patient_id_number VARCHAR(50) NULL AFTER patient_phone', 
    'SELECT "patient_id_number already exists" as status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add patient_address if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'patient_address');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE queue_tokens ADD COLUMN patient_address TEXT NULL AFTER patient_id_number', 
    'SELECT "patient_address already exists" as status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add service_type if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'service_type');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE queue_tokens ADD COLUMN service_type VARCHAR(100) NULL AFTER patient_address', 
    'SELECT "service_type already exists" as status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show final table structure
DESCRIBE queue_tokens;

SELECT 'Database fix completed! Check the table structure above.' as status;
