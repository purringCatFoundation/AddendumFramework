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
    user_uuid UUID NULL,           -- NULL means global revocation
    revoked_before TIMESTAMP NOT NULL DEFAULT NOW(),
    reason VARCHAR(255),           -- Optional reason (e.g., "admin_action", "user_logout", "security_incident")
    created_by UUID NULL,          -- Admin who created the revocation
    created_at TIMESTAMP DEFAULT NOW()
);

-- Indexes for efficient lookups
CREATE INDEX IF NOT EXISTS idx_token_revocations_user_uuid ON token_revocations(user_uuid);
CREATE INDEX IF NOT EXISTS idx_token_revocations_revoked_before ON token_revocations(revoked_before);
CREATE INDEX IF NOT EXISTS idx_token_revocations_composite ON token_revocations(user_uuid, revoked_before);

-- Function to check if a token is valid based on user UUID and issued timestamp
CREATE OR REPLACE FUNCTION is_token_valid(
    p_user_uuid UUID,
    p_issued_at TIMESTAMP
) RETURNS BOOLEAN AS $$
DECLARE
    revocation_timestamp TIMESTAMP;
BEGIN
    -- Check for user-specific revocations
    SELECT MAX(revoked_before) INTO revocation_timestamp
    FROM token_revocations 
    WHERE user_uuid = p_user_uuid 
    AND revoked_before >= p_issued_at;
    
    -- If user-specific revocation found, token is invalid
    IF revocation_timestamp IS NOT NULL THEN
        RETURN FALSE;
    END IF;
    
    -- Check for global revocations (user_uuid IS NULL)
    SELECT MAX(revoked_before) INTO revocation_timestamp
    FROM token_revocations 
    WHERE user_uuid IS NULL 
    AND revoked_before >= p_issued_at;
    
    -- If global revocation found, token is invalid
    IF revocation_timestamp IS NOT NULL THEN
        RETURN FALSE;
    END IF;
    
    -- No revocations found, token is valid
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;

-- Function to revoke all tokens for a specific user
CREATE OR REPLACE FUNCTION revoke_user_tokens(
    p_user_uuid UUID,
    p_reason VARCHAR(255) DEFAULT 'user_logout',
    p_created_by UUID DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    INSERT INTO token_revocations (user_uuid, revoked_before, reason, created_by)
    VALUES (p_user_uuid, NOW(), p_reason, p_created_by);
END;
$$ LANGUAGE plpgsql;

-- Function to revoke all tokens globally (emergency use)
CREATE OR REPLACE FUNCTION revoke_all_tokens(
    p_reason VARCHAR(255) DEFAULT 'global_revocation',
    p_created_by UUID DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    INSERT INTO token_revocations (user_uuid, revoked_before, reason, created_by)
    VALUES (NULL, NOW(), p_reason, p_created_by);
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
