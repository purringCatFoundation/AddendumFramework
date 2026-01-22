-- pgTAP setup and configuration
-- This file sets up the pgTAP testing framework

-- Install pgTAP extension
CREATE EXTENSION IF NOT EXISTS pgtap;

-- Set up test plan
BEGIN;

-- Test that pgTAP is working
SELECT plan(1);
SELECT pass('pgTAP is installed and working');

SELECT * FROM finish();
ROLLBACK;