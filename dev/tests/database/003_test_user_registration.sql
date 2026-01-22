-- Test user registration functionality

BEGIN;

SELECT plan(8);

-- Test register_user function
PREPARE test_email AS SELECT 'test@example.com'::VARCHAR;
PREPARE test_password AS SELECT 'password123'::TEXT;

-- Test successful user registration
SELECT lives_ok(
    'SELECT register_user(''test@example.com'', ''password123'')',
    'register_user should execute without error'
);

-- Check if user was created
SELECT ok(
    (SELECT COUNT(*) FROM users WHERE email = 'test@example.com') = 1,
    'User should be created in users table'
);

-- Check if password was stored
SELECT ok(
    (SELECT COUNT(*) FROM user_passwords up
     JOIN users u ON u.id = up.user_id
     WHERE u.email = 'test@example.com') = 1,
    'Password should be stored in user_passwords table'
);

-- Test UUID generation
SELECT ok(
    (SELECT uuid FROM users WHERE email = 'test@example.com') IS NOT NULL,
    'UUID should be generated for new user'
);

-- Test email uniqueness constraint
SELECT throws_ok(
    'SELECT register_user(''test@example.com'', ''different_password'')',
    '23505',  -- unique violation error code
    NULL,
    'Duplicate email should throw unique constraint violation'
);

-- Test function return values
SELECT ok(
    (SELECT email FROM register_user('test2@example.com', 'password456')) = 'test2@example.com',
    'register_user should return correct email'
);

SELECT ok(
    (SELECT uuid FROM register_user('test3@example.com', 'password789')) IS NOT NULL,
    'register_user should return generated UUID'
);

-- Test data integrity
SELECT ok(
    (SELECT COUNT(*) FROM users u
     JOIN user_passwords up ON u.id = up.user_id
     WHERE u.email = 'test2@example.com' AND up.password = 'password456') = 1,
    'User and password data should be properly linked'
);

SELECT * FROM finish();
ROLLBACK;