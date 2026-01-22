-- Test file: tests/database/002_test_application_tokens.sql
-- Tests for 004_application_tokens.sql migration

BEGIN;

SELECT plan(42);

-- =============================================================================
-- TEST APPLICATION_TOKENS TABLE STRUCTURE
-- =============================================================================

-- Test 1-9: Table and columns exist
SELECT has_table('application_tokens', 'Application tokens table should exist');
SELECT has_column('application_tokens', 'id', 'Should have id column');
SELECT has_column('application_tokens', 'uuid', 'Should have uuid column');
SELECT has_column('application_tokens', 'token_hash', 'Should have token_hash column');
SELECT has_column('application_tokens', 'application_name', 'Should have application_name column');
SELECT has_column('application_tokens', 'owner_name', 'Should have owner_name column');
SELECT has_column('application_tokens', 'owner_email', 'Should have owner_email column');
SELECT has_column('application_tokens', 'jti', 'Should have jti column');
SELECT has_column('application_tokens', 'last_used_at', 'Should have last_used_at column');

-- Test 10-13: More columns
SELECT has_column('application_tokens', 'created_at', 'Should have created_at column');
SELECT has_column('application_tokens', 'revoked_at', 'Should have revoked_at column');
SELECT has_column('application_tokens', 'revoked_reason', 'Should have revoked_reason column');

-- Test 14-16: Column types
SELECT col_type_is('application_tokens', 'uuid', 'uuid', 'UUID should be uuid type');
SELECT col_type_is('application_tokens', 'token_hash', 'character varying(255)', 'Token hash should be varchar(255)');
SELECT col_type_is('application_tokens', 'jti', 'character varying(100)', 'JTI should be varchar(100)');

-- Test 17-19: Primary key and unique constraints
SELECT has_pk('application_tokens', 'Should have primary key');
SELECT col_not_null('application_tokens', 'uuid', 'UUID should be NOT NULL');
SELECT col_not_null('application_tokens', 'token_hash', 'Token hash should be NOT NULL');

-- =============================================================================
-- TEST INDEXES
-- =============================================================================

-- Test 20-25: Indexes exist
SELECT has_index('application_tokens', 'idx_application_tokens_application_name', 'Should have application_name index');
SELECT has_index('application_tokens', 'idx_application_tokens_owner_email', 'Should have owner_email index');
SELECT has_index('application_tokens', 'idx_application_tokens_jti', 'Should have jti index');
SELECT has_index('application_tokens', 'idx_application_tokens_created_at', 'Should have created_at index');
SELECT has_index('application_tokens', 'idx_application_tokens_revoked_at', 'Should have revoked_at index');
SELECT has_index('application_tokens', 'idx_application_tokens_active', 'Should have active tokens index');

-- =============================================================================
-- TEST FUNCTIONS EXIST
-- =============================================================================

-- Test 26-34: Functions exist
SELECT has_function('create_application_token', 'create_application_token function should exist');
SELECT has_function('is_application_token_valid', 'is_application_token_valid function should exist');
SELECT has_function('update_application_token_last_used', 'update_application_token_last_used function should exist');
SELECT has_function('revoke_application_token', 'revoke_application_token function should exist');
SELECT has_function('revoke_application_tokens_by_name', 'revoke_application_tokens_by_name function should exist');
SELECT has_function('revoke_application_tokens_by_owner', 'revoke_application_tokens_by_owner function should exist');
SELECT has_function('revoke_application_tokens_by_date', 'revoke_application_tokens_by_date function should exist');
SELECT has_function('get_application_token_statistics', 'get_application_token_statistics function should exist');
SELECT has_function('list_active_application_tokens', 'list_active_application_tokens function should exist');

-- =============================================================================
-- TEST FUNCTION BEHAVIOR
-- =============================================================================

-- Test 35: create_application_token creates token
SELECT is(
    (SELECT COUNT(*) FROM application_tokens WHERE application_name = 'Test App'),
    0::BIGINT,
    'Token should not exist before creation'
);

SELECT create_application_token(
    'hash123',
    'Test App',
    'John Doe',
    'john@example.com',
    'jti-123'
);

SELECT is(
    (SELECT COUNT(*) FROM application_tokens WHERE application_name = 'Test App'),
    1::BIGINT,
    'Token should exist after creation'
);

-- Test 37: is_application_token_valid returns true for active token
SELECT is(
    is_application_token_valid('jti-123'),
    TRUE,
    'Active token should be valid'
);

-- Test 38: is_application_token_valid returns false for non-existent token
SELECT is(
    is_application_token_valid('non-existent-jti'),
    FALSE,
    'Non-existent token should be invalid'
);

-- Test 39: revoke_application_token revokes token
SELECT revoke_application_token('jti-123', 'Testing revocation');

SELECT is(
    is_application_token_valid('jti-123'),
    FALSE,
    'Revoked token should be invalid'
);

-- Test 40: Verify revoked_reason is set
SELECT is(
    (SELECT revoked_reason FROM application_tokens WHERE jti = 'jti-123'),
    'Testing revocation',
    'Revocation reason should be set'
);

-- Test 41-42: Statistics function works
SELECT create_application_token('hash456', 'App 2', 'Jane Doe', 'jane@example.com', 'jti-456');

SELECT is(
    (SELECT active_tokens FROM get_application_token_statistics()),
    1::BIGINT,
    'Statistics should show 1 active token'
);

SELECT is(
    (SELECT revoked_tokens FROM get_application_token_statistics()),
    1::BIGINT,
    'Statistics should show 1 revoked token'
);

SELECT * FROM finish();
ROLLBACK;
