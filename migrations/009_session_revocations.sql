ALTER TABLE token_revocations
    ADD COLUMN IF NOT EXISTS sid VARCHAR(255) NULL;

CREATE INDEX IF NOT EXISTS idx_token_revocations_session
    ON token_revocations(subject, sid) WHERE sid IS NOT NULL;

-- An upgrade from the original schema retains these overloads. The old validator
-- interprets user_uuid NULL as global, and the old revokers make default calls ambiguous.
DROP FUNCTION IF EXISTS is_token_valid(UUID, TIMESTAMP);
DROP FUNCTION IF EXISTS revoke_user_tokens(UUID, VARCHAR, UUID);
DROP FUNCTION IF EXISTS revoke_all_tokens(VARCHAR, UUID);

-- Session revocations apply to every token in the session, regardless of issue time.
CREATE OR REPLACE FUNCTION is_token_valid(
    p_token_type VARCHAR(64),
    p_subject VARCHAR(255),
    p_jti VARCHAR(255),
    p_issued_at TIMESTAMP,
    p_sid VARCHAR(255)
) RETURNS BOOLEAN AS $$
BEGIN
    RETURN NOT EXISTS (
        SELECT 1 FROM token_revocations
        WHERE (
            sid IS NOT NULL
            AND sid = p_sid
            AND subject = p_subject
        ) OR (
            sid IS NULL
            AND (token_type IS NULL OR token_type = p_token_type)
            AND (subject IS NULL OR subject = p_subject OR user_uuid::text = p_subject)
            AND (jti IS NULL OR jti = p_jti)
            AND revoked_before >= p_issued_at
        )
    );
END;
$$ LANGUAGE plpgsql;

-- Keep existing four-argument callers working without treating session rows as global rules.
CREATE OR REPLACE FUNCTION is_token_valid(
    p_token_type VARCHAR(64),
    p_subject VARCHAR(255),
    p_jti VARCHAR(255),
    p_issued_at TIMESTAMP
) RETURNS BOOLEAN AS $$
BEGIN
    RETURN is_token_valid(p_token_type, p_subject, p_jti, p_issued_at, NULL::VARCHAR);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION revoke_session(
    p_sid VARCHAR(255),
    p_subject VARCHAR(255),
    p_reason VARCHAR(255) DEFAULT 'user_logout',
    p_created_by UUID DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    IF p_sid IS NULL OR btrim(p_sid) = '' OR p_subject IS NULL OR btrim(p_subject) = '' THEN
        RAISE EXCEPTION 'Session ID and subject are required' USING ERRCODE = '22023';
    END IF;

    INSERT INTO token_revocations (subject, sid, reason, created_by)
    VALUES (p_subject, p_sid, p_reason, p_created_by);
END;
$$ LANGUAGE plpgsql;

-- Session lifetime can be extended by refreshing; age alone cannot prove its tokens expired.
CREATE OR REPLACE FUNCTION cleanup_expired_revocations(
    p_days_old INTEGER DEFAULT 30
) RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM token_revocations
    WHERE sid IS NULL
    AND created_at < NOW() - INTERVAL '1 day' * p_days_old;

    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;
