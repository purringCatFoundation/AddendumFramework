-- Admins System
-- Admin privileges for users - grants full access to all resources

CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT gen_random_uuid(),

    -- User reference
    user_uuid UUID NOT NULL,

    -- Admin metadata
    granted_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    granted_by_user_uuid UUID, -- NULL for CLI grants
    granted_reason TEXT,

    -- Revocation
    revoked_at TIMESTAMP WITH TIME ZONE,
    revoked_by_user_uuid UUID,
    revoked_reason TEXT,

    -- Constraints
    CONSTRAINT fk_admin_user FOREIGN KEY (user_uuid) REFERENCES users(uuid) ON DELETE CASCADE,
    CONSTRAINT fk_granted_by_user FOREIGN KEY (granted_by_user_uuid) REFERENCES users(uuid) ON DELETE SET NULL,
    CONSTRAINT fk_revoked_by_user FOREIGN KEY (revoked_by_user_uuid) REFERENCES users(uuid) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX idx_admins_user_uuid ON admins(user_uuid);
CREATE INDEX idx_admins_granted_at ON admins(granted_at);
CREATE INDEX idx_admins_revoked_at ON admins(revoked_at);

-- Only one active admin record per user (partial unique index)
CREATE UNIQUE INDEX unique_active_admin_per_user ON admins(user_uuid) WHERE revoked_at IS NULL;

-- Function: Check if user is admin
CREATE OR REPLACE FUNCTION is_user_admin(p_user_uuid UUID)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1
        FROM admins
        WHERE user_uuid = p_user_uuid
        AND revoked_at IS NULL
    );
END;
$$ LANGUAGE plpgsql;

-- Function: Grant admin privileges to user
CREATE OR REPLACE FUNCTION grant_admin_privileges(
    p_user_uuid UUID,
    p_granted_by_user_uuid UUID DEFAULT NULL,
    p_reason TEXT DEFAULT NULL
) RETURNS UUID AS $$
DECLARE
    v_uuid UUID;
    v_user_exists BOOLEAN;
BEGIN
    -- Check if user exists
    SELECT EXISTS (
        SELECT 1 FROM users WHERE uuid = p_user_uuid
    ) INTO v_user_exists;

    IF NOT v_user_exists THEN
        RAISE EXCEPTION 'User with UUID % does not exist', p_user_uuid;
    END IF;

    -- Check if user already has active admin privileges
    IF is_user_admin(p_user_uuid) THEN
        RAISE EXCEPTION 'User % already has active admin privileges', p_user_uuid;
    END IF;

    -- Grant admin privileges
    INSERT INTO admins (
        user_uuid,
        granted_by_user_uuid,
        granted_reason
    ) VALUES (
        p_user_uuid,
        p_granted_by_user_uuid,
        p_reason
    )
    RETURNING uuid INTO v_uuid;

    RETURN v_uuid;
END;
$$ LANGUAGE plpgsql;

-- Function: Revoke admin privileges from user
CREATE OR REPLACE FUNCTION revoke_admin_privileges(
    p_user_uuid UUID,
    p_revoked_by_user_uuid UUID DEFAULT NULL,
    p_reason TEXT DEFAULT NULL
) RETURNS BOOLEAN AS $$
DECLARE
    v_affected INTEGER;
BEGIN
    UPDATE admins
    SET
        revoked_at = CURRENT_TIMESTAMP,
        revoked_by_user_uuid = p_revoked_by_user_uuid,
        revoked_reason = p_reason
    WHERE user_uuid = p_user_uuid
    AND revoked_at IS NULL;

    GET DIAGNOSTICS v_affected = ROW_COUNT;

    RETURN v_affected > 0;
END;
$$ LANGUAGE plpgsql;

