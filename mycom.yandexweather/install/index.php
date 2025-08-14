<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log.txt");

class DebugSession
{
    public static function debugLog($message, $data = null)
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'data' => $data
        ];

        file_put_contents(LOG_FILENAME, $message  ."\n", FILE_APPEND);
    }
}

class mycom_yandexweather extends CModule
{

    var $MODULE_ID = 'mycom.yandexweather';
    var $MODULE_NAME = 'YandexWeather';
    var $MODULE_DESCRIPTION = 'Погода в Москве';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $PARTNER_NAME = 'mycom';
    var $PARTNER_URI = 'https://mycompany.com';

   public function __construct()
   {
       $arModuleVersion = array();
       include __DIR__ . '/version.php';

       $this->MODULE_VERSION = $arModuleVersion['VERSION'];
       $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
   }

   public function InstallFiles()
   {
       $documentRoot = $_SERVER['DOCUMENT_ROOT'];

       $sourceComponents = __DIR__ . '\components';
       $targetComponents = $documentRoot . "/local/components";

       // Копирование компонтов
       if (is_dir($sourceComponents))
       {
           if (!is_dir($targetComponents)) {
               if (!mkdir($targetComponents, 0755, true)) {
                   DebugSession::debugLog("Failed to create directory: $targetComponents");
               }
           }

           $sourceFiles = scandir($sourceComponents);

           CopyDirFiles($sourceComponents, $targetComponents, true, true);
       }

       // Копирование настроек
       $sourceAdmin = __DIR__ . "\..\admin";
       $targetAdmin = $documentRoot . "/bitrix/admin";
       if (is_dir($sourceAdmin))
       {
           CopyDirFiles($sourceAdmin, $targetAdmin, true, true);
       }

       return true;
   }

    public function UnInstallFiles()
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];

        $componentPath = $documentRoot . "/local/components/mycom/yandexweather.witget";
        if (is_dir($componentPath))
        {
            \Bitrix\Main\IO\Directory::deleteDirectory($componentPath);
        }

        // Удаляем admin-скрипты
        $adminFile = $documentRoot."/bitrix/admin/mycom_yandexweather_settings.php";
        if (file_exists($adminFile))
        {
            @unlink($adminFile);
        }
        return true;
    }

    public function DoInstall()
    {
        global $APPLICATION;

        $error = null;
        $posted = false;
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $request = $context->getRequest();

        if ($request->isPost() && check_bitrix_sessid() && $request['STEP'] === 'save')
        {
            $posted = true;
            $apiKey = trim($_POST['API_KEY'] ?? '');
            $lat = trim($_POST['LAT'] ?? Option::get($this->MODULE_ID, 'lat', '55.7558'));
            $lon = trim($_POST['LON'] ?? Option::get($this->MODULE_ID, 'lon', '37.6173'));
            $skip_validation = isset($_POST['SKIP_VALIDATION']) && $_POST['SKIP_VALIDATION'] === 'Y';

            if ($apiKey === '')
            {

                $error = "API ключ не может быть пустым.";
                DebugSession::debugLog($error);
            }
            else
            {
                if (!$skip_validation)
                {
                    try
                    {

                        $clientPath = __DIR__ . '\..\lib\YandexWeatherClient.php';
                        if (!is_file($clientPath)) {

                            $error = "Внутренняя ошибка: YandexWeatherClient не найден. Путь: ". $clientPath .  " Проверьте расположение файлов модуля.";
                            DebugSession::debugLog($error);
                        }
                        require_once $clientPath;

                        $client = new \MyCom\YandexWeather\YandexWeatherClient($apiKey, ['timeout' => 5, 'retries' => 0]);

                        $client->getCurrentWeather($lat, $lon, 'ru_RU');

                    }
                    catch (\Exception $e)
                    {
                        $error = "Проверка ключа не пройдена: " . htmlspecialchars($e->getMessage());
                        DebugSession::debugLog($error);
                    }
                }

                if (empty($error))
                {
                    Option::set($this->MODULE_ID, 'api_key', $apiKey);
                    Option::set($this->MODULE_ID, 'lat', $lat);
                    Option::set($this->MODULE_ID, 'lon', $lon);

                    if (!ModuleManager::isModuleInstalled($this->MODULE_ID)) {
                        ModuleManager::registerModule($this->MODULE_ID);

                        if (!ModuleManager::isModuleInstalled($this->MODULE_ID)) {
                            $error = "Ошибка регистрации модуля!";
                            DebugSession::debugLog($error);
                        }
                    } else {
                        DebugSession::debugLog("Module already registered");
                    }

                    $this->InstallFiles();

                    $eventManager = EventManager::getInstance();
                    $eventManager->registerEventHandler(
                        'main',
                        'OnEpilog',
                        $this->MODULE_ID,
                        '\MyCom\YandexWeather\UI\Witget\UIRightExtenshionWeather',
                        'handleOnEpilog'
                    );
                    $handlers = $eventManager->findEventHandlers('main', 'OnEpilog');
                    $registered = false;

                    foreach ($handlers as $handler) {
                        if ($handler['TO_CLASS'] === '\\MyCom\\YandexWeather\\UI\\Witget\\UIRightExtenshionWeather'
                            && $handler['TO_METHOD'] === 'handleOnEpilog') {
                            $registered = true;
                            break;
                        }
                    }

                    DebugSession::debugLog("Event handler registered: " . ($registered ? "yes" : "no"));


                    $APPLICATION->IncludeAdminFile( "Установка модуля " . $this->MODULE_ID, __DIR__ . "/step_finish.php");
                    return true;
                }
            }
        }
        else {
            $currentApi = \Bitrix\Main\Config\Option::get($this->MODULE_ID, 'api_key', '');
            $currentLat = \Bitrix\Main\Config\Option::get($this->MODULE_ID, 'lat', '55.7558');
            $currentLon = \Bitrix\Main\Config\Option::get($this->MODULE_ID, 'lon', '37.6173');

            $_STEP_ERROR = $error;
            $_STEP_POSTED = $posted;
            $_CURRENT_API = $currentApi;
            $_CURRENT_LAT = $currentLat;
            $_CURRENT_LON = $currentLon;

            $APPLICATION->IncludeAdminFile("Установка модуля " . $this->MODULE_ID, __DIR__ . "/step.php");
            return true;
        }
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            '\MyCom\YandexWeather\UI\Withet\UIRightExtenshionWeather',
            'handleOnEpilog'
        );

        $this->UnInstallFiles();

        Option::delete($this->MODULE_ID);

        if (ModuleManager::isModuleInstalled($this->MODULE_ID)) {
            ModuleManager::unRegisterModule($this->MODULE_ID);

            if (ModuleManager::isModuleInstalled($this->MODULE_ID)) {
                $error = "Ошибка удаления модуля!";
                DebugSession::debugLog($error);
            }
        } else {
            DebugSession::debugLog("Module not registered");
        }


        $APPLICATION->IncludeAdminFile("Удаление модуля ".$this->MODULE_ID, __DIR__."/unstep1.php");
        return true;
    }

}