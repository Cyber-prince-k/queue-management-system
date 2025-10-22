-- Migration: Add patient age, address, and service type fields
-- Run this if you already have an existing database

USE qech_queue_system;

-- Add new columns to queue_tokens table
ALTER TABLE queue_tokens 
ADD COLUMN IF NOT EXISTS patient_age INT AFTER patient_name,
ADD COLUMN IF NOT EXISTS patient_address TEXT AFTER patient_id_number,
ADD COLUMN IF NOT EXISTS service_type VARCHAR(100) AFTER patient_address;

-- Update queue_history to support NULL token_id for queue-level actions
ALTER TABLE queue_history 
MODIFY COLUMN token_id INT NULL;

-- Add new action types to queue_history
ALTER TABLE queue_history 
MODIFY COLUMN action ENUM('created', 'called', 'completed', 'cancelled', 'queue_paused', 'queue_resumed', 'reassigned', 'attended') NOT NULL;

-- Add created_at column alias for compatibility
ALTER TABLE queue_history 
CHANGE COLUMN action_time created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

SELECT 'Migration completed successfully!' as status;
