<?php

use Bitrix\Main\Loader;

try {
    Loader::registerAutoLoadClasses(null, [

    ]);
} catch (\Bitrix\Main\LoaderException $e) {
    ShowError($e->getMessage());
}