ALTER TABLE pconfig ADD updated datetime NOT NULL DEFAULT '0001-01-01 00:00:00';
ALTER TABLE pconfig ADD INDEX pconfig_updated (updated);
