<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$defaultApiKey = '';
if (\Bitrix\Main\Loader::includeModule('main')) {
    $defaultApiKey = Option::get('yandexweather.witget', 'api_key', '');
}
$arComponentParameters = array(
    "GROUPS" => array(
        "SETTINGS" => array(
            "NAME" => "Настройки Yandex weather",
        ),
        "DISPLAY" => array(
            "NAME" => "Отображение",
        )
    ),
    "PARAMETERS" => array(

        "API_KEY" => array(
            "PARENT" => "SETTINGS",
            "NAME" => "API Key",
            "TYPE" => "STRING",
            "DEFAULT" => $defaultApiKey,
            "REFRESH" => "N",
            "DESCRIPTION" => "Если пусто — берётся из настроек модуля"
        ),

        "LAT" => array(
            "PARENT" => "SETTINGS",
            "NAME" => "Широта",
            "TYPE" => "STRING",
            "DEFAULT" => "55.7558",
        ),
        "LON" => array(
            "PARENT" => "SETTINGS",
            "NAME" => "Долгота",
            "TYPE" => "STRING",
            "DEFAULT" => "37.6173",
        ),

        "LANG" => array(
            "PARENT" => "SETTINGS",
            "NAME" => "Язык ответа",
            "TYPE" => "STRING",
            "DEFAULT" => "ru_RU",
        ),

        "CACHE_TIME" => array(
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => "Время кэша",
            "DEFAULT" => 1800,
        ),

    )
);