#!/bin/bash
# Availability Integrity Check Script
# Run this regularly to verify the availability constraint is working

echo "==================================="
echo "AVAILABILITY INTEGRITY AUDIT"
echo "Date: $(date)"
echo "==================================="
echo ""

mysql -u root biblioteca << 'SQLEOF'
-- Check 1: Count inconsistencies
SELECT 'CHECK 1: Data Integrity' as check_name;
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN 'PASS: No inconsistencies found'
        ELSE 'FAIL: ' || COUNT(*) || ' inconsistencies found'
    END as result,
    COUNT(*) as count
FROM resources
WHERE available_copies > total_copies OR available_copies < 0;

-- Check 2: Verify constraints exist
SELECT 'CHECK 2: Constraint Protection' as check_name;
SELECT 
    CASE 
        WHEN COUNT(*) = 2 THEN 'PASS: Both constraints active'
        ELSE 'FAIL: Only ' || COUNT(*) || ' constraints found'
    END as result,
    COUNT(*) as count
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'resources' 
AND CONSTRAINT_TYPE = 'CHECK'
AND CONSTRAINT_NAME LIKE 'chk_available%';

-- Check 3: Sample of valid records
SELECT 'CHECK 3: Sample Records (first 5)' as check_name;
SELECT id, title, total_copies, available_copies, support_type
FROM resources
WHERE is_active = 1
LIMIT 5;

-- Check 4: Statistics
SELECT 'CHECK 4: System Statistics' as check_name;
SELECT 
    COUNT(*) as total_resources,
    SUM(CASE WHEN available_copies = 0 THEN 1 ELSE 0 END) as zero_copies,
    SUM(CASE WHEN available_copies > 0 AND available_copies <= total_copies THEN 1 ELSE 0 END) as valid_available,
    SUM(CASE WHEN total_copies = 0 THEN 1 ELSE 0 END) as no_total
FROM resources;

SQLEOF

echo ""
echo "=== AUDIT COMPLETE ==="
echo ""
echo "If all checks show PASS, the system is secure."
echo ""
