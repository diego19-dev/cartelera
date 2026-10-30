<?php
/**
 * Cron CLI: refresca indicadores externos en general_content (upsertExternal).
 *
 * Divisas, cripto, café, petróleo, clima. Crontab típico 06:00 America/Bogota:
 *   0 6 * * * php /var/www/html/Cartelera/cron/refresh_external.php >> logs/cron-refresh.log 2>&1
 */
require_once __DIR__ . '/bootstrap.php';

set_time_limit(300);
ini_set('display_errors', '0');
ini_set('user_agent', 'CSET-TV/1.0');

$log = [];
$ok = 0;
$fail = 0;

function externalFetch(string $url, int $timeout = 15): ?array {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'header'  => "User-Agent: Mozilla/5.0 CSET-TV/1.0\r\nAccept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        error_log("externalFetch failed: $url");
        return null;
    }
    $json = json_decode($data, true);
    return is_array($json) ? $json : null;
}

/** Precio y variación % vs cierre previo (Yahoo Finance chart). */
function yahooQuote(string $symbol): ?array {
    $data = externalFetch(
        "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1d&range=1d",
        20
    );
    $meta = $data['chart']['result'][0]['meta'] ?? null;
    if (!$meta || !isset($meta['regularMarketPrice'])) {
        return null;
    }
    $price = (float) $meta['regularMarketPrice'];
    $prev  = (float) ($meta['chartPreviousClose'] ?? $price);
    $chg   = $prev > 0 ? (($price - $prev) / $prev) * 100 : 0.0;
    return ['price' => $price, 'change' => $chg];
}

function formatPct(float $chg): string {
    return ($chg >= 0 ? '+' : '') . number_format($chg, 2) . '%';
}

