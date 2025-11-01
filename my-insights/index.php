<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("my-insights");
?>

<?
$APPLICATION->IncludeComponent('app:insights', '', [
    'SEF_MODE' => 'Y',
    'SEF_FOLDER' => '/my-insights/',
    'SEF_URL_TEMPLATES' => [
        'list'  => '',
        'group' => 'group/#GROUP_ID#/',
    ],
], false);

?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>