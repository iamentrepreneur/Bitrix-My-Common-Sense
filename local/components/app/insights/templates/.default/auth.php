<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
?>
<div class="ins-auth-required">
    <div style="font-size:18px;font-weight:700;margin-bottom:8px;">Требуется авторизация</div>
    <div style="color:#777;margin-bottom:12px;">
        Чтобы пользоваться разделом, войдите в личный кабинет.
    </div>
    <a href="/auth/?backurl=<?=rawurlencode($APPLICATION->GetCurPageParam())?>" style="display:inline-block;padding:10px 14px;border-radius:10px;background:#2d2d2d;color:#fff;text-decoration:none;">
        Войти
    </a>
</div>
