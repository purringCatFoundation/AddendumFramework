-- Test file: tests/database/001_test_users_table.sql
-- Tests for 001_init.sql migration (users, user_passwords, token_revocations)

BEGIN;

SELECT plan(35);

-- =============================================================================
-- TEST USERS TABLE
-- =============================================================================

-- Test 1-4: Users table structure
SELECT has_table('users', 'Users table should exist');
SELECT has_column('users', 'id', 'Users table should have id column');
SELECT has_column('users', 'uuid', 'Users table should have uuid column');
SELECT has_column('users', 'email', 'Users table should have email column');

-- Test 5-8: Users column types
SELECT col_type_is('users', 'id', 'integer', 'Users id should be integer');
SELECT col_type_is('users', 'uuid', 'uuid', 'Users uuid should be uuid');
SELECT col_type_is('users', 'email', 'character varying', 'Users email should be varchar');

-- Test 9-11: Users constraints
SELECT has_pk('users', 'Users should have primary key');
SELECT col_not_null('users', 'uuid', 'Users uuid should be NOT NULL');
SELECT col_not_null('users', 'email', 'Users email should be NOT NULL');

-- Test 12-13: Users unique constraints
SELECT has_index('users', 'users_uuid_key', 'Users should have unique uuid index');
SELECT has_index('users', 'users_email_key', 'Users should have unique email index');

-- =============================================================================
-- TEST USER_PASSWORDS TABLE
-- =============================================================================

-- Test 14-17: User passwords table structure
SELECT has_table('user_passwords', 'User passwords table should exist');
SELECT has_column('user_passwords', 'id', 'User passwords should have id column');
SELECT has_column('user_passwords', 'user_id', 'User passwords should have user_id column');
SELECT has_column('user_passwords', 'password', 'User passwords should have password column');

-- Test 18-19: User passwords foreign key
SELECT has_fk('user_passwords', 'User passwords should have foreign key to users');

-- =============================================================================
-- TEST TOKEN_REVOCATIONS TABLE
-- =============================================================================

-- Test 20-24: Token revocations table structure
SELECT has_table('token_revocations', 'Token revocations table should exist');
SELECT has_column('token_revocations', 'id', 'Token revocations should have id column');
SELECT has_column('token_revocations', 'user_uuid', 'Token revocations should have user_uuid column');
SELECT has_column('token_revocations', 'revoked_before', 'Token revocations should have revoked_before column');
SELECT has_column('token_revocations', 'reason', 'Token revocations should have reason column');

-- Test 25-27: Token revocations indexes
SELECT has_index('token_revocations', 'idx_token_revocations_user_uuid', 'Token revocations should have user_uuid index');
SELECT has_index('token_revocations', 'idx_token_revocations_revoked_before', 'Token revocations should have revoked_before index');
SELECT has_index('token_revocations', 'idx_token_revocations_composite', 'Token revocations should have composite index');

-- =============================================================================
-- TEST FUNCTIONS
-- =============================================================================

-- Test 28: register_user function exists
SELECT has_function('register_user', 'register_user function should exist');

-- Test 29: is_token_valid function exists
SELECT has_function('is_token_valid', 'is_token_valid function should exist');

-- Test 30: revoke_user_tokens function exists
SELECT has_function('revoke_user_tokens', 'revoke_user_tokens function should exist');

-- Test 31: revoke_all_tokens function exists
SELECT has_function('revoke_all_tokens', 'revoke_all_tokens function should exist');

-- Test 32: cleanup_expired_revocations function exists
SELECT has_function('cleanup_expired_revocations', 'cleanup_expired_revocations function should exist');

-- =============================================================================
-- TEST FUNCTION BEHAVIOR
-- =============================================================================

-- Test 33: register_user creates user
SELECT is(
    (SELECT COUNT(*) FROM users WHERE email = 'test@example.com'),
    0::BIGINT,
    'User should not exist before registration'
);

SELECT register_user('test@example.com', 'hashed_password');

SELECT is(
    (SELECT COUNT(*) FROM users WHERE email = 'test@example.com'),
    1::BIGINT,
    'User should exist after registration'
);

-- Test 35: Token validation works for new user
SELECT is(
    is_token_valid(
        (SELECT uuid FROM users WHERE email = 'test@example.com'),
        NOW()::TIMESTAMP
    ),
    TRUE,
    'Token should be valid for new user'
);

SELECT * FROM finish();
ROLLBACK;
