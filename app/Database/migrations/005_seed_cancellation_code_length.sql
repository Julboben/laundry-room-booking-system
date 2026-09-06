INSERT INTO settings (setting_key, setting_value)
VALUES ('cancellation_code_length', '4')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
