<?php
/**
 * Ventana show_from / show_until de slides (académico, centro, galería).
 *
 * Mínimo 1 hora, máximo 6 meses. Lo aplican los modelos en create/update y
 * el formulario PHP vía applyFromPost() (validity_mode del POST).
 */
class ContentValidity {

    public const MIN_SECONDS = 3600;

    public const MAX_MONTHS = 6;

    /**
     * Suma una vigencia relativa (misma semántica que el panel Svelte).
     *
     * @throws InvalidArgumentException
     */
    public static function addDuration(int $amount, string $unit, ?DateTimeImmutable $from = null): DateTimeImmutable {
        $from = $from ?? new DateTimeImmutable('now');
        $n = max(0, $amount);
        return match ($unit) {
            'hours' => $from->modify('+' . $n . ' hours'),
            'days' => $from->modify('+' . $n . ' days'),
            'months' => $from->modify('+' . $n . ' months'),
            default => throw new InvalidArgumentException('Unidad de vigencia inválida'),
        };
    }

    /** True si show_until está en el pasado (el slide no sale en la TV). */
    public static function isExpired(?string $until): bool {
        if ($until === null || trim($until) === '') {
            return false;
        }
        $ts = strtotime($until);
        return $ts !== false && $ts < time();
    }

    /**
     * Interpreta validity_mode / amount / unit del formulario PHP sobre $data.
     *
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     */
    public static function applyFromPost(array &$data, ?string $previousUntil = null): void {
        $mode = (string) ($_POST['validity_mode'] ?? 'none');
        if ($mode === 'keep') {
            if (self::isExpired($previousUntil)) {
                throw new InvalidArgumentException(
                    'La vigencia ya venció. Elija «Sin límite de tiempo» o defina un nuevo tiempo de publicación para que se muestre en la TV.'
                );
            }
            unset($data['show_until'], $data['show_from']);
            return;
        }
        if ($mode === 'none') {
            $data['show_until'] = '';
            return;
        }
        if ($mode !== 'new') {
            unset($data['show_until']);
            return;
        }
        $amount = (int) ($_POST['validity_amount'] ?? 0);
        $unit = (string) ($_POST['validity_unit'] ?? 'days');
        if ($amount < 1) {
            throw new InvalidArgumentException('La vigencia mínima es 1 hora');
        }
        $until = self::addDuration($amount, $unit);
        $iso = $until->format('c');
        self::assertShowUntil($iso, $previousUntil);
        $data['show_until'] = $iso;
    }

    /**
     * Parsea datetime a ISO 8601 o null si vacío/inválido.
     */
    public static function parseDateTime(?string $dt): ?string {
        if ($dt === null || trim($dt) === '') {
            return null;
        }
        $ts = strtotime($dt);
        return $ts !== false ? date('c', $ts) : null;
    }

    /**
     * Valida rango de show_until.
     * Si $previousIso coincide (mismo instante), se omite el mínimo de 1 h
     * para permitir guardar sin tocar una vigencia que ya le queda menos de 1 h.
     *
     * @throws InvalidArgumentException
     */
    public static function assertShowUntil(?string $iso, ?string $previousIso = null): void {
        if ($iso === null || $iso === '') {
            return;
        }

        $ts = strtotime($iso);
        if ($ts === false) {
            throw new InvalidArgumentException('Fecha de vigencia inválida');
        }

        $now = time();
        $max = (new DateTimeImmutable('now'))->modify('+' . self::MAX_MONTHS . ' months')->getTimestamp();

        if ($ts > $max) {
            throw new InvalidArgumentException('La vigencia máxima es 6 meses');
        }

        $prevTs = $previousIso !== null && $previousIso !== '' ? strtotime($previousIso) : false;
        $unchanged = $prevTs !== false && abs($prevTs - $ts) < 2;
        if ($unchanged) {
            return;
        }

        if ($ts < $now + self::MIN_SECONDS) {
            throw new InvalidArgumentException('La vigencia mínima es 1 hora');
        }
    }

    /**
     * Normaliza show_from / show_until en un payload create/update.
     * Cadenas vacías → null (quita el límite).
     *
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     */
    public static function applyShowWindow(array &$data, ?string $previousUntil = null): void {
        if (array_key_exists('show_from', $data)) {
            $parsed = self::parseDateTime(is_string($data['show_from']) ? $data['show_from'] : null);
            $data['show_from'] = $parsed;
        }

        if (array_key_exists('show_until', $data)) {
            $raw = $data['show_until'];
            if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                $data['show_until'] = null;
            } else {
                $parsed = self::parseDateTime(is_string($raw) ? $raw : (string) $raw);
                if ($parsed === null) {
                    throw new InvalidArgumentException('Fecha de vigencia inválida');
                }
                self::assertShowUntil($parsed, $previousUntil);
                $data['show_until'] = $parsed;
            }
        }
    }
}
