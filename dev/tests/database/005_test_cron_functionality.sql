-- Test cron functionality

BEGIN;

SELECT plan(12);

-- Test table existence
SELECT has_table('cron', 'cron table should exist');
SELECT has_table('cron_schedule', 'cron_schedule table should exist');

-- Test cron function
SELECT has_function('cron_get_scheduled_jobs', ARRAY['character varying'], 'cron_get_scheduled_jobs function should exist');

-- Setup test data
INSERT INTO cron (code, expression, enabled) VALUES
    ('test_job_1', '0 */6 * * *', TRUE),
    ('test_job_2', '0 0 * * *', FALSE),
    ('test_job_3', '*/15 * * * *', TRUE);

-- Schedule some jobs
INSERT INTO cron_schedule (code, scheduled_at, status) VALUES
    ('test_job_1', NOW() - INTERVAL '1 hour', 'pending'),
    ('test_job_1', NOW() - INTERVAL '30 minutes', 'pending'),
    ('test_job_2', NOW() - INTERVAL '1 hour', 'pending'),
    ('test_job_3', NOW() + INTERVAL '1 hour', 'pending');

-- Test that enabled jobs are returned
SELECT ok(
    (SELECT COUNT(*) FROM cron_get_scheduled_jobs()) >= 1,
    'cron_get_scheduled_jobs should return enabled, due jobs'
);

-- Test that disabled jobs are cancelled
SELECT lives_ok(
    'SELECT * FROM cron_get_scheduled_jobs()',
    'cron_get_scheduled_jobs should handle disabled jobs'
);

-- Check that disabled job was cancelled
SELECT ok(
    (SELECT status FROM cron_schedule WHERE code = 'test_job_2' LIMIT 1) = 'cancelled',
    'Disabled job should be cancelled'
);

-- Test specific job filtering
SELECT ok(
    (SELECT COUNT(*) FROM cron_get_scheduled_jobs('test_job_1')) >= 1,
    'cron_get_scheduled_jobs should filter by specific job code'
);

-- Test that only one job per code is returned (oldest scheduled_at)
WITH job_counts AS (
    SELECT code, COUNT(*) as cnt
    FROM cron_get_scheduled_jobs()
    GROUP BY code
)
SELECT ok(
    (SELECT MAX(cnt) FROM job_counts) = 1,
    'Only one job per code should be returned'
);

-- Test that future jobs are not returned
SELECT ok(
    (SELECT COUNT(*) FROM cron_get_scheduled_jobs() WHERE code = 'test_job_3') = 0,
    'Future scheduled jobs should not be returned'
);

-- Test duplicate handling (multiple pending jobs for same code)
INSERT INTO cron_schedule (code, scheduled_at, status) VALUES
    ('test_job_1', NOW() - INTERVAL '45 minutes', 'pending');

-- Run the function and check omitted jobs
SELECT lives_ok(
    'SELECT * FROM cron_get_scheduled_jobs()',
    'Function should handle multiple pending jobs for same code'
);

-- Check that duplicate jobs are omitted
SELECT ok(
    (SELECT COUNT(*) FROM cron_schedule WHERE code = 'test_job_1' AND status = 'omitted') >= 1,
    'Duplicate jobs should be marked as omitted'
);

-- Test cron table constraints
SELECT has_pk('cron', 'cron table should have primary key');
SELECT has_pk('cron_schedule', 'cron_schedule table should have primary key');

-- Test foreign key relationship
SELECT fk_ok('cron_schedule', 'code', 'cron', 'code', 'cron_schedule should reference cron table');

SELECT * FROM finish();
ROLLBACK;