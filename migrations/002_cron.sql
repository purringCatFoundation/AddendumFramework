CREATE TABLE IF NOT EXISTS cron (
    code VARCHAR PRIMARY KEY,
    expression VARCHAR NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS cron_schedule (
    id SERIAL PRIMARY KEY,
    code VARCHAR NOT NULL REFERENCES cron(code),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    scheduled_at TIMESTAMPTZ NOT NULL,
    started_at TIMESTAMPTZ,
    ended_at TIMESTAMPTZ,
    status VARCHAR NOT NULL,
    UNIQUE (code, scheduled_at)
);

CREATE OR REPLACE FUNCTION cron_get_scheduled_jobs(p_code VARCHAR DEFAULT NULL)
RETURNS TABLE(id INT, code VARCHAR)
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE cron_schedule cs
    SET status = 'cancelled', ended_at = NOW()
    WHERE status = 'pending'
      AND cs.code IN (SELECT c.code FROM cron c WHERE c.enabled = FALSE);

    RETURN QUERY
    WITH ranked AS (
        SELECT cs.id, cs.code,
               ROW_NUMBER() OVER (PARTITION BY cs.code ORDER BY cs.scheduled_at) AS rn
        FROM cron_schedule cs
        JOIN cron c ON c.code = cs.code
        WHERE cs.status = 'pending'
          AND cs.scheduled_at <= NOW()
          AND c.enabled = TRUE
          AND (p_code IS NULL OR cs.code = p_code)
    ),
    omitted AS (
        UPDATE cron_schedule cs
        SET status = 'omitted', ended_at = NOW()
        FROM ranked r
        WHERE cs.id = r.id AND r.rn > 1
        RETURNING cs.id
    )
    SELECT ranked.id, ranked.code FROM ranked WHERE rn = 1;
END;
$$;
