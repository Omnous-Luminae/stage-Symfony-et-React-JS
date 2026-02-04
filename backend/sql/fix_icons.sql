SET NAMES utf8mb4;
UPDATE incident_categories SET icon = '🖥️' WHERE code = 'hardware';
UPDATE incident_categories SET icon = '💾' WHERE code = 'software';
UPDATE incident_categories SET icon = '🌐' WHERE code = 'network';
UPDATE incident_categories SET icon = '🏢' WHERE code = 'infrastructure';
UPDATE incident_categories SET icon = '🔒' WHERE code = 'security';
UPDATE incident_categories SET icon = '📋' WHERE code = 'other';
