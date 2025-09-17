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
        <?$APPLICATION->SetAdditionalCss("/local/templates/my-common-sense/fonts/stylesheet.css");?>
        <?$APPLICATION->SetAdditionalCss("/local/templates/my-common-sense/css/reset.css");?>
	</head>
	<body>
		<div id="panel">
			<?$APPLICATION->ShowPanel();?>
		</div>
	
						