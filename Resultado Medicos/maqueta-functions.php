<?php
function calcularPosicion($valor, $min, $max) {
    $rango = $max - $min;
    $visualMin = $min - ($rango * 0.5);
    $visualMax = $max + ($rango * 0.5);

    if ($valor < $visualMin) $valor = $visualMin;
    if ($valor > $visualMax) $valor = $visualMax;

    return (($valor - $visualMin) / ($visualMax - $visualMin)) * 100;
}

function estadoResultado($valor, $min, $max) {
    if ($valor < $min) return "bajo";
    if ($valor > $max) return "alto";
    return "normal";
}

