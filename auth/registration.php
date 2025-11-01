<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Решистрация");
?><div class="wrapper-fluid">
	<div class="wrapper">
<?$APPLICATION->IncludeComponent(
	"bitrix:main.register",
	"",
	Array(
		"AUTH" => "Y",
		"REQUIRED_FIELDS" => array(),
		"SET_TITLE" => "Y",
		"SHOW_FIELDS" => array("EMAIL", "NAME", "SECOND_NAME", "LAST_NAME"),
		"SUCCESS_PAGE" => "",
		"USER_PROPERTY" => array(),
		"USER_PROPERTY_NAME" => "",
		"USE_BACKURL" => "Y"
	)
);?>
	</div>
</div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>