-- Migration: Action Logging System
-- Creates tables and functions for comprehensive action logging with security features

-- Create enum for action status
CREATE TYPE action_status AS ENUM ('success', 'failure', 'forbidden', 'error');

-- Action logs table - comprehensive logging of all user actions
CREATE TABLE IF NOT EXISTS action_logs (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT uuid_generate_v4() UNIQUE,

    -- User and authentication info
    user_uuid UUID NOT NULL,
    character_uuid UUID NULL,
    token_type VARCHAR(20) NOT NULL DEFAULT 'user',

    -- Action details
    action_name VARCHAR(255) NOT NULL,
    action_class VARCHAR(500) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    request_path VARCHAR(1000) NOT NULL,

    -- Request data (sanitized - no sensitive info)
    request_params JSONB NULL,
    query_params JSONB NULL,

    -- Response details
    status action_status NOT NULL,
    http_status_code INTEGER NOT NULL,
    response_message TEXT NULL,

    -- Metadata
    ip_address INET NULL,
    user_agent TEXT NULL,

    -- Timing
    execution_time_ms INTEGER NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Indexes for efficient querying
    CONSTRAINT valid_http_status CHECK (http_status_code >= 100 AND http_status_code < 600)
);

-- Indexes for efficient lookups
CREATE INDEX IF NOT EXISTS idx_action_logs_user_uuid ON action_logs(user_uuid);
CREATE INDEX IF NOT EXISTS idx_action_logs_character_uuid ON action_logs(character_uuid) WHERE character_uuid IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_action_logs_token_type ON action_logs(token_type);
CREATE INDEX IF NOT EXISTS idx_action_logs_action_name ON action_logs(action_name);
CREATE INDEX IF NOT EXISTS idx_action_logs_status ON action_logs(status);
CREATE INDEX IF NOT EXISTS idx_action_logs_created_at ON action_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_action_logs_user_created ON action_logs(user_uuid, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_action_logs_ip_address ON action_logs(ip_address);

-- GIN index for JSONB columns for efficient JSON queries
CREATE INDEX IF NOT EXISTS idx_action_logs_request_params ON action_logs USING GIN (request_params);
CREATE INDEX IF NOT EXISTS idx_action_logs_query_params ON action_logs USING GIN (query_params);

-- Function to log an action
CREATE OR REPLACE FUNCTION log_action(
    p_user_uuid UUID,
    p_character_uuid UUID,
    p_token_type VARCHAR(20),
    p_action_name VARCHAR(255),
    p_action_class VARCHAR(500),
    p_http_method VARCHAR(10),
    p_request_path VARCHAR(1000),
    p_request_params JSONB DEFAULT NULL,
    p_query_params JSONB DEFAULT NULL,
    p_status action_status DEFAULT 'success',
    p_http_status_code INTEGER DEFAULT 200,
    p_response_message TEXT DEFAULT NULL,
    p_ip_address INET DEFAULT NULL,
    p_user_agent TEXT DEFAULT NULL,
    p_execution_time_ms INTEGER DEFAULT NULL
)
RETURNS UUID AS $$
DECLARE
    v_log_uuid UUID;
BEGIN
    INSERT INTO action_logs (
        user_uuid,
        character_uuid,
        token_type,
        action_name,
        action_class,
        http_method,
        request_path,
        request_params,
        query_params,
        status,
        http_status_code,
        response_message,
        ip_address,
        user_agent,
        execution_time_ms
    ) VALUES (
        p_user_uuid,
        p_character_uuid,
        p_token_type,
        p_action_name,
        p_action_class,
        p_http_method,
        p_request_path,
        p_request_params,
        p_query_params,
        p_status,
        p_http_status_code,
        p_response_message,
        p_ip_address,
        p_user_agent,
        p_execution_time_ms
    ) RETURNING uuid INTO v_log_uuid;

    RETURN v_log_uuid;
END;
$$ LANGUAGE plpgsql;

-- Function to get user action history
CREATE OR REPLACE FUNCTION get_user_action_logs(
    p_user_uuid UUID,
    p_limit INTEGER DEFAULT 100,
    p_offset INTEGER DEFAULT 0
)
RETURNS TABLE(
    log_uuid UUID,
    character_uuid UUID,
    token_type VARCHAR,
    action_name VARCHAR,
    http_method VARCHAR,
    request_path VARCHAR,
    status action_status,
    http_status_code INTEGER,
    response_message TEXT,
    ip_address INET,
    execution_time_ms INTEGER,
    created_at TIMESTAMPTZ
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        al.uuid,
        al.character_uuid,
        al.token_type,
        al.action_name,
        al.http_method,
        al.request_path,
        al.status,
        al.http_status_code,
        al.response_message,
        al.ip_address,
        al.execution_time_ms,
        al.created_at
    FROM action_logs al
    WHERE al.user_uuid = p_user_uuid
    ORDER BY al.created_at DESC
    LIMIT p_limit
    OFFSET p_offset;
END;
$$ LANGUAGE plpgsql;

-- Function to get failed action attempts for security monitoring
CREATE OR REPLACE FUNCTION get_failed_action_attempts(
    p_hours_back INTEGER DEFAULT 24,
    p_limit INTEGER DEFAULT 100
)
RETURNS TABLE(
    log_uuid UUID,
    user_uuid UUID,
    character_uuid UUID,
    action_name VARCHAR,
    http_method VARCHAR,
    request_path VARCHAR,
    status action_status,
    http_status_code INTEGER,
    ip_address INET,
    attempt_count BIGINT,
    first_attempt TIMESTAMPTZ,
    last_attempt TIMESTAMPTZ
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        al.uuid,
        al.user_uuid,
        al.character_uuid,
        al.action_name,
        al.http_method,
        al.request_path,
        al.status,
        al.http_status_code,
        al.ip_address,
        COUNT(*) OVER (PARTITION BY al.user_uuid, al.action_name) as attempt_count,
        MIN(al.created_at) OVER (PARTITION BY al.user_uuid, al.action_name) as first_attempt,
        MAX(al.created_at) OVER (PARTITION BY al.user_uuid, al.action_name) as last_attempt
    FROM action_logs al
    WHERE al.status IN ('failure', 'forbidden', 'error')
      AND al.created_at >= CURRENT_TIMESTAMP - (p_hours_back || ' hours')::INTERVAL
    ORDER BY al.created_at DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

-- Function to get action statistics
CREATE OR REPLACE FUNCTION get_action_statistics(
    p_user_uuid UUID DEFAULT NULL,
    p_hours_back INTEGER DEFAULT 24
)
RETURNS TABLE(
    action_name VARCHAR,
    total_calls BIGINT,
    success_count BIGINT,
    failure_count BIGINT,
    forbidden_count BIGINT,
    error_count BIGINT,
    avg_execution_time_ms NUMERIC
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        al.action_name,
        COUNT(*) as total_calls,
        COUNT(*) FILTER (WHERE al.status = 'success') as success_count,
        COUNT(*) FILTER (WHERE al.status = 'failure') as failure_count,
        COUNT(*) FILTER (WHERE al.status = 'forbidden') as forbidden_count,
        COUNT(*) FILTER (WHERE al.status = 'error') as error_count,
        ROUND(AVG(al.execution_time_ms), 2) as avg_execution_time_ms
    FROM action_logs al
    WHERE (p_user_uuid IS NULL OR al.user_uuid = p_user_uuid)
      AND al.created_at >= CURRENT_TIMESTAMP - (p_hours_back || ' hours')::INTERVAL
    GROUP BY al.action_name
    ORDER BY total_calls DESC;
END;
$$ LANGUAGE plpgsql;

-- Function to cleanup old action logs (data retention policy)
CREATE OR REPLACE FUNCTION cleanup_old_action_logs(p_days_to_keep INTEGER DEFAULT 90)
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM action_logs
    WHERE created_at < CURRENT_TIMESTAMP - (p_days_to_keep || ' days')::INTERVAL;

    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

-- Function to get suspicious activity (multiple failed attempts from same IP)
CREATE OR REPLACE FUNCTION get_suspicious_activity(
    p_hours_back INTEGER DEFAULT 24,
    p_min_attempts INTEGER DEFAULT 5
)
RETURNS TABLE(
    ip_address INET,
    user_uuid UUID,
    failed_attempts BIGINT,
    unique_actions BIGINT,
    first_attempt TIMESTAMPTZ,
    last_attempt TIMESTAMPTZ,
    sample_action VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        al.ip_address,
        al.user_uuid,
        COUNT(*) as failed_attempts,
        COUNT(DISTINCT al.action_name) as unique_actions,
        MIN(al.created_at) as first_attempt,
        MAX(al.created_at) as last_attempt,
        MIN(al.action_name) as sample_action
    FROM action_logs al
    WHERE al.status IN ('failure', 'forbidden', 'error')
      AND al.created_at >= CURRENT_TIMESTAMP - (p_hours_back || ' hours')::INTERVAL
      AND al.ip_address IS NOT NULL
    GROUP BY al.ip_address, al.user_uuid
    HAVING COUNT(*) >= p_min_attempts
    ORDER BY failed_attempts DESC;
END;
$$ LANGUAGE plpgsql;

-- Comment on table
COMMENT ON TABLE action_logs IS 'Comprehensive logging of all user actions for security, auditing, and analytics';
COMMENT ON COLUMN action_logs.user_uuid IS 'UUID of the user performing the action';
COMMENT ON COLUMN action_logs.character_uuid IS 'UUID of the character if action performed with character token';
COMMENT ON COLUMN action_logs.token_type IS 'Type of token used (admin, application, user, character)';
COMMENT ON COLUMN action_logs.action_name IS 'Human-readable name of the action';
COMMENT ON COLUMN action_logs.action_class IS 'Fully qualified class name of the action';
COMMENT ON COLUMN action_logs.request_params IS 'Sanitized request parameters (sensitive data removed)';
COMMENT ON COLUMN action_logs.execution_time_ms IS 'Action execution time in milliseconds';
