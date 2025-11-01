-- HOTFIX: add patient_email to queue_tokens if missing
ALTER TABLE queue_tokens
  ADD COLUMN IF NOT EXISTS patient_email VARCHAR(100) NULL AFTER patient_phone;
