-- Application Tokens System
-- Tokens for external application/service authentication
-- These tokens never expire and have APPLICATION token type

CREATE TABLE IF NOT EXISTS application_tokens (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT gen_random_uuid(),

    -- Token identification
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    application_name VARCHAR(100) NOT NULL,

    -- Ownership information
    owner_name VARCHAR(200) NOT NULL,
    owner_email VARCHAR(255) NOT NULL,

    -- Token details
    jti VARCHAR(100) NOT NULL UNIQUE, -- JWT ID for revocation
    last_used_at TIMESTAMP WITH TIME ZONE,

    -- Metadata
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP WITH TIME ZONE,
    revoked_reason TEXT,

    -- Constraints
    CONSTRAINT valid_application_name CHECK (LENGTH(application_name) >= 3),
    CONSTRAINT valid_owner_name CHECK (LENGTH(owner_name) >= 2),
    CONSTRAINT valid_owner_email CHECK (owner_email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
);

-- Indexes for performance
CREATE INDEX idx_application_tokens_application_name ON application_tokens(application_name);
CREATE INDEX idx_application_tokens_owner_email ON application_tokens(owner_email);
CREATE INDEX idx_application_tokens_jti ON application_tokens(jti);
CREATE INDEX idx_application_tokens_created_at ON application_tokens(created_at);
CREATE INDEX idx_application_tokens_revoked_at ON application_tokens(revoked_at);
CREATE INDEX idx_application_tokens_active ON application_tokens(created_at) WHERE revoked_at IS NULL;

-- Function: Create application token
CREATE OR REPLACE FUNCTION create_application_token(
    p_token_hash VARCHAR,
    p_application_name VARCHAR,
    p_owner_name VARCHAR,
    p_owner_email VARCHAR,
    p_jti VARCHAR
) RETURNS UUID AS $$
DECLARE
    v_uuid UUID;
BEGIN
    INSERT INTO application_tokens (
        token_hash,
        application_name,
        owner_name,
        owner_email,
        jti
    ) VALUES (
        p_token_hash,
        p_application_name,
        p_owner_name,
        p_owner_email,
        p_jti
    )
    RETURNING uuid INTO v_uuid;

    RETURN v_uuid;
END;
$$ LANGUAGE plpgsql;

-- Function: Check if application token is valid
CREATE OR REPLACE FUNCTION is_application_token_valid(p_jti VARCHAR)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1
        FROM application_tokens
        WHERE jti = p_jti
        AND revoked_at IS NULL
    );
END;
$$ LANGUAGE plpgsql;

-- Function: Update token last used timestamp
CREATE OR REPLACE FUNCTION update_application_token_last_used(p_jti VARCHAR)
RETURNS VOID AS $$
BEGIN
    UPDATE application_tokens
    SET last_used_at = CURRENT_TIMESTAMP
    WHERE jti = p_jti
    AND revoked_at IS NULL;
END;
$$ LANGUAGE plpgsql;

-- Function: Revoke application token by JTI
CREATE OR REPLACE FUNCTION revoke_application_token(
    p_jti VARCHAR,
    p_reason TEXT DEFAULT NULL
) RETURNS BOOLEAN AS $$
DECLARE
    v_affected INTEGER;
BEGIN
    UPDATE application_tokens
    SET
        revoked_at = CURRENT_TIMESTAMP,
        revoked_reason = p_reason
    WHERE jti = p_jti
    AND revoked_at IS NULL;

    GET DIAGNOSTICS v_affected = ROW_COUNT;

    RETURN v_affected > 0;
END;
$$ LANGUAGE plpgsql;

-- Function: Revoke application tokens by application name
CREATE OR REPLACE FUNCTION revoke_application_tokens_by_name(
    p_application_name VARCHAR,
    p_created_after TIMESTAMP WITH TIME ZONE DEFAULT NULL,
    p_reason TEXT DEFAULT NULL
) RETURNS INTEGER AS $$
DECLARE
    v_affected INTEGER;
BEGIN
    UPDATE application_tokens
    SET
        revoked_at = CURRENT_TIMESTAMP,
        revoked_reason = p_reason
    WHERE application_name = p_application_name
    AND revoked_at IS NULL
    AND (p_created_after IS NULL OR created_at >= p_created_after);

    GET DIAGNOSTICS v_affected = ROW_COUNT;

    RETURN v_affected;
