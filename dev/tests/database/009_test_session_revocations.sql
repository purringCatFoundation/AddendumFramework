BEGIN;

SELECT plan(19);

SELECT has_column('token_revocations', 'sid', 'Revocations can target a session');
SELECT has_function('revoke_session', ARRAY['character varying', 'character varying', 'character varying', 'uuid'], 'Session revocation function exists');
SELECT hasnt_function('is_token_valid', ARRAY['uuid', 'timestamp without time zone'], 'Unsafe legacy validator is retired on upgrades');
SELECT hasnt_function('revoke_user_tokens', ARRAY['uuid', 'character varying', 'uuid'], 'Legacy user revocation overload cannot shadow the new function');
SELECT hasnt_function('revoke_all_tokens', ARRAY['character varying', 'uuid'], 'Legacy global revocation overload cannot shadow the new function');

SELECT ok(is_token_valid('user', 'session-user', 'access-a', NOW()::timestamp, 'session-a'), 'Session A starts valid');
SELECT lives_ok($$SELECT revoke_session('session-a', 'session-user', 'logout')$$, 'Logout creates a session revocation');
SELECT ok(NOT is_token_valid('user', 'session-user', 'access-a', (NOW() - INTERVAL '1 hour')::timestamp, 'session-a'), 'Logout blocks access token A');
SELECT ok(NOT is_token_valid('user_refresh', 'session-user', 'refresh-a', (NOW() - INTERVAL '1 hour')::timestamp, 'session-a'), 'Logout blocks refresh token A');
SELECT ok(NOT is_token_valid('admin', 'session-user', 'old-access-a', (NOW() - INTERVAL '1 day')::timestamp, 'session-a'), 'Logout blocks older admin access tokens of the session');
SELECT ok(NOT is_token_valid('user', 'session-user', 'racing-access-a', (NOW() + INTERVAL '1 second')::timestamp, 'session-a'), 'A token issued after logout cannot resurrect session A');
SELECT ok(is_token_valid('user', 'session-user', 'access-b', (NOW() - INTERVAL '1 hour')::timestamp, 'session-b'), 'Session B access token remains valid');
SELECT ok(is_token_valid('user_refresh', 'session-user', 'refresh-b', (NOW() - INTERVAL '1 hour')::timestamp, 'session-b'), 'Session B refresh token remains valid');
SELECT ok(is_token_valid('user', 'other-user', 'access-other', NOW()::timestamp, 'session-a'), 'Session revocation is scoped to its subject');
SELECT ok(is_token_valid('application', 'external-api', 'app-jti', NOW()::timestamp), 'Session logout does not create a global revocation');

SELECT throws_ok($$SELECT revoke_session(NULL, 'session-user')$$, '22023', 'Session ID and subject are required', 'Missing session ID cannot create a global rule');
SELECT throws_ok($$SELECT revoke_session('session-a', '')$$, '22023', 'Session ID and subject are required', 'Missing subject cannot create a global rule');

UPDATE token_revocations SET created_at = NOW() - INTERVAL '31 days' WHERE sid = 'session-a';
SELECT cleanup_expired_revocations(30);
SELECT ok(NOT is_token_valid('user_refresh', 'session-user', 'refresh-a', NOW()::timestamp, 'session-a'), 'Cleanup cannot resurrect a revoked session');

SELECT revoke_tokens_before('user', 'session-user', NULL, NOW()::timestamp, 'cutoff');
SELECT ok(NOT is_token_valid('user', 'session-user', 'access-b', (NOW() - INTERVAL '1 hour')::timestamp, 'session-b'), 'Existing cutoff revocations still apply to session tokens');

SELECT * FROM finish();
ROLLBACK;
