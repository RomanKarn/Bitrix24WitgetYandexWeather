<?php
namespace MyCom\YandexWeather\Setting;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

$moduleId = 'mycom.yandexweather';
$request = Application::getInstance()->getContext()->getRequest();

$defaultOptions = [
    ['id' => 'sidebar', 'selector' => '.sidebar-pulse-block'],
];

$stored = Option::get($moduleId, 'placements', json_encode($defaultOptions, JSON_UNESCAPED_UNICODE));
$placements = json_decode($stored, true) ?: [];

// Обработка сохранения формы
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('save') == 'Y') {
    $newPlacements = [];

    // Собираем данные из формы
    $ids = $request->getPost('placement_id') ?: [];
    $selectors = $request->getPost('placement_selector') ?: [];

    // Обрабатываем каждую пару значений
    foreach ($ids as $index => $id) {
        $id = trim($id);
        $selector = trim($selectors[$index] ?? '');

        // Сохраняем только заполненные строки
        if (!empty($id)) {
            $newPlacements[] = [
                'id' => $id,
                'selector' => $selector
            ];
        }
    }

    // Сохраняем новые данные
    Option::set($moduleId, 'placements', json_encode($newPlacements, JSON_UNESCAPED_UNICODE));

    // Правильный редирект с сохранением параметров
    $redirectUrl = $APPLICATION->GetCurPage() . "?lang=" . LANGUAGE_ID . "&mid=" . $moduleId;
    LocalRedirect($redirectUrl);
    exit;
}

$sDocTitle = "Настройки размещения виджета погоды";
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <form method="post" action="<?= htmlspecialchars($APPLICATION->GetCurPage()) ?>?lang=<?= LANGUAGE_ID ?>&mid=<?= $moduleId ?>">
        <?= bitrix_sessid_post(); ?>
        <input type="hidden" name="save" value="Y">

        <h2>Настройки размещения виджета погоды</h2>

        <table class="placement-table" id="placement-table">
            <thead>
            <tr>
                <th width="40%">ID Элемента</th>
                <th width="55%">CSS-селектор элемента</th>
                <th width="5%">Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($placements as $index => $placement): ?>
                <tr>
                    <td>
                        <input type="text"
                               name="placement_id[]"
                               value="<?= htmlspecialchars($placement['id']) ?>"
                               placeholder="ID родителя в который будет вставляться виджет">
                    </td>
                    <td>
                        <input type="text"
                               name="placement_selector[]"
                               value="<?= htmlspecialchars($placement['selector']) ?>"
                               placeholder="Класс объекта после которого будет вставляться виджет">
                    </td>
                    <td>
                        <?php if ($index < count($placements)): ?>
                            <span class="remove-placement" onclick="removePlacement(this)">×</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <button type="button" class="adm-btn add-placement-btn" onclick="addPlacement()">
            + Добавить новую строку
        </button>

        <br><br>
        <input type="submit" value="Сохранить" class="adm-btn-save">
    </form>

    <script>
        // Добавление новой строки
        function addPlacement() {
            const tbody = $('#placement-table tbody');
            const newRow = `
            <tr>
                <td>
                    <input type="text"
                           name="placement_id[]"
                           value=""
                           placeholder="Уникальный идентификатор">
                </td>
                <td>
                    <input type="text"
                           name="placement_selector[]"
                           value=""
                           placeholder=".class-name или #element-id">
                </td>
                <td>
                    <span class="remove-placement" onclick="removePlacement(this)">×</span>
                </td>
            </tr>
        `;
            tbody.append(newRow);
        }

        // Удаление строки
        function removePlacement(element) {
            $(element).closest('tr').remove();
        }

        // Автоматическое удаление пустых строк при отправке формы
        $('form').on('submit', function() {
            $('#placement-table tbody tr').each(function() {
                const idInput = $(this).find('input[name="placement_id[]"]');

                if (idInput.val().trim() === '') {
                    $(this).remove();
                }
            });
        });
    </script>

    <style>
        .placement-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .placement-table th {
            text-align: left;
            padding: 10px;
            background: #f0f0f0;
        }
        .placement-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .placement-table input {
            width: 95%;
            padding: 8px;
            box-sizing: border-box;
        }
        .add-placement-btn {
            margin: 10px 0;
        }
        .remove-placement {
            color: #ff0000;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
        }
    </style>

    <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'); ?>