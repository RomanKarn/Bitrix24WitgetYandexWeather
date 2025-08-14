<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$weather = $arResult['WEATHER'] ?? null;
?>
<div class="b24-app-block b24-app-desktop">
    <? if (!empty($arResult['ERROR'])): ?>
        <div class="b-weather-error"><?= htmlspecialchars($arResult['ERROR']) ?></div>
    <? elseif ($weather): ?>
        <div class="b24-app-block-header">
            <div class="b-weather-temp"><?= htmlspecialchars($weather['temp']) ?>&deg;C  <?= htmlspecialchars($weather['condition']) ?></div>
        </div>
        <div class="b24-app-block-content">
            <span>Ощущается: <?= htmlspecialchars($weather['feels_like']) ?>&deg;</span><br>
            <span>Влажность: <?= htmlspecialchars($weather['humidity']) ?>%</span><br>
            <span>Ветер: <?= htmlspecialchars($weather['wind_speed']) ?> м/с</span>
        </div>
    <? else: ?>
        <div>Данные недоступны</div>
    <? endif; ?>
</div>