-- Function: Get admin record for user
CREATE OR REPLACE FUNCTION get_admin_by_user_uuid(p_user_uuid UUID)
RETURNS TABLE (
    id INTEGER,
    uuid UUID,
    user_uuid UUID,
    granted_at TIMESTAMP WITH TIME ZONE,
    granted_by_user_uuid UUID,
    granted_reason TEXT,
    revoked_at TIMESTAMP WITH TIME ZONE,
    revoked_by_user_uuid UUID,
    revoked_reason TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        a.id,
        a.uuid,
        a.user_uuid,
        a.granted_at,
        a.granted_by_user_uuid,
        a.granted_reason,
        a.revoked_at,
        a.revoked_by_user_uuid,
        a.revoked_reason
    FROM admins a
    WHERE a.user_uuid = p_user_uuid
    AND a.revoked_at IS NULL
    LIMIT 1;
END;
$$ LANGUAGE plpgsql;

-- Function: List all active admins
CREATE OR REPLACE FUNCTION list_active_admins()
RETURNS TABLE (
    admin_uuid UUID,
    user_uuid UUID,
    user_email VARCHAR,
    granted_at TIMESTAMP WITH TIME ZONE,
    granted_by_email VARCHAR,
    granted_reason TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        a.uuid,
        a.user_uuid,
        u.email,
        a.granted_at,
        gb.email as granted_by_email,
        a.granted_reason
    FROM admins a
    JOIN users u ON a.user_uuid = u.uuid
    LEFT JOIN users gb ON a.granted_by_user_uuid = gb.uuid
    WHERE a.revoked_at IS NULL
    ORDER BY a.granted_at DESC;
END;
$$ LANGUAGE plpgsql;

-- Function: Get admin statistics
CREATE OR REPLACE FUNCTION get_admin_statistics()
RETURNS TABLE (
    total_admins BIGINT,
    active_admins BIGINT,
    revoked_admins BIGINT,
    admins_granted_last_30d BIGINT,
    admins_revoked_last_30d BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        COUNT(*)::BIGINT,
        COUNT(*) FILTER (WHERE revoked_at IS NULL)::BIGINT,
        COUNT(*) FILTER (WHERE revoked_at IS NOT NULL)::BIGINT,
        COUNT(*) FILTER (WHERE granted_at >= CURRENT_TIMESTAMP - INTERVAL '30 days')::BIGINT,
        COUNT(*) FILTER (WHERE revoked_at >= CURRENT_TIMESTAMP - INTERVAL '30 days')::BIGINT
    FROM admins;
END;
$$ LANGUAGE plpgsql;

-- Function: Get admin audit trail for user
CREATE OR REPLACE FUNCTION get_admin_audit_trail(p_user_uuid UUID)
RETURNS TABLE (
    admin_uuid UUID,
    action VARCHAR,
    action_at TIMESTAMP WITH TIME ZONE,
    action_by_email VARCHAR,
    reason TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        a.uuid,
        CASE
            WHEN a.revoked_at IS NULL THEN 'GRANTED'::VARCHAR
            ELSE 'REVOKED'::VARCHAR
        END as action,
        COALESCE(a.revoked_at, a.granted_at) as action_at,
        COALESCE(rb.email, gb.email) as action_by_email,
        COALESCE(a.revoked_reason, a.granted_reason) as reason
    FROM admins a
    LEFT JOIN users gb ON a.granted_by_user_uuid = gb.uuid
    LEFT JOIN users rb ON a.revoked_by_user_uuid = rb.uuid
    WHERE a.user_uuid = p_user_uuid
    ORDER BY COALESCE(a.revoked_at, a.granted_at) DESC;
END;
$$ LANGUAGE plpgsql;

COMMENT ON TABLE admins IS 'Admin privileges for users - grants full access to all resources';
COMMENT ON COLUMN admins.user_uuid IS 'User who has admin privileges';
COMMENT ON COLUMN admins.granted_by_user_uuid IS 'User who granted admin privileges (NULL for CLI)';
COMMENT ON COLUMN admins.granted_reason IS 'Reason for granting admin privileges';
COMMENT ON COLUMN admins.revoked_at IS 'When admin privileges were revoked (NULL if active)';
COMMENT ON COLUMN admins.revoked_by_user_uuid IS 'User who revoked admin privileges';
COMMENT ON COLUMN admins.revoked_reason IS 'Reason for revoking admin privileges';
