<?
global $APPLICATION;
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
?>
<!DOCTYPE html>
<html>
	<head>
		<?$APPLICATION->ShowHead();?>
		<title><?$APPLICATION->ShowTitle();?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1 user-scalable=no">
        <?$APPLICATION->SetAdditionalCss("/local/templates/my-common-sense/fonts/roboto/stylesheet.css");?>
        <?$APPLICATION->SetAdditionalCss("/local/templates/my-common-sense/fonts/remix/remixicon.css");?>
        <?$APPLICATION->SetAdditionalCss("/local/templates/my-common-sense/css/reset.css");?>
	</head>
	<body>
		<div id="panel">
			<?$APPLICATION->ShowPanel();?>
		</div>

        <header>
            <div class="wrapper-fluid">
                <div class="wrapper">
                    <div class="header-wrapper">
                        <div class="header-logo">
                            <a href="/">
                                <span>My</span>
                                <span>_</span>
                                <span>Common</span>
                                <span>_</span>
                                <span>Sense</span>
                            </a>
                        </div>
                        <div class="header-menu">
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:menu",
                                "top-menu",
                                [
                                    "ALLOW_MULTI_SELECT" => "N",
                                    "CHILD_MENU_TYPE" => "left",
                                    "DELAY" => "N",
                                    "MAX_LEVEL" => "1",
                                    "MENU_CACHE_GET_VARS" => [
                                        0 => "",
                                    ],
                                    "MENU_CACHE_TIME" => "3600",
                                    "MENU_CACHE_TYPE" => "N",
                                    "MENU_CACHE_USE_GROUPS" => "Y",
                                    "ROOT_MENU_TYPE" => "top",
                                    "USE_EXT" => "N"
                                ],
                                false,
                                array("HIDE_ICONS" => "Y")
                            );?>
                        </div>
                    </div>
                </div>
            </div>
        </header>


