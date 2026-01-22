-- Security Audit Logs
-- Tracks security-relevant events for compliance and security monitoring

CREATE TABLE IF NOT EXISTS security_audit_logs (
    id SERIAL PRIMARY KEY,
    event VARCHAR(100) NOT NULL,
    user_uuid UUID,
    resource_type VARCHAR(50),
    resource_uuid UUID,
    ip_address INET,
    user_agent TEXT,
    metadata JSONB,
    success BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for common queries
CREATE INDEX idx_security_audit_logs_event ON security_audit_logs(event);
CREATE INDEX idx_security_audit_logs_user_uuid ON security_audit_logs(user_uuid);
CREATE INDEX idx_security_audit_logs_created_at ON security_audit_logs(created_at DESC);
CREATE INDEX idx_security_audit_logs_resource ON security_audit_logs(resource_type, resource_uuid);
CREATE INDEX idx_security_audit_logs_success ON security_audit_logs(success) WHERE success = false;

-- Partial index for failed events (security monitoring)
CREATE INDEX idx_security_audit_logs_failed_events ON security_audit_logs(event, created_at DESC)
WHERE success = false;

-- Comments
COMMENT ON TABLE security_audit_logs IS 'Security audit trail for compliance and monitoring';
COMMENT ON COLUMN security_audit_logs.event IS 'Event type (e.g., auth.login, character.deleted)';
COMMENT ON COLUMN security_audit_logs.user_uuid IS 'User who performed the action (NULL for pre-auth events)';
COMMENT ON COLUMN security_audit_logs.resource_type IS 'Type of resource affected (character, object, etc)';
COMMENT ON COLUMN security_audit_logs.resource_uuid IS 'UUID of the affected resource';
COMMENT ON COLUMN security_audit_logs.metadata IS 'Additional event-specific data';
COMMENT ON COLUMN security_audit_logs.success IS 'Whether the action succeeded';
