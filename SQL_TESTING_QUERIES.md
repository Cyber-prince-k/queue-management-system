# SQL Testing & Verification Queries

## Database Verification

### Check Table Structure
```sql
-- Verify queue_tokens has new columns
DESCRIBE queue_tokens;

-- Should show: patient_age, patient_address, service_type
```

### Check Sample Data
```sql
-- View recent patient registrations with all details
SELECT 
    token_number,
    patient_name,
    patient_age,
    patient_phone,
    service_type,
    priority_type,
    status,
    queue_position,
    created_at
FROM queue_tokens
ORDER BY created_at DESC
LIMIT 10;
```

## Queue Statistics

### Current Queue Status by Department
```sql
SELECT 
    d.name as department,
    COUNT(CASE WHEN qt.status = 'waiting' THEN 1 END) as waiting,
    COUNT(CASE WHEN qt.status = 'serving' THEN 1 END) as serving,
    COUNT(CASE WHEN qt.priority_type != 'no' THEN 1 END) as priority_cases,
    COUNT(*) as total
FROM departments d
LEFT JOIN queue_tokens qt ON d.id = qt.department_id 
    AND qt.status IN ('waiting', 'serving')
GROUP BY d.id, d.name;
```

### Patients Waiting by Priority
```sql
SELECT 
    priority_type,
    COUNT(*) as count,
    AVG(patient_age) as avg_age
FROM queue_tokens
WHERE status = 'waiting'
GROUP BY priority_type
ORDER BY 
    CASE priority_type
        WHEN 'emergency' THEN 1
        WHEN 'elderly' THEN 2
        WHEN 'pregnant' THEN 3
        WHEN 'disability' THEN 4
        WHEN 'no' THEN 5
    END;
```

## Action History

### Recent Staff Actions
```sql
SELECT 
    qh.action,
    qh.created_at,
    u.username as staff_member,
    qt.token_number,
    qt.patient_name,
    qh.notes
FROM queue_history qh
LEFT JOIN users u ON qh.performed_by = u.id
LEFT JOIN queue_tokens qt ON qh.token_id = qt.id
ORDER BY qh.created_at DESC
LIMIT 20;
```

### Queue Pause/Resume History
```sql
SELECT 
    qh.action,
    qh.created_at,
    u.username as staff_member,
    qh.notes
FROM queue_history qh
LEFT JOIN users u ON qh.performed_by = u.id
WHERE qh.action IN ('queue_paused', 'queue_resumed')
ORDER BY qh.created_at DESC;
```

### Patient Reassignment History
```sql
SELECT 
    qt.token_number,
    qt.patient_name,
    qh.created_at as reassigned_at,
    u.username as reassigned_by,
    qh.notes
FROM queue_history qh
JOIN queue_tokens qt ON qh.token_id = qt.id
LEFT JOIN users u ON qh.performed_by = u.id
WHERE qh.action = 'reassigned'
ORDER BY qh.created_at DESC;
```

## Performance Metrics

### Average Wait Time by Department
```sql
SELECT 
    d.name as department,
    AVG(TIMESTAMPDIFF(MINUTE, qt.created_at, qt.called_at)) as avg_wait_minutes,
    COUNT(*) as patients_served
FROM queue_tokens qt
JOIN departments d ON qt.department_id = d.id
WHERE qt.called_at IS NOT NULL
    AND DATE(qt.created_at) = CURDATE()
GROUP BY d.id, d.name;
```

### Service Completion Time
```sql
SELECT 
    d.name as department,
    AVG(TIMESTAMPDIFF(MINUTE, qt.called_at, qt.completed_at)) as avg_service_minutes,
    COUNT(*) as completed_today
FROM queue_tokens qt
JOIN departments d ON qt.department_id = d.id
WHERE qt.completed_at IS NOT NULL
    AND DATE(qt.completed_at) = CURDATE()
GROUP BY d.id, d.name;
```

### Daily Statistics
```sql
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_patients,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN priority_type != 'no' THEN 1 END) as priority_cases,
    AVG(patient_age) as avg_age
FROM queue_tokens
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

## Data Quality Checks

### Find Patients Without Age
```sql
SELECT 
    token_number,
    patient_name,
    created_at
FROM queue_tokens
WHERE patient_age IS NULL
    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);
```

### Find Elderly Patients Without Priority
```sql
SELECT 
    token_number,
    patient_name,
    patient_age,
    priority_type
FROM queue_tokens
WHERE patient_age >= 65
    AND priority_type = 'no'
    AND status IN ('waiting', 'serving');
```

### Check for Duplicate Tokens
```sql
SELECT 
    token_number,
    COUNT(*) as count