function externalRun(string $name, callable $fn) {
    global $log, $ok, $fail;
    echo "[$name] ";
    try {
        $result = $fn();
        if ($result === true || (is_int($result) && $result > 0)) {
            echo "OK\n";
            $log[] = "$name: OK";
            $ok++;
        } else {
            echo "Sin cambios\n";
            $log[] = "$name: sin cambios";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        $log[] = "$name: ERROR - " . $e->getMessage();
        $fail++;
    }
}

// =============================================================
// 1) DIVISAS (USD a varias monedas)
// =============================================================
externalRun('Divisas USD', function() {
    $data = externalFetch('https://open.er-api.com/v6/latest/USD');
    if (!$data || !isset($data['rates'])) return false;

    $rates = $data['rates'];
    $top = ['COP', 'EUR', 'GBP', 'BRL', 'MXN', 'PEN', 'ARS', 'CLP', 'CAD', 'JPY'];
    $result = [];
    foreach ($top as $code) {
        if (isset($rates[$code])) {
            $result[$code] = round($rates[$code], 2);
        }
    }
    if (empty($result)) return false;

    GeneralContent::upsertExternal('currency', 'Cotización del Dólar (USD)', $result,
        'https://www.exchangerate-api.com');

    // TRM dedicado: el slide debe mostrar precio, no solo fecha/fuente
    if (isset($result['COP'])) {
        GeneralContent::upsertExternal('currency', 'TRM Colombia (Peso Colombiano)', [
            'COP por USD' => number_format((float) $result['COP'], 2, '.', ','),
            'Fecha'       => date('Y-m-d'),
            'Fuente'      => 'Tipo de cambio referencia (USD→COP)',
        ], 'https://www.banrep.gov.co/');
    }
    return true;
});

// =============================================================
// 2) CRIPTOMONEDAS
// =============================================================
externalRun('Criptomonedas', function() {
    $data = externalFetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum&vs_currencies=usd&include_24hr_change=true');
    if (!$data) return false;

    $result = [];
    if (isset($data['bitcoin'])) {
        $result['Bitcoin'] = [
            'USD'    => '$' . number_format($data['bitcoin']['usd'], 2),
            'Var 24h' => round($data['bitcoin']['usd_24h_change'] ?? 0, 2) . '%',
        ];
    }
    if (isset($data['ethereum'])) {
        $result['Ethereum'] = [
            'USD'    => '$' . number_format($data['ethereum']['usd'], 2),
            'Var 24h' => round($data['ethereum']['usd_24h_change'] ?? 0, 2) . '%',
        ];
    }
    if (empty($result)) return false;

    GeneralContent::upsertExternal('currency', 'Criptomonedas', $result, 'https://www.coingecko.com');
    return true;
});

// =============================================================
// 3) Café (precio internacional - USD por libra, Yahoo KC=F en centavos)
// =============================================================
externalRun('Café', function() {
    $quote = yahooQuote('KC=F');
    if (!$quote) return false;

    $usdLb = $quote['price'] / 100.0;
    $result = [
        'Precio USD/lb' => number_format($usdLb, 2, '.', ''),
        'Tendencia 24h' => formatPct($quote['change']),
        'Mercado'       => 'New York (Arábica)',
        'Actualizado'   => date('Y-m-d H:i'),
        'Fuente'        => 'ICE Futures (KC=F)',
    ];

    // Título canónico con tilde; alias legacy se desactivan en upsertExternal
    GeneralContent::upsertExternal('commodity', 'Café (Arábica) - Precio Internacional', $result,
        'https://finance.yahoo.com/quote/KC=F');
    return true;
});

// =============================================================
// 4) Petróleo (WTI y Brent vía Yahoo Finance)
// =============================================================
externalRun('Petróleo', function() {
    $wti = yahooQuote('CL=F');
    $brent = yahooQuote('BZ=F');
    if (!$wti || !$brent) return false;

    $avgChg = ($wti['change'] + $brent['change']) / 2;
    $result = [
        'WTI USD/barril'   => number_format($wti['price'], 2, '.', ''),
        'Brent USD/barril' => number_format($brent['price'], 2, '.', ''),
        'OPEP USD/barril'  => number_format(($wti['price'] + $brent['price']) / 2, 2, '.', ''),
        'Variación'        => formatPct($avgChg),
        'Actualizado'      => date('Y-m-d H:i'),
        'Fuente'           => 'Yahoo Finance / NYMEX',
    ];

    GeneralContent::upsertExternal('commodity', 'Petróleo - Precios WTI y Brent', $result,
        'https://finance.yahoo.com/quote/CL=F');
    return true;
});

// =============================================================
// 5) CLIMA (Open-Meteo, sin API key)
// =============================================================
externalRun('Clima', function() {
    $lat = 7.1193;
    $lon = -73.1227;
    $city = 'Bucaramanga';

    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true";
    $data = externalFetch($url);
    if (!$data || !isset($data['current_weather'])) return false;

    $cw = $data['current_weather'];
    $weatherCodes = [
        0 => 'Despejado', 1 => 'Mayormente despejado', 2 => 'Parcialmente nublado',
        3 => 'Nublado', 45 => 'Niebla', 48 => 'Niebla con escarcha',
        51 => 'Llovizna ligera', 53 => 'Llovizna moderada', 55 => 'Llovizna intensa',
        61 => 'Lluvia ligera', 63 => 'Lluvia moderada', 65 => 'Lluvia intensa',
        71 => 'Nieve ligera', 73 => 'Nieve moderada', 75 => 'Nieve intensa',
        80 => 'Chubascos', 81 => 'Chubascos', 82 => 'Chubascos intensos',
        95 => 'Tormenta eléctrica',
    ];

    $result = [
        'Ciudad'      => $city,
        'Temperatura' => round($cw['temperature'], 1) . ' °C',
        'Viento'      => $cw['windspeed'] . ' km/h',
        'Condición'   => $weatherCodes[$cw['weathercode']] ?? 'Desconocido',
        'Actualizado' => date('Y-m-d H:i', strtotime($cw['time'])),
    ];

    GeneralContent::upsertExternal('weather', 'Clima actual - ' . $city, $result,
        'https://open-meteo.com');
    return true;
});

// =============================================================
// 6) LIMPIEZA
// =============================================================
externalRun('Limpieza sesiones', function() {
    $deleted = Database::getInstance()->query(
        "DELETE FROM user_sessions WHERE expires_at < now()"
    )->rowCount();
    return $deleted >= 0;
});

// =============================================================
// RESUMEN
// =============================================================
$summary = "\n--- REFRESH EXTERNO " . date('Y-m-d H:i:s') . " ---\n";
$summary .= "OK: $ok  FAIL: $fail\n";
foreach ($log as $l) {
    $summary .= "  - $l\n";
}

file_put_contents(LOG_PATH . '/external-refresh.log', $summary, FILE_APPEND | LOCK_EX);

echo "\n========================================\n";
echo "Finalizado. OK=$ok, FAIL=$fail\n";
echo "Hora: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n";