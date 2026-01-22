-- Test token revocation functionality

BEGIN;

SELECT plan(16);

-- Test table existence
SELECT has_table('token_revocations', 'token_revocations table should exist');

-- Test token revocation functions
SELECT has_function('is_token_valid', ARRAY['uuid', 'timestamp without time zone'], 'is_token_valid function should exist');
SELECT has_function('revoke_user_tokens', ARRAY['uuid', 'character varying', 'uuid'], 'revoke_user_tokens function should exist');
SELECT has_function('revoke_all_tokens', ARRAY['character varying', 'uuid'], 'revoke_all_tokens function should exist');

-- Create test user for token tests
INSERT INTO users (email, uuid) VALUES ('token_test@example.com', '123e4567-e89b-12d3-a456-426614174000');

-- Test token validation without any revocations
SELECT ok(
    is_token_valid('123e4567-e89b-12d3-a456-426614174000', (NOW() - INTERVAL '1 hour')::TIMESTAMP),
    'Token should be valid when no revocations exist'
);

-- Test user-specific token revocation
SELECT lives_ok(
    'SELECT revoke_user_tokens(''123e4567-e89b-12d3-a456-426614174000'', ''test_logout'')',
    'revoke_user_tokens should execute without error'
);

-- Test that older tokens are now invalid
SELECT ok(
    NOT is_token_valid('123e4567-e89b-12d3-a456-426614174000', (NOW() - INTERVAL '1 hour')::TIMESTAMP),
    'Token issued before revocation should be invalid'
);

-- Test that newer tokens are still valid
SELECT ok(
    is_token_valid('123e4567-e89b-12d3-a456-426614174000', (NOW() + INTERVAL '1 hour')::TIMESTAMP),
    'Token issued after revocation should be valid'
);

-- Test global token revocation
SELECT lives_ok(
    'SELECT revoke_all_tokens(''security_incident'')',
    'revoke_all_tokens should execute without error'
);

-- Test that tokens issued before global revocation are invalid
SELECT ok(
    NOT is_token_valid('123e4567-e89b-12d3-a456-426614174000', (NOW() - INTERVAL '30 minutes')::TIMESTAMP),
    'Tokens issued before global revocation should be invalid'
);

-- Test revocation record creation
SELECT ok(
    (SELECT COUNT(*) FROM token_revocations WHERE user_uuid = '123e4567-e89b-12d3-a456-426614174000') >= 1,
    'User-specific revocation record should exist'
);

SELECT ok(
    (SELECT COUNT(*) FROM token_revocations WHERE user_uuid IS NULL) >= 1,
    'Global revocation record should exist'
);

-- Test revocation reasons
SELECT ok(
    (SELECT reason FROM token_revocations WHERE user_uuid = '123e4567-e89b-12d3-a456-426614174000' LIMIT 1) = 'test_logout',
    'Revocation reason should be stored correctly'
);

-- Test cleanup function
SELECT has_function('cleanup_expired_revocations', ARRAY['integer'], 'cleanup_expired_revocations function should exist');

-- Insert old revocation record for cleanup test
INSERT INTO token_revocations (user_uuid, revoked_before, reason, created_at)
VALUES ('123e4567-e89b-12d3-a456-426614174000', NOW(), 'old_record', NOW() - INTERVAL '31 days');

-- Test cleanup function
SELECT ok(
    cleanup_expired_revocations(30) >= 1,
    'cleanup_expired_revocations should remove old records'
);

-- Verify cleanup worked
SELECT ok(
    (SELECT COUNT(*) FROM token_revocations WHERE reason = 'old_record') = 0,
    'Old revocation records should be cleaned up'
);

SELECT * FROM finish();
ROLLBACK;