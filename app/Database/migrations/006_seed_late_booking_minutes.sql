INSERT INTO settings (setting_key, setting_value)
VALUES ('late_booking_minutes', '120')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
