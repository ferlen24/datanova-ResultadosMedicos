<?php
require __DIR__ . '/maqueta-data.php';
require __DIR__ . '/maqueta-functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Análisis - Demo</title>
    <link rel="stylesheet" href="maqueta.css">

</head>
<body>

<div class="header">
    <img src="images/logo.svg" alt="Logo" class="header-logo" />
    <div class="header-text">
        <h1>-  Demo Medicos </h1>
        <p>Vista de ejemplo sin conexión a base de datos</p>
    </div>
</div>

<div class="contenedor">
    <?php foreach ($analisis as $index => $item): ?>
        <?php
            $posicion = calcularPosicion($item["resultado"], $item["ref_min"], $item["ref_max"]);
            $estado = estadoResultado($item["resultado"], $item["ref_min"], $item["ref_max"]);
        ?>
        <div class="card">
            <div class="card-top">
                <div class="titulo"><?= htmlspecialchars($item["nombre"]) ?></div>
                <div class="subtitulo"><?= htmlspecialchars($item["equipo"]) ?></div>
            </div>

            <div class="card-body">
                <div class="resultado-box">
                    <div class="label">Resultado</div>
                    <div class="valor">
                        <?= htmlspecialchars($item["resultado"]) ?>
                        <small><?= htmlspecialchars($item["unidad"]) ?></small>
                    </div>

                    <div class="estado <?= $estado ?>">
                        <?= $estado === 'normal' ? 'Dentro de los valores normales' : ($estado === 'alto' ? 'Valor alto' : 'Valor bajo') ?>
                    </div>
                </div>

                <div class="historia-box">
                    <div class="label">Historia</div>
                    <div class="historia-visual" tabindex="0">
                        <div class="historia-track historia-svg-wrap">
                        <?php
                        // Preparar puntos de historia: soporta formatos:
                        // - valor numérico (se le asigna fecha: hoy - (n-index-1) días)
                        // - array asociativo ['fecha' => 'YYYY-MM-DD', 'valor' => X]
                        // - array indexado [fecha, valor]
                        $historyRaw = isset($item["historia"]) && is_array($item["historia"]) ? $item["historia"] : array();
                        $fechaRealizacion = isset($item["fecha_realizacion"]) && is_array($item["fecha_realizacion"]) ? $item["fecha_realizacion"] : array();
                        $points = array();
                        if (count($historyRaw) === 0) {
                            echo '<div class="hist-empty">-</div>';
                        } else {
                            $n = count($historyRaw);
                            foreach ($historyRaw as $hIndex => $hValue) {
                                $ts = null;
                                $val = null;

                                if (is_array($hValue)) {
                                    // formato asociativo
                                    if (isset($hValue['fecha']) && isset($hValue['valor'])) {
                                        $ts = strtotime($hValue['fecha']);
                                        $val = floatval($hValue['valor']);
                                    } elseif (isset($hValue[0]) && isset($hValue[1])) {
                                        $ts = strtotime($hValue[0]);
                                        $val = floatval($hValue[1]);
                                    }
                                } else {
                                    // solo valor: asignar fecha relativa (hoy - (n-index-1) días)
                                    $val = floatval($hValue);
                                    if (!empty($fechaRealizacion) && isset($fechaRealizacion[$hIndex])) {
                                        $ts = strtotime($fechaRealizacion[$hIndex]);
                                        if ($ts === false) $ts = null;
                                    }
                                    if ($ts === null) {
                                        $daysAgo = ($n - $hIndex - 1);
                                        $ts = strtotime("-{$daysAgo} days");
                                    }
                                }

                                if ($ts === false || $ts === null) $ts = time();
                                if ($val === null) $val = 0;

                                $points[] = array('t' => $ts, 'v' => $val);
                            }

                            // calcular rangos
                            $tsVals = array_column($points, 't');
                            $vVals = array_column($points, 'v');
                            $minT = min($tsVals);
                            $maxT = max($tsVals);
                            $minV = min($vVals);
                            $maxV = max($vVals);

                            // ajustar rangos si son iguales
                            if ($minT == $maxT) { $minT -= 86400; $maxT += 86400; }
                            if ($minV == $maxV) { $minV -= 1; $maxV += 1; }

                            // dimensiones SVG
                            $w = 360; $h = 120;
                            $padL = 40; $padR = 12; $padT = 10; $padB = 30;
                            $innerW = $w - $padL - $padR;
                            $innerH = $h - $padT - $padB;

                            // generar coordenadas
                            $polyPoints = array();
                            $circles = array();
                            foreach ($points as $p) {
                                $x = $padL + (($p['t'] - $minT) / ($maxT - $minT)) * $innerW;
                                $y = $padT + (1 - (($p['v'] - $minV) / ($maxV - $minV))) * $innerH;
                                $polyPoints[] = $x . ',' . $y;
                                $circles[] = array('x' => $x, 'y' => $y, 'v' => $p['v'], 't' => $p['t']);
                            }

                            // etiquetas
                            $dateFmtMin = date('Y-m-d', $minT);
                            $dateFmtMax = date('Y-m-d', $maxT);
                            $valFmtMin = $minV;
                            $valFmtMax = $maxV;

                            // output SVG
                            ?>
                            <svg viewBox="0 0 <?= $w ?> <?= $h ?>" width="100%" height="120" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Gráfico de historia">
                                <!-- ejes -->
                                <line x1="<?= $padL ?>" y1="<?= $padT ?>" x2="<?= $padL ?>" y2="<?= ($h - $padB) ?>" stroke="#ddd" stroke-width="1" />
                                <line x1="<?= $padL ?>" y1="<?= ($h - $padB) ?>" x2="<?= ($w - $padR) ?>" y2="<?= ($h - $padB) ?>" stroke="#ddd" stroke-width="1" />

                                <!-- labels valores -->
                                <text x="8" y="<?= $padT + 6 ?>" font-size="11" fill="#666"><?= htmlspecialchars((string)$valFmtMax) ?></text>
                                <text x="8" y="<?= ($h - $padB) ?>" font-size="11" fill="#666"><?= htmlspecialchars((string)$valFmtMin) ?></text>

                                <!-- labels fechas -->
                                <text x="<?= $padL ?>" y="<?= ($h - 8) ?>" font-size="11" fill="#666"><?= htmlspecialchars($dateFmtMin) ?></text>
                                <text x="<?= ($w - $padR - 60) ?>" y="<?= ($h - 8) ?>" font-size="11" fill="#666"><?= htmlspecialchars($dateFmtMax) ?></text>

                                <!-- polyline -->
                                <polyline fill="none" stroke="var(--accent-color)" stroke-width="2" points="<?= implode(' ', $polyPoints) ?>" stroke-linejoin="round" stroke-linecap="round" />

                                <!-- puntos -->
                                <?php foreach ($circles as $c): ?>
                                    <circle cx="<?= $c['x'] ?>" cy="<?= $c['y'] ?>" r="4" fill="#fff" stroke="var(--accent-color)" stroke-width="2">
                                        <title><?= htmlspecialchars(date('Y-m-d', $c['t']) . ' — ' . $c['v']) ?></title>
                                    </circle>
                                <?php endforeach; ?>
                            </svg>
                        <?php } ?>
                    </div>
                    <div class="historia-modal-overlay" aria-hidden="true">
                        <div class="historia-modal-content" role="dialog" aria-modal="true">
                            <div class="historia-modal-header">
                                <div class="historia-modal-title">Historia <?= htmlspecialchars($item["nombre"]) ?></div>
                                <button type="button" class="historia-modal-close" aria-label="Cerrar">
                                    &times;
                                </button>
                            </div>

                            <?php if (count($points) === 0): ?>
                                <div class="hist-empty">-</div>
                            <?php else: ?>
                                <div class="historia-modal-graph">
                                    <svg class="historia-modal-svg" viewBox="0 0 <?= $w ?> <?= $h ?>" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Gráfico de historia">
                                        <line x1="<?= $padL ?>" y1="<?= $padT ?>" x2="<?= $padL ?>" y2="<?= ($h - $padB) ?>" stroke="#ddd" stroke-width="1" />
                                        <line x1="<?= $padL ?>" y1="<?= ($h - $padB) ?>" x2="<?= ($w - $padR) ?>" y2="<?= ($h - $padB) ?>" stroke="#ddd" stroke-width="1" />

                                        <text x="8" y="<?= $padT + 6 ?>" font-size="11" fill="#666"><?= htmlspecialchars((string)$valFmtMax) ?></text>
                                        <text x="8" y="<?= ($h - $padB) ?>" font-size="11" fill="#666"><?= htmlspecialchars((string)$valFmtMin) ?></text>

                                        <text x="<?= $padL ?>" y="<?= ($h - 8) ?>" font-size="11" fill="#666"><?= htmlspecialchars($dateFmtMin) ?></text>
                                        <text x="<?= ($w - $padR - 60) ?>" y="<?= ($h - 8) ?>" font-size="11" fill="#666"><?= htmlspecialchars($dateFmtMax) ?></text>

                                        <polyline fill="none" stroke="var(--accent-color)" stroke-width="2" points="<?= implode(' ', $polyPoints) ?>" stroke-linejoin="round" stroke-linecap="round" />

                                        <?php foreach ($circles as $c): ?>
                                            <circle cx="<?= $c['x'] ?>" cy="<?= $c['y'] ?>" r="4" fill="#fff" stroke="var(--accent-color)" stroke-width="2">
                                                <title><?= htmlspecialchars(date('Y-m-d', $c['t']) . ' — ' . $c['v']) ?></title>
                                            </circle>
                                        <?php endforeach; ?>
                                    </svg>
                                </div>

                                <div class="historia-modal-table-wrap">
                                    <table class="historia-table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Valor (<?= htmlspecialchars($item["unidad"]) ?>)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($points as $p): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(date('Y-m-d', $p['t'])) ?></td>
                                                    <td><?= htmlspecialchars((string)$p['v']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="barra-wrap">
                <div class="gauge">
                    <div class="marker js-marker" data-pos="<?= round($posicion, 2) ?>">
                        <div class="marker-bubble"><?= htmlspecialchars($item["resultado"]) ?></div>
                    </div>

                    <div class="barra">
                        <div class="segmento bajo">
                            0 a <?= $item["ref_min"] ?>
                        </div>
                        <div class="segmento normal">
                            <?= $item["ref_min"] ?> a <?= $item["ref_max"] ?>
                        </div>
                        <div class="segmento alto">
                            &gt; <?= $item["ref_max"] ?>
                        </div>
                    </div>

                    <div class="referencia">REFERENCIA</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="maqueta.js" defer></script>
<!--
document.addEventListener("DOMContentLoaded", function () {
    const markers = document.querySelectorAll(".js-marker");

    markers.forEach((marker, index) => {
        const pos = parseFloat(marker.dataset.pos) || 50;
        marker.style.left = "0%";

        setTimeout(() => {
            marker.style.left = pos + "%";
        }, 200 + (index * 180));
    });
});
});
-->

</body>
</html>