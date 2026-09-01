<?php
/**
 * Script para insertar/actualizar indicadores financieros manualmente
 * Uso (mantenimiento, no público): php tools/seed_indicators.php
 *
 * Carga los indicadores principales que no tienen API gratuita:
 * - Café (precio USD/libra)
 * - Petróleo WTI / Brent
 * - TRM Colombia (si difiere del dólar general)
 */

require_once __DIR__ . '/src/Core/bootstrap.php';

$message = '';
$error = '';
$indicators = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $indicators = $_POST['indicators'] ?? [];
    $updated = 0;
    $errors = [];

    foreach ($indicators as $key => $data) {
        try {
            $category = $data['category'] ?? null;
            $title    = $data['title'] ?? null;
            $payload  = $data['data'] ?? [];
            $source   = $data['source'] ?? null;

            // Solo campos con valor; no borrar precios previos si el input queda vacío
            $incoming = array_filter($payload, fn($v) => $v !== '' && $v !== null);
            $existingRow = Database::getInstance()->fetch(
                'SELECT external_data FROM general_content WHERE category = ? AND title = ?',
                [$category, $title]
            );
            $previous = [];
            if (!empty($existingRow['external_data'])) {
                $decoded = json_decode($existingRow['external_data'], true);
                if (is_array($decoded)) {
                    $previous = $decoded;
                }
            }
            $payload = array_merge($previous, $incoming);

            if ($category && $title && !empty($payload)) {
                GeneralContent::upsertExternal($category, $title, $payload, $source);
                $updated++;
            }
        } catch (Exception $e) {
            $errors[] = "$title: " . $e->getMessage();
        }
    }

    if ($updated > 0) {
        $message = "✓ Se actualizaron $updated indicadores.";
    }
    if (!empty($errors)) {
        $error = 'Errores: ' . implode('; ', $errors);
    }
}

