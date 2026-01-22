-- Test file: tests/database/003_test_admins.sql
-- Tests for 005_admins.sql migration

BEGIN;

SELECT plan(40);

-- =============================================================================
-- SETUP: Create test users
-- =============================================================================

INSERT INTO users (email) VALUES ('admin@example.com'), ('user@example.com'), ('granter@example.com');

-- =============================================================================
-- TEST ADMINS TABLE STRUCTURE
-- =============================================================================

-- Test 1-9: Table and columns exist
SELECT has_table('admins', 'Admins table should exist');
SELECT has_column('admins', 'id', 'Should have id column');
SELECT has_column('admins', 'uuid', 'Should have uuid column');
SELECT has_column('admins', 'user_uuid', 'Should have user_uuid column');
SELECT has_column('admins', 'granted_at', 'Should have granted_at column');
SELECT has_column('admins', 'granted_by_user_uuid', 'Should have granted_by_user_uuid column');
SELECT has_column('admins', 'granted_reason', 'Should have granted_reason column');
SELECT has_column('admins', 'revoked_at', 'Should have revoked_at column');
SELECT has_column('admins', 'revoked_by_user_uuid', 'Should have revoked_by_user_uuid column');

-- Test 10-11: More columns
SELECT has_column('admins', 'revoked_reason', 'Should have revoked_reason column');

-- Test 12-14: Column types
SELECT col_type_is('admins', 'uuid', 'uuid', 'UUID should be uuid type');
SELECT col_type_is('admins', 'user_uuid', 'uuid', 'User UUID should be uuid type');
SELECT col_type_is('admins', 'granted_at', 'timestamp with time zone', 'Granted at should be timestamptz');

-- Test 15-17: Primary key and constraints
SELECT has_pk('admins', 'Should have primary key');
SELECT col_not_null('admins', 'uuid', 'UUID should be NOT NULL');
SELECT col_not_null('admins', 'user_uuid', 'User UUID should be NOT NULL');

-- =============================================================================
-- TEST INDEXES
-- =============================================================================

-- Test 18-21: Indexes exist
SELECT has_index('admins', 'idx_admins_user_uuid', 'Should have user_uuid index');
SELECT has_index('admins', 'idx_admins_granted_at', 'Should have granted_at index');
SELECT has_index('admins', 'idx_admins_revoked_at', 'Should have revoked_at index');
SELECT has_index('admins', 'unique_active_admin_per_user', 'Should have unique active admin per user index');

-- =============================================================================
-- TEST FOREIGN KEYS
-- =============================================================================

-- Test 22: Foreign key to users exists
SELECT has_fk('admins', 'Should have foreign key constraints');

-- =============================================================================
-- TEST FUNCTIONS EXIST
-- =============================================================================

-- Test 23-28: Functions exist
SELECT has_function('is_user_admin', 'is_user_admin function should exist');
SELECT has_function('grant_admin_privileges', 'grant_admin_privileges function should exist');
SELECT has_function('revoke_admin_privileges', 'revoke_admin_privileges function should exist');
SELECT has_function('get_admin_by_user_uuid', 'get_admin_by_user_uuid function should exist');
SELECT has_function('list_active_admins', 'list_active_admins function should exist');
SELECT has_function('get_admin_statistics', 'get_admin_statistics function should exist');

-- Test 29: get_admin_audit_trail function exists
SELECT has_function('get_admin_audit_trail', 'get_admin_audit_trail function should exist');

-- =============================================================================
-- TEST FUNCTION BEHAVIOR
-- =============================================================================

-- Test 30: is_user_admin returns false for non-admin
SELECT is(
    is_user_admin((SELECT uuid FROM users WHERE email = 'user@example.com')),
    FALSE,
    'Non-admin user should not be admin'
);

-- Test 31: grant_admin_privileges grants admin
SELECT grant_admin_privileges(
    (SELECT uuid FROM users WHERE email = 'admin@example.com'),
    (SELECT uuid FROM users WHERE email = 'granter@example.com'),
    'System administrator'
);

SELECT is(
    is_user_admin((SELECT uuid FROM users WHERE email = 'admin@example.com')),
    TRUE,
    'User should be admin after grant'
);

-- Test 33: Cannot grant admin twice
SELECT throws_ok(
    format(
        'SELECT grant_admin_privileges(%L, NULL, NULL)',
        (SELECT uuid FROM users WHERE email = 'admin@example.com')
    ),
    'User % already has active admin privileges',
    'Should not be able to grant admin twice'
);

-- Test 34: get_admin_by_user_uuid returns admin record
SELECT is(
    (SELECT COUNT(*) FROM get_admin_by_user_uuid(
        (SELECT uuid FROM users WHERE email = 'admin@example.com')
    )),
    1::BIGINT,
    'Should find admin record for admin user'
);

-- Test 35: list_active_admins includes granted admin
SELECT is(
    (SELECT COUNT(*) FROM list_active_admins() WHERE user_email = 'admin@example.com'),
    1::BIGINT,
    'Active admins list should include admin'
);

-- Test 36: revoke_admin_privileges revokes admin
SELECT revoke_admin_privileges(
    (SELECT uuid FROM users WHERE email = 'admin@example.com'),
    (SELECT uuid FROM users WHERE email = 'granter@example.com'),
    'No longer needed'
);

SELECT is(
    is_user_admin((SELECT uuid FROM users WHERE email = 'admin@example.com')),
    FALSE,
    'User should not be admin after revocation'
);

-- Test 38: Revoked admin not in active list
SELECT is(
    (SELECT COUNT(*) FROM list_active_admins() WHERE user_email = 'admin@example.com'),
    0::BIGINT,
    'Revoked admin should not be in active list'
);

-- Test 39: get_admin_statistics shows correct counts
SELECT is(
    (SELECT revoked_admins FROM get_admin_statistics()),
    1::BIGINT,
    'Statistics should show 1 revoked admin'
);

-- Test 40: get_admin_audit_trail shows history
SELECT is(
    (SELECT COUNT(*) FROM get_admin_audit_trail(
        (SELECT uuid FROM users WHERE email = 'admin@example.com')
    )),
    1::BIGINT,
    'Audit trail should have entry'
);

SELECT * FROM finish();
ROLLBACK;
