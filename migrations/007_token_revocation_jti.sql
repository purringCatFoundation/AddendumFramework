ALTER TABLE token_revocations
    ADD COLUMN IF NOT EXISTS subject VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS jti VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS token_type VARCHAR(64) NULL;

UPDATE token_revocations
SET subject = user_uuid::text
WHERE subject IS NULL
AND user_uuid IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_token_revocations_subject ON token_revocations(subject);
CREATE INDEX IF NOT EXISTS idx_token_revocations_jti ON token_revocations(jti);
CREATE INDEX IF NOT EXISTS idx_token_revocations_token_type ON token_revocations(token_type);
CREATE INDEX IF NOT EXISTS idx_token_revocations_subject_token_type ON token_revocations(subject, token_type, revoked_before);

CREATE OR REPLACE FUNCTION is_token_valid(
    p_token_type VARCHAR(64),
    p_subject VARCHAR(255),
    p_jti VARCHAR(255),
    p_issued_at TIMESTAMP
) RETURNS BOOLEAN AS $$
DECLARE
    revocation_found BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1
        FROM token_revocations
        WHERE (token_type IS NULL OR token_type = p_token_type)
        AND (subject IS NULL OR subject = p_subject OR user_uuid::text = p_subject)
        AND (jti IS NULL OR jti = p_jti)
        AND revoked_before >= p_issued_at
    ) INTO revocation_found;

    RETURN NOT revocation_found;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION revoke_token(
    p_jti VARCHAR(255),
    p_token_type VARCHAR(64),
    p_subject VARCHAR(255),
    p_revoked_before TIMESTAMP,
    p_reason VARCHAR(255) DEFAULT 'token_revocation',
    p_created_by UUID DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    INSERT INTO token_revocations (subject, jti, token_type, revoked_before, reason, created_by)
    VALUES (p_subject, p_jti, p_token_type, p_revoked_before, p_reason, p_created_by);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION revoke_tokens_before(
    p_token_type VARCHAR(64),
    p_subject VARCHAR(255),
    p_jti VARCHAR(255),
    p_revoked_before TIMESTAMP,
    p_reason VARCHAR(255) DEFAULT 'token_revocation',
    p_created_by UUID DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    INSERT INTO token_revocations (subject, jti, token_type, revoked_before, reason, created_by)
    VALUES (p_subject, p_jti, p_token_type, p_revoked_before, p_reason, p_created_by);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION revoke_user_tokens(
    p_user_uuid UUID,
    p_reason VARCHAR(255) DEFAULT 'user_logout',
    p_created_by UUID DEFAULT NULL,
    p_token_type VARCHAR(64) DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    INSERT INTO token_revocations (user_uuid, subject, token_type, revoked_before, reason, created_by)
    VALUES (p_user_uuid, p_user_uuid::text, p_token_type, NOW(), p_reason, p_created_by);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION revoke_all_tokens(
    p_reason VARCHAR(255) DEFAULT 'global_revocation',
    p_created_by UUID DEFAULT NULL,
    p_token_type VARCHAR(64) DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    INSERT INTO token_revocations (user_uuid, subject, token_type, revoked_before, reason, created_by)
    VALUES (NULL, NULL, p_token_type, NOW(), p_reason, p_created_by);
END;
$$ LANGUAGE plpgsql;
