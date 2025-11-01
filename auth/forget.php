<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Забыли пароль");
?><div class="wrapper-fluid">
	<div class="wrapper">
<?$APPLICATION->IncludeComponent(
	"bitrix:main.auth.forgotpasswd",
	"",
	Array(
		"AUTH_AUTH_URL" => "/auth/",
		"AUTH_REGISTER_URL" => "/auth/registration.php"
	)
);?>
	</div>
</div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>