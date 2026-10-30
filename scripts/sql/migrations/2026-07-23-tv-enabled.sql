-- Interruptor maestro de la cartelera (zona de peligro en Configuración)
INSERT INTO display_config (config_key, config_value, description)
VALUES
  ('tv_enabled', '1', 'Cartelera encendida (1) o apagada (0)'),
  ('tv_off_message', 'Cartelera apagada', 'Mensaje cuando la cartelera está apagada')
ON CONFLICT (config_key) DO NOTHING;
