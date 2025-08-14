<?php

CModule::AddAutoloadClasses(
    "mycom.yandexweather",
    [
        "MyCom\\YandexWeather\\YandexWeatherClient" => "lib/YandexWeatherClient.php",
        "MyCom\\YandexWeather\\UI\\Witget\\UIRightExtenshionWeather" => "lib/UIRightExtenshionWeather.php",
    ]
);
