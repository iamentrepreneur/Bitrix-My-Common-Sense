<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("my-insights");
?>

    <div class="wrapper-fluid">
        <div class="wrapper">
            <?
            $APPLICATION->IncludeComponent('app:insights', '', [
                'SEF_MODE' => 'Y',
                'SEF_FOLDER' => '/my-insights/',
                'SEF_URL_TEMPLATES' => [
                    'list' => '',
                    'group' => 'group/#GROUP_ID#/',
                ],
            ], false);

            ?>
        </div>
    </div>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>