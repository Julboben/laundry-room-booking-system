INSERT INTO settings (setting_key, setting_value)
VALUES ('weather_postcode', '1352')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
