<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Авторизация");
?>
    <div class="wrapper-fluid">
        <div class="wrapper">
            <? $APPLICATION->IncludeComponent(
                "bitrix:system.auth.form",
                "common",
                array(
                    "FORGOT_PASSWORD_URL" => "/auth/forget.php",
                    "PROFILE_URL" => "/auth/personal.php",
                    "REGISTER_URL" => "/auth/registration.php",
                    "SHOW_ERRORS" => "N"
                )
            ); ?>
        </div>
    </div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>