<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Списки");

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

global $USER;
if (!$USER->isAuthorized()) {
    LocalRedirect(SITE_DIR . 'auth/');
}
Loader::includeModule('highloadblock');
?>

    <div class="wrapper-fluid">
        <div class="wrapper">
            <?
            function getDataClass($tableName)
            {
                $hl = HL\HighloadBlockTable::getList([
                    'filter' => ['=TABLE_NAME' => $tableName]
                ])->fetch();
                if (!$hl) die("HL не найден: " . $tableName);
                $entity = HL\HighloadBlockTable::compileEntity($hl);
                return $entity->getDataClass();
            }

            $DC_G = getDataClass('uf_insights_groups');
            $DC_I = getDataClass('uf_insights_items');

            $userId = (int)$USER->GetID();
            $groups = $DC_G::getList([
                'filter' => ['=UF_OWNER_ID' => $userId],
                'select' => ['ID', 'UF_NAME', 'UF_COLOR'],
                'order' => ['ID' => 'ASC']
            ])->fetchAll();

            if ($groups) {
                foreach ($groups as $key => $g) {
                    $gid = (int)$g['ID'];
                    $color = htmlspecialchars($g['UF_COLOR'] ?: '#ccc');
                    $name = htmlspecialchars($g['UF_NAME'] ?: 'Без названия');

                    // --- инсайты этой группы ---
                    $items = $DC_I::getList([
                        'filter' => ['=UF_OWNER_ID' => $userId, '=UF_GROUP_ID' => $gid],
                        'select' => ['ID', 'UF_TITLE', 'UF_TEXT', 'UF_TAGS', 'UF_IS_PINNED', 'UF_CREATED_AT'],
                        'order' => ['UF_IS_PINNED' => 'DESC', 'UF_CREATED_AT' => 'DESC']
                    ])->fetchAll();

                    $groups[$key]['ITEMS'] = $items;
                }
            }
            ?>

            <div class="read-lists">
                <? foreach ($groups as $group): ?>
                    <? if (!empty($group['ITEMS'])): ?>
                        <div class="read-list panel">
                            <div class="read-list-title">
                                <?= $group['UF_NAME'] ?>
                            </div>
                            <ul class="read-list-ul">
                                <? foreach ($group['ITEMS'] as $item): ?>
                                    <li class="read-list-item">
                                        <div class="read-list-item-inner">
                                            <div class="read-list-item-title">
                                                <?= $item['UF_TITLE'] ?>
                                            </div>
                                            <div class="read-list-item-text">
                                                <?= $item['UF_TEXT'] ?>
                                            </div>
                                        </div>
                                    </li>
                                <? endforeach; ?>
                            </ul>
                        </div>
                    <? endif; ?>
                <? endforeach; ?>
            </div>
        </div>
    </div>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>