FROM queue_tokens
GROUP BY token_number
HAVING count > 1;
```

## Test Data Creation

### Create Test Patient
```sql
INSERT INTO queue_tokens (
    token_number,
    patient_name,
    patient_age,
    patient_phone,
    patient_id_number,
    patient_address,
    service_type,
    department_id,
    priority_type,
    queue_position,
    status
) VALUES (
    'TEST-20231014-9999',
    'Test Patient',
    45,
    '+265 999 000 000',
    'TEST123456',
    '123 Test Street, Blantyre',
    'General Consultation',
    (SELECT id FROM departments WHERE code = 'opd'),
    'no',
    999,
    'waiting'
);
```

### Create Test Priority Patient
```sql
INSERT INTO queue_tokens (
    token_number,
    patient_name,
    patient_age,
    patient_phone,
    patient_id_number,
    service_type,
    department_id,
    priority_type,
    queue_position,
    status
) VALUES (
    'TEST-20231014-9998',
    'Elderly Test Patient',
    72,
    '+265 888 000 000',
    'TEST789012',
    'Follow-up Consultation',
    (SELECT id FROM departments WHERE code = 'opd'),
    'elderly',
    998,
    'waiting'
);
```

## Cleanup Queries

### Delete Test Data
```sql
-- Delete test tokens
DELETE FROM queue_tokens 
WHERE token_number LIKE 'TEST-%';

-- Delete old completed tokens (older than 30 days)
DELETE FROM queue_tokens 
WHERE status = 'completed' 
    AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Reset Queue Positions
```sql
-- Recalculate queue positions for a department
SET @pos = 0;
UPDATE queue_tokens 
SET queue_position = (@pos := @pos + 1)
WHERE department_id = (SELECT id FROM departments WHERE code = 'opd')
    AND status = 'waiting'
ORDER BY priority_type DESC, created_at ASC;
```

## Reporting Queries

### Daily Summary Report
```sql
SELECT 
    d.name as Department,
    COUNT(CASE WHEN qt.status = 'waiting' THEN 1 END) as 'Currently Waiting',
    COUNT(CASE WHEN qt.status = 'serving' THEN 1 END) as 'Being Served',
    COUNT(CASE WHEN qt.status = 'completed' AND DATE(qt.completed_at) = CURDATE() THEN 1 END) as 'Completed Today',
    COUNT(CASE WHEN qt.priority_type != 'no' AND qt.status IN ('waiting', 'serving') THEN 1 END) as 'Priority Cases'
FROM departments d
LEFT JOIN queue_tokens qt ON d.id = qt.department_id
GROUP BY d.id, d.name
ORDER BY d.name;
```

### Service Type Analysis
```sql
SELECT 
    service_type,
    COUNT(*) as count,
    AVG(patient_age) as avg_age,
    COUNT(CASE WHEN priority_type != 'no' THEN 1 END) as priority_count
FROM queue_tokens
WHERE service_type IS NOT NULL
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY service_type
ORDER BY count DESC;
```

### Staff Performance Report
```sql
SELECT 
    u.username as staff_member,
    COUNT(CASE WHEN qh.action = 'called' THEN 1 END) as patients_called,
    COUNT(CASE WHEN qh.action = 'attended' THEN 1 END) as patients_attended,
    COUNT(CASE WHEN qh.action = 'reassigned' THEN 1 END) as patients_reassigned,
    DATE(qh.created_at) as date
FROM queue_history qh
JOIN users u ON qh.performed_by = u.id
WHERE u.role IN ('staff', 'admin')
    AND DATE(qh.created_at) = CURDATE()
GROUP BY u.id, u.username, DATE(qh.created_at)
ORDER BY patients_called DESC;
```

## Troubleshooting Queries

### Find Stuck Patients (serving for too long)
```sql
SELECT 
    token_number,
    patient_name,
    department_id,
    called_at,
    TIMESTAMPDIFF(MINUTE, called_at, NOW()) as minutes_serving
FROM queue_tokens
WHERE status = 'serving'
    AND called_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY called_at ASC;
```

### Find Orphaned Queue History
```sql
SELECT qh.*
FROM queue_history qh
LEFT JOIN queue_tokens qt ON qh.token_id = qt.id
WHERE qh.token_id IS NOT NULL
    AND qt.id IS NULL;
```

### Check Department Status
```sql
SELECT 
    code,
    name,
    is_active,
    CASE 
        WHEN is_active = 1 THEN 'Active'
        ELSE 'Paused'
    END as status
FROM departments;
```

## Backup & Restore

### Backup Today's Data
```sql
-- Export today's queue tokens
SELECT * FROM queue_tokens
WHERE DATE(created_at) = CURDATE()
INTO OUTFILE '/tmp/queue_backup_today.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

### Get Database Size
```sql
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM information_schema.TABLES
WHERE table_schema = 'qech_queue_system'
ORDER BY (data_length + index_length) DESC;
```

## Quick Reference

### Count Active Patients
```sql
SELECT COUNT(*) FROM queue_tokens WHERE status IN ('waiting', 'serving');
```

### Today's Registrations
```sql
SELECT COUNT(*) FROM queue_tokens WHERE DATE(created_at) = CURDATE();
```

### Average Age of Patients
```sql
SELECT AVG(patient_age) FROM queue_tokens WHERE patient_age IS NOT NULL;
```

### Most Common Service Types
```sql
SELECT service_type, COUNT(*) as count 
FROM queue_tokens 
WHERE service_type IS NOT NULL 
GROUP BY service_type 
ORDER BY count DESC 
LIMIT 5;
```

---

**Usage Tips:**
- Run these queries in phpMyAdmin SQL tab
- Adjust date ranges as needed
- Use LIMIT to prevent overwhelming results
- Export results to CSV for analysis
- Schedule regular backups
