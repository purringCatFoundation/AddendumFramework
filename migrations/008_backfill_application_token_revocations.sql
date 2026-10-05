INSERT INTO token_revocations (subject, jti, token_type, revoked_before, reason, created_at)
SELECT
    application_name,
    jti,
    'application',
    COALESCE(created_at, revoked_at)::timestamp,
    COALESCE(revoked_reason, 'application_token_revocation'),
    COALESCE(revoked_at, CURRENT_TIMESTAMP)::timestamp
FROM application_tokens
WHERE revoked_at IS NOT NULL
AND NOT EXISTS (
    SELECT 1
    FROM token_revocations tr
    WHERE tr.token_type = 'application'
    AND tr.jti = application_tokens.jti
);
