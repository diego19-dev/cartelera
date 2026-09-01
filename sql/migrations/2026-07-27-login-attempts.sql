-- Rate limiting real de login (por IP, en BD — no por sesión PHP).
-- Ver AUDITORIA_SEGURIDAD_CSET.md (C3) y PLAN_BLINDAJE_CSET.md (E3).
CREATE TABLE IF NOT EXISTS login_attempts
(
    id              BIGSERIAL PRIMARY KEY,
    ip_address      VARCHAR(45) NOT NULL,
    username        VARCHAR(50),
    success         BOOLEAN NOT NULL,
    failure_reason  VARCHAR(30),
    user_agent      VARCHAR(255),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_created ON login_attempts(ip_address, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_login_attempts_username_created ON login_attempts(username, created_at DESC);