END;
$$ LANGUAGE plpgsql;

-- Function: Revoke application tokens by owner email
CREATE OR REPLACE FUNCTION revoke_application_tokens_by_owner(
    p_owner_email VARCHAR,
    p_created_after TIMESTAMP WITH TIME ZONE DEFAULT NULL,
    p_reason TEXT DEFAULT NULL
) RETURNS INTEGER AS $$
DECLARE
    v_affected INTEGER;
BEGIN
    UPDATE application_tokens
    SET
        revoked_at = CURRENT_TIMESTAMP,
        revoked_reason = p_reason
    WHERE owner_email = p_owner_email
    AND revoked_at IS NULL
    AND (p_created_after IS NULL OR created_at >= p_created_after);

    GET DIAGNOSTICS v_affected = ROW_COUNT;

    RETURN v_affected;
END;
$$ LANGUAGE plpgsql;

-- Function: Revoke application tokens by date
CREATE OR REPLACE FUNCTION revoke_application_tokens_by_date(
    p_created_after TIMESTAMP WITH TIME ZONE,
    p_application_name VARCHAR DEFAULT NULL,
    p_owner_email VARCHAR DEFAULT NULL,
    p_reason TEXT DEFAULT NULL
) RETURNS INTEGER AS $$
DECLARE
    v_affected INTEGER;
BEGIN
    UPDATE application_tokens
    SET
        revoked_at = CURRENT_TIMESTAMP,
        revoked_reason = p_reason
    WHERE created_at >= p_created_after
    AND revoked_at IS NULL
    AND (p_application_name IS NULL OR application_name = p_application_name)
    AND (p_owner_email IS NULL OR owner_email = p_owner_email);

    GET DIAGNOSTICS v_affected = ROW_COUNT;

    RETURN v_affected;
END;
$$ LANGUAGE plpgsql;

-- Function: Get application token statistics
CREATE OR REPLACE FUNCTION get_application_token_statistics()
RETURNS TABLE (
    total_tokens BIGINT,
    active_tokens BIGINT,
    revoked_tokens BIGINT,
    unique_applications BIGINT,
    unique_owners BIGINT,
    tokens_used_last_24h BIGINT,
    tokens_used_last_7d BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        COUNT(*)::BIGINT,
        COUNT(*) FILTER (WHERE revoked_at IS NULL)::BIGINT,
        COUNT(*) FILTER (WHERE revoked_at IS NOT NULL)::BIGINT,
        COUNT(DISTINCT application_name)::BIGINT,
        COUNT(DISTINCT owner_email)::BIGINT,
        COUNT(*) FILTER (WHERE last_used_at >= CURRENT_TIMESTAMP - INTERVAL '24 hours')::BIGINT,
        COUNT(*) FILTER (WHERE last_used_at >= CURRENT_TIMESTAMP - INTERVAL '7 days')::BIGINT
    FROM application_tokens;
END;
$$ LANGUAGE plpgsql;

-- Function: List active application tokens
CREATE OR REPLACE FUNCTION list_active_application_tokens()
RETURNS TABLE (
    uuid UUID,
    application_name VARCHAR,
    owner_name VARCHAR,
    owner_email VARCHAR,
    created_at TIMESTAMP WITH TIME ZONE,
    last_used_at TIMESTAMP WITH TIME ZONE
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        at.uuid,
        at.application_name,
        at.owner_name,
        at.owner_email,
        at.created_at,
        at.last_used_at
    FROM application_tokens at
    WHERE at.revoked_at IS NULL
    ORDER BY at.created_at DESC;
END;
$$ LANGUAGE plpgsql;

COMMENT ON TABLE application_tokens IS 'Application tokens for external service authentication - never expire unless revoked';
COMMENT ON COLUMN application_tokens.token_hash IS 'SHA-256 hash of the token for secure storage';
COMMENT ON COLUMN application_tokens.jti IS 'JWT ID - unique identifier for the token, used in JWT payload';
COMMENT ON COLUMN application_tokens.application_name IS 'Name of the application/service using this token';
COMMENT ON COLUMN application_tokens.owner_name IS 'Name of the person/team responsible for this token';
COMMENT ON COLUMN application_tokens.owner_email IS 'Email of the person/team responsible for this token';
