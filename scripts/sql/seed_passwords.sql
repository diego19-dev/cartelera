-- =============================================================
--  Script de generación de hashes de contraseñas reales
--  Ejecutar una vez después de la instalación
-- =============================================================
-- php -r "echo password_hash('TuPassword', PASSWORD_BCRYPT);"
--
-- Luego UPDATE users SET password_hash = '...' WHERE username = '...';

-- Genera los hashes reales y luego los reemplaza:

/*
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'admin';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'jp_empresarial';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'jp_salud';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'jp_tecnologia';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'jp_hoteleria';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'jp_turismo';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'jp_barismo';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'jp_eventos';
UPDATE users SET password_hash = '<HASH_PHP_BCRYPT>' WHERE username = 'comunicaciones';
*/