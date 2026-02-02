UPDATE users SET password = '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6' WHERE username = 'admin@construction.com';
UPDATE users SET password = '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6' WHERE user_level = 'worker';
SELECT username, LENGTH(password) as pass_length FROM users;