// Cargar valores actuales desde la BD
$db = Database::getInstance();
$currentIndicators = [];
$rows = $db->fetchAll(
    "SELECT id, category, title, description, external_data, source_url
     FROM general_content
     WHERE is_external = TRUE AND is_active = TRUE
     ORDER BY category, title"
);
foreach ($rows as $r) {
    if (!empty($r['external_data']) && is_string($r['external_data'])) {
        $decoded = json_decode($r['external_data'], true);
        $r['external_data'] = is_array($decoded) ? $decoded : [];
    } elseif (!is_array($r['external_data'] ?? null)) {
        $r['external_data'] = [];
    }
    $currentIndicators[$r['title']] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CSET - Indicadores Financieros</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: white;
            padding: 24px 32px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 8px 0 0; opacity: 0.9; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .card h2 {
            font-size: 18px;
            color: #1e3a8a;
            margin: 0 0 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .form-grid label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .form-grid input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-grid input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 8px;
        }
        .btn:hover { background: #2563eb; }
        .btn-secondary { background: #64748b; }
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-info    { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
        .source-link { font-size: 12px; color: #64748b; font-style: italic; }
        .full-width { grid-column: 1 / -1; }
        .actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .help-box {
            background: #eff6ff;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #1e40af;
        }
        .help-box strong { color: #1e3a8a; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💰 Indicadores Financieros</h1>
        <p>Actualización manual de indicadores que se mostrarán en las TVs</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="help-box">
        <strong>💡 Instrucciones:</strong>
        <ul>
            <li>Los valores se actualizan manualmente aquí y se muestran automáticamente en la TV</li>
            <li>También se ejecuta el cron (<code>cron/refresh_external.php</code>) que trae dólar, cripto y clima desde APIs gratuitas</li>
            <li>Café y petróleo también se actualizan solos a las 6:00 vía cron (Yahoo Finance)</li>
            <li>Los campos vacíos se ignoran (no se guardan)</li>
        </ul>
    </div>

    <form method="POST">
        <div class="card">
            <h2>☕ Café (Arábica) - Precio Internacional</h2>
            <input type="hidden" name="indicators[cafe][category]" value="commodity">
            <input type="hidden" name="indicators[cafe][title]" value="Café (Arábica) - Precio Internacional">
            <input type="hidden" name="indicators[cafe][source]" value="https://finance.yahoo.com/quote/KC=F">
            <?php
              $cafeData = $currentIndicators['Café (Arábica) - Precio Internacional']['external_data'] ?? [];
            ?>
            <div class="form-grid">
                <div>
                    <label>Precio USD por libra</label>
                    <input type="text" name="indicators[cafe][data][Precio USD/lb]"
                           value="<?= htmlspecialchars($cafeData['Precio USD/lb'] ?? '') ?>"
                           placeholder="2.45">
                </div>
                <div>
                    <label>Tendencia (24h)</label>
                    <input type="text" name="indicators[cafe][data][Tendencia 24h]"
                           value="<?= htmlspecialchars($cafeData['Tendencia 24h'] ?? '') ?>"
                           placeholder="+0.5%">
                </div>
                <div>
                    <label>Precio COP por carga</label>
                    <input type="text" name="indicators[cafe][data][COP por carga]"
                           value="<?= htmlspecialchars($cafeData['COP por carga'] ?? '') ?>"
                           placeholder="2.350.000">
                </div>
                <div>
                    <label>Mercado</label>
                    <input type="text" name="indicators[cafe][data][Mercado]"
                           value="<?= htmlspecialchars($cafeData['Mercado'] ?? 'New York (Arábica)') ?>"
                           placeholder="New York (Arábica)">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>🛢️ Petróleo - Precios Internacionales</h2>
            <input type="hidden" name="indicators[oil][category]" value="commodity">
            <input type="hidden" name="indicators[oil][title]" value="Petróleo - Precios WTI y Brent">
            <input type="hidden" name="indicators[oil][source]" value="https://www.eia.gov/petroleum/">
            <?php
              $oilData = $currentIndicators['Petróleo - Precios WTI y Brent']['external_data'] ?? [];
              $trmData = $currentIndicators['TRM Colombia (Peso Colombiano)']['external_data'] ?? [];
            ?>
            <div class="form-grid">
                <div>
                    <label>WTI (USD/barril)</label>
                    <input type="text" name="indicators[oil][data][WTI USD/barril]"
                           value="<?= htmlspecialchars($oilData['WTI USD/barril'] ?? '') ?>"
                           placeholder="78.50">
                </div>
                <div>
                    <label>Brent (USD/barril)</label>
                    <input type="text" name="indicators[oil][data][Brent USD/barril]"
                           value="<?= htmlspecialchars($oilData['Brent USD/barril'] ?? '') ?>"
                           placeholder="82.30">
                </div>
                <div>
                    <label>OPEP (USD/barril)</label>
                    <input type="text" name="indicators[oil][data][OPEP USD/barril]"
                           value="<?= htmlspecialchars($oilData['OPEP USD/barril'] ?? '') ?>"
                           placeholder="80.10">
                </div>
                <div>
                    <label>Variación diaria</label>
                    <input type="text" name="indicators[oil][data][Variación]"
                           value="<?= htmlspecialchars($oilData['Variación'] ?? '') ?>"
                           placeholder="-0.3%">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>🇨🇴 TRM Colombia (si difiere del dólar general)</h2>
            <input type="hidden" name="indicators[trm][category]" value="currency">
            <input type="hidden" name="indicators[trm][title]" value="TRM Colombia (Peso Colombiano)">
            <input type="hidden" name="indicators[trm][source]" value="https://www.banrep.gov.co/">
            <div class="form-grid">
                <div>
                    <label>TRM hoy (COP por USD)</label>
                    <input type="text" name="indicators[trm][data][COP por USD]"
                           value="<?= htmlspecialchars($trmData['COP por USD'] ?? '') ?>"
                           placeholder="4.250">
                </div>
                <div>
                    <label>Variación</label>
                    <input type="text" name="indicators[trm][data][Variación]"
                           value="<?= htmlspecialchars($trmData['Variación'] ?? '') ?>"
                           placeholder="+0.2%">
                </div>
                <div>
                    <label>Fecha</label>
                    <input type="date" name="indicators[trm][data][Fecha]"
                           value="<?= htmlspecialchars($trmData['Fecha'] ?? date('Y-m-d')) ?>">
                </div>
                <div>
                    <label>Fuente</label>
                    <input type="text" name="indicators[trm][data][Fuente]"
                           value="<?= htmlspecialchars($trmData['Fuente'] ?? 'Banco de la República') ?>"
                           placeholder="Banco de la República">
                </div>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn">💾 Actualizar indicadores</button>
            <a href="public/admin/dashboard.php" class="btn btn-secondary">← Volver al panel</a>
        </div>
    </form>

    <div class="card">
        <h2>📊 Indicadores cargados automáticamente</h2>
        <p class="source-link">Estos se actualizan vía cron (<code>cron/refresh_external.php</code>):</p>
        <table style="width:100%; border-collapse:collapse; margin-top:12px;">
            <tr style="background:#f8fafc;text-align:left;">
                <th style="padding:8px;">Categoría</th>
                <th style="padding:8px;">Título</th>
                <th style="padding:8px;">Datos</th>
            </tr>
            <?php
            $autoCategories = ['currency', 'weather'];
            foreach ($currentIndicators as $title => $ind):
                if (in_array($ind['category'], $autoCategories) && stripos($title, 'Café') === false && stripos($title, 'Cafe') === false && stripos($title, 'Petróleo') === false && stripos($title, 'Petroleo') === false && strpos($title, 'TRM') === false):
                    $data = is_array($ind['external_data']) ? json_encode($ind['external_data'], JSON_UNESCAPED_UNICODE) : $ind['external_data'];
                    $dataShort = strlen($data) > 100 ? substr($data, 0, 100) . '...' : $data;
            ?>
            <tr style="border-bottom:1px solid #e2e8f0;">
                <td style="padding:8px;"><span class="badge badge-info"><?= htmlspecialchars($ind['category']) ?></span></td>
                <td style="padding:8px;"><?= htmlspecialchars($title) ?></td>
                <td style="padding:8px;font-size:12px;font-family:monospace;color:#475569;"><?= htmlspecialchars($dataShort) ?></td>
            </tr>
            <?php endif; endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>