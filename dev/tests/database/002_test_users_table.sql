-- Test users table structure and functionality

BEGIN;

-- Plan the number of tests
SELECT plan(12);

-- Test table existence
SELECT has_table('users', 'users table should exist');
SELECT has_table('user_passwords', 'user_passwords table should exist');

-- Test columns
SELECT has_column('users', 'id', 'users table should have id column');
SELECT has_column('users', 'uuid', 'users table should have uuid column');
SELECT has_column('users', 'email', 'users table should have email column');

SELECT has_column('user_passwords', 'id', 'user_passwords table should have id column');
SELECT has_column('user_passwords', 'user_id', 'user_passwords table should have user_id column');
SELECT has_column('user_passwords', 'password', 'user_passwords table should have password column');
SELECT has_column('user_passwords', 'created_at', 'user_passwords table should have created_at column');

-- Test constraints
SELECT has_pk('users', 'users table should have primary key');
SELECT has_pk('user_passwords', 'user_passwords table should have primary key');

-- Test function existence
SELECT has_function('register_user', ARRAY['character varying', 'text'], 'register_user function should exist');

SELECT * FROM finish();
ROLLBACK;