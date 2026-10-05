CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT uuid_generate_v4() UNIQUE,
    email VARCHAR NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS user_passwords (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    password TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE FUNCTION register_user(p_email VARCHAR, p_password TEXT)
RETURNS TABLE(uuid UUID, email VARCHAR) AS $$
DECLARE
    v_id INTEGER;
    v_uuid UUID;
BEGIN
    INSERT INTO users(email) VALUES(p_email) RETURNING id, users.uuid INTO v_id, v_uuid;
    INSERT INTO user_passwords(user_id, password) VALUES(v_id, p_password);
    RETURN QUERY SELECT v_uuid, p_email;
END;
$$ LANGUAGE plpgsql;

-- Token revocation management table
CREATE TABLE IF NOT EXISTS token_revocations (
    id SERIAL PRIMARY KEY,
    user_uuid UUID NULL,           -- Legacy user UUID; NULL means global revocation
    subject VARCHAR(255) NULL,     -- JWT sub; NULL means global revocation
    jti VARCHAR(255) NULL,         -- JWT ID for revoking a single token
    token_type VARCHAR(64) NULL,   -- NULL applies to all token types
    revoked_before TIMESTAMP NOT NULL DEFAULT NOW(),
    reason VARCHAR(255),           -- Optional reason (e.g., "admin_action", "user_logout", "security_incident")
    created_by UUID NULL,          -- Admin who created the revocation
    created_at TIMESTAMP DEFAULT NOW()
);

-- Indexes for efficient lookups
CREATE INDEX IF NOT EXISTS idx_token_revocations_user_uuid ON token_revocations(user_uuid);
CREATE INDEX IF NOT EXISTS idx_token_revocations_subject ON token_revocations(subject);
CREATE INDEX IF NOT EXISTS idx_token_revocations_jti ON token_revocations(jti);
CREATE INDEX IF NOT EXISTS idx_token_revocations_token_type ON token_revocations(token_type);
CREATE INDEX IF NOT EXISTS idx_token_revocations_revoked_before ON token_revocations(revoked_before);
CREATE INDEX IF NOT EXISTS idx_token_revocations_subject_token_type ON token_revocations(subject, token_type, revoked_before);

-- Function to check if a token is valid based on token ID, subject, type, and issued timestamp
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

-- Function to revoke one specific token
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

-- Function to revoke tokens by type, optional subject/JTI, and issue timestamp cutoff
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

-- Function to revoke all tokens for a specific user
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

-- Function to revoke all tokens globally (emergency use)
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

-- Function to clean up old revocation records (run periodically)
CREATE OR REPLACE FUNCTION cleanup_expired_revocations(
    p_days_old INTEGER DEFAULT 30
) RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM token_revocations 
    WHERE created_at < NOW() - INTERVAL '1 day' * p_days_old;
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;
