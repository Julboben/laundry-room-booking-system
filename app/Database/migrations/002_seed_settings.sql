INSERT INTO settings (setting_key, setting_value)
VALUES
    ('booking_weeks_ahead', '8'),
    ('takeover_rule_minutes', '30'),
    ('slot_07-10_start', '07:00'),
    ('slot_07-10_end', '10:00'),
    ('slot_10-13_start', '10:00'),
    ('slot_10-13_end', '13:00'),
    ('slot_13-16_start', '13:00'),
    ('slot_13-16_end', '16:00'),
    ('slot_16-19_start', '16:00'),
    ('slot_16-19_end', '19:00'),
    ('calendar_message', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
