<?php
namespace MyCom\YandexWeather\UI\Witget;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

class UIRightExtenshionWeather
{
    public static function handleOnEpilog(): void
    {

        if (Context::getCurrent()->getRequest()->isAdminSection()) {
            return;
        }

        if (empty($componentOutput) && isset($GLOBALS['APPLICATION'])) {
            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'mycom:yandexweather.witget',
                '.default',
                [
                    'CACHE_TIME' => 1800,
                    'SHOW_ERRORS' => 'Y'
                ]
            );
            $componentOutput = ob_get_clean();
        }

        $moduleId = 'mycom.yandexweather';
        $defaultOptions = [
            ['id' => 'sidebar', 'selector' => '.sidebar-pulse-block'],
        ];
        $stored = Option::get($moduleId, 'placements', json_encode($defaultOptions, JSON_UNESCAPED_UNICODE));
        $placements = json_decode($stored, true);
        if (!is_array($placements) || count($placements) === 0) {
            return;
        }

        $payload = [
            'html' => $componentOutput,
            'placements' => $placements
        ];

        try {
            $jsPayload  = Json::encode($payload);

            Asset::getInstance()->addString(
                <<<HTML
                <script>
                BX.ready(function() {
                    try {
                        var data = {$jsPayload};
                        var widgetHtml = data.html;
                        var placements = data.placements || [];
                        var naberId = 0;
                        placements.forEach(function(p) {
                            naberId++;
                            const sidebar = document.getElementById(p.id);
                            
                            if (!sidebar) {
                                console.warn('Weather widget: Sidebar not found');
                                return;
                            }  
                            
                            if(p.selector) {
                                const pulse = sidebar.querySelector(p.selector);
                                
                                if (!pulse) {
                                    console.warn('Weather widget: Pulse block not found');
                                    return;
                                }
                                
                                const weatherContainer = document.createElement('div');
                                weatherContainer.id = 'yandex-weather-' + naberId;
                                weatherContainer.innerHTML = widgetHtml;
                                
                                // Добавляем после pulse блока
                                pulse.parentNode.insertBefore(weatherContainer, pulse.nextSibling);
                                
                                console.log('Weather widget initialized');
                            }
                            else {
                                const weatherContainer = document.createElement('div');
                                weatherContainer.id = 'yandex-weather-' + naberId;
                                weatherContainer.innerHTML = widgetHtml;
                                
                                sidebar.appendChild(weatherContainer);
                                
                                console.log('Weather widget initialized');
                            }
                        });
                    } catch (e) {
                        console.error('Weather widget error:', e);
                    }
                });
                </script>
                HTML
            );

        } catch (\Exception $e) {
            DebugSession::debugLog("JSON encode error: " . $e->getMessage());
        }
    }
}