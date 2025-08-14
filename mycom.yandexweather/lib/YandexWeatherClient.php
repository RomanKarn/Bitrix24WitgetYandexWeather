<?php

namespace MyCom\YandexWeather;

use Bitrix\Main\Web\HttpClient;
class YandexWeatherClient
{
    protected string $apiKey;
    protected int $timeout;
    protected int $retries;

    public function __construct(string $apiKey, array $opts = [])
    {
        $this->apiKey = trim($apiKey);
        $this->timeout = intval($opts['timeout'] ?? 5);
        $this->retries = max(0, intval($opts['retries'] ?? 1));
        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('Yandex API key is empty');
        }
    }

    protected function buildUrl(string $lat, string $lon, string $lang = 'ru_RU'): string
    {
        $lat = rawurlencode($lat);
        $lon = rawurlencode($lon);
        return "https://api.weather.yandex.ru/v2/forecast?lat={$lat}&lon={$lon}&lang={$lang}";
    }

    public function getCurrentWeather(string $lat, string $lon, string $lang = 'ru_RU'): array
    {
        $url = $this->buildUrl($lat, $lon, $lang);

        $lastException = null;
        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            $http = new HttpClient();
            $http->setHeader('X-Yandex-Weather-Key', $this->apiKey);
            $http->setTimeout($this->timeout);
            $http->disableSslVerification(false);

            $response = $http->get($url);
            $status = (int)$http->getStatus();

            if ($response === false) {
                $lastException = new \RuntimeException("HTTP request failed (attempt {$attempt})");
                continue;
            }

            if ($status !== 200) {
                $lastException = new \RuntimeException("Yandex API returned status {$status}");
                continue;
            }

            $data = json_decode($response, true);
            if (!is_array($data)) {
                $lastException = new \RuntimeException("Invalid JSON from Yandex");
                continue;
            }

            if (!isset($data['fact'])) {
                $lastException = new \RuntimeException("Yandex response missing 'fact' node");
                continue;
            }

            return [
                'raw' => $data,
                'fact' => $data['fact'],
                'now' => $data['now'] ?? null,
                'info' => $data['info'] ?? null
            ];
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new \RuntimeException("Unknown error fetching Yandex weather");
    }

}