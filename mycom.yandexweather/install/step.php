<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<h2>Настройка Yandex</h2>

<form method="post" action="<?= $APPLICATION->GetCurPageParam('', ['install','uninstall','mode']) ?>">
    <?= bitrix_sessid_post(); ?>
    <input type="hidden" name="install" value="Y" />
    <input type="hidden" name="STEP" value="save" />
    <input type="hidden" name="id" value="<?= htmlspecialchars($moduleId ?? 'mycom.yandexweather') ?>" />

    <table class="adm-detail-content-table edit-table" style="width: 100%;">
        <tr>
            <td width="40%"><label for="api_key">API ключ Яндекс.Погоды</label></td>
            <td>
                <input type="text" id="api_key" name="API_KEY" value="<?php echo htmlspecialchars($_CURRENT_API); ?>" style="width: 100%;" />
                <div class="adm-info-message">Получите ключ в кабинете разработчика Яндекс.Погоды.</div>
            </td>
        </tr>

        <tr>
            <td><label for="lat">Широта</label></td>
            <td><input type="text" id="lat" name="LAT" value="<?php echo htmlspecialchars($_CURRENT_LAT); ?>" /></td>
        </tr>

        <tr>
            <td><label for="lon">Долгота</label></td>
            <td><input type="text" id="lon" name="LON" value="<?php echo htmlspecialchars($_CURRENT_LON); ?>" /></td>
        </tr>

        <tr>
            <td>Пропустить проверку ключа</td>
            <td>
                <label><input type="checkbox" name="SKIP_VALIDATION" value="Y" /> Сохранить ключ без проверки (не рекомендуется)</label>
            </td>
        </tr>
    </table>

    <br/>

    <input type="submit" value="Сохранить и завершить установку" class="adm-btn-save" />
</form>