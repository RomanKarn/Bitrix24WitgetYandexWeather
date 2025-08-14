<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Config\Option;
use MyCom\YandexWeather\YandexWeatherClient;

class VendorWeatherWidgetComponent extends CBitrixComponent
{
    const TRANSLATE_CONDITION = [
        'clear' => 'ясно',
        'partly-cloudy' => 'малооблачно',
        'cloudy' => 'облачно с прояснениями',
        'overcast' => 'пасмурно',
        'light-rain' => 'небольшой дождь',
        'rain' => 'дождь',
        'heavy-rain' => 'сильный дождь',
        'showers' => 'ливень',
        'wet-snow' => 'дождь со снегом',
        'light-snow' => 'небольшой снег',
        'snow' => 'снег',
        'snow-showers' => 'снегопад',
        'hail' => 'град',
        'thunderstorm' => 'гроза',
        'thunderstorm-with-rain' => 'дождь с грозой',
        'thunderstorm-with-hail' => 'гроза с градом',
    ];

    public function executeComponent()
    {

        $params = $this->refactParams();

        if (empty($params['API_KEY'])) {
            $this->arResult['ERROR'] = 'API ключ не задан в настройках модуля';
            $this->includeComponentTemplate();
            return;
        }

        $cacheId = md5(serialize([
            $params['API_KEY'],
            $params['LAT'],
            $params['LON']
        ]));

        if ($this->startResultCache($this->arParams['CACHE_TIME'], $cacheId)) {
            try {

                $client = new YandexWeatherClient($params['API_KEY']);

                $res = $client->getCurrentWeather($params['LAT'], $params['LON'], $params['LANG']);

                $fact = $res['fact'] ?? [];

                $fact['condition'] = $this->translateCondition($fact['condition']);

                $this->arResult['WEATHER'] = [
                    'temp' => isset($fact['temp']) ? (int)$fact['temp'] : null,
                    'feels_like' => isset($fact['feels_like']) ? (int)$fact['feels_like'] : null,
                    'condition' => $fact['condition'] ?? null,
                    'icon' => $fact['icon'] ?? null,
                    'humidity' => $fact['humidity'] ?? null,
                    'wind_speed' => $fact['wind_speed'] ?? null,
                    'raw' => $res['raw'] ?? null,
                    'updated' => $res['now'] ?? time(),
                ];

                $this->setResultCacheKeys(['WEATHER']);
                $this->includeComponentTemplate();
            } catch (\Exception $e) {
                $this->abortResultCache();
                $this->arResult['ERROR'] = $e->getMessage();
                $this->includeComponentTemplate();
            }
        }
    }

    private function refactParams()
    {
        $params['API_KEY'] = trim($this->arParams['API_KEY'] ?? Option::get('mycom.yandexweather', 'api_key', ''));
        $params['LAT'] = $this->arParams['LAT'] ?? Option::get('mycom.yandexweather', 'lat', '55.7558');
        $params['LON'] = $this->arParams['LON'] ?? Option::get('mycom.yandexweather', 'lon', '37.6173');
        $params['LANG'] = $this->arParams['LANG'] ?? 'ru_RU';

        return $params;
    }
    private function translateCondition($condition)
    {
        if($condition !== null)
        {
            $condition = self::TRANSLATE_CONDITION[$condition] ?? $condition;
        }
        return $condition;
    }
}