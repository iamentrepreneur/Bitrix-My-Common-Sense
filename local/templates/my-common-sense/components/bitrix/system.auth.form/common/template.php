<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CJSCore::Init();
?>

<div class="panel auth-panel">

    <?
    if ($arResult['SHOW_ERRORS'] === 'Y' && $arResult['ERROR'] && !empty($arResult['ERROR_MESSAGE'])) {
        ShowMessage($arResult['ERROR_MESSAGE']);
    }
    ?>

    <? if ($arResult["FORM_TYPE"] == "login"): ?>

        <form class="auth-form" name="system_auth_form<?= $arResult["RND"] ?>" method="post" target="_top"
              action="<?= $arResult["AUTH_URL"] ?>">
            <? if ($arResult["BACKURL"] <> ''): ?>
                <input type="hidden" name="backurl" value="<?= $arResult["BACKURL"] ?>"/>
            <?endif ?>
            <? foreach ($arResult["POST"] as $key => $value): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= $value ?>"/>
            <?endforeach ?>
            <input type="hidden" name="AUTH_FORM" value="Y"/>
            <input type="hidden" name="TYPE" value="AUTH"/>


            <div class="auth-form-row">
                <input class="input" type="text" name="USER_LOGIN" maxlength="50" value="" size="17"/>
                <script>
                    BX.ready(function () {
                        var loginCookie = BX.getCookie("<?=CUtil::JSEscape($arResult["~LOGIN_COOKIE_NAME"])?>");
                        if (loginCookie) {
                            var form = document.forms["system_auth_form<?=$arResult["RND"]?>"];
                            var loginInput = form.elements["USER_LOGIN"];
                            loginInput.value = loginCookie;
                        }
                    });
                </script>
            </div>

            <div class="auth-form-row">
                <input class="input" type="password" name="USER_PASSWORD" maxlength="255" size="17" autocomplete="off"/>
                <? if ($arResult["SECURE_AUTH"]): ?>
                    <span class="bx-auth-secure" id="bx_auth_secure<?= $arResult["RND"] ?>"
                          title="<? echo GetMessage("AUTH_SECURE_NOTE") ?>" style="display:none">
					<div class="bx-auth-secure-icon"></div>
				</span>
                    <noscript>
				<span class="bx-auth-secure" title="<? echo GetMessage("AUTH_NONSECURE_NOTE") ?>">
					<div class="bx-auth-secure-icon bx-auth-secure-unlock"></div>
				</span>
                    </noscript>
                    <script>
                        document.getElementById('bx_auth_secure<?=$arResult["RND"]?>').style.display = 'inline-block';
                    </script>
                <?endif ?>
            </div>

            <div class="auth-form-row">
                <? if ($arResult["STORE_PASSWORD"] == "Y"): ?>
                    <input type="checkbox" id="USER_REMEMBER_frm" name="USER_REMEMBER" value="Y"/>
                    <label for="USER_REMEMBER_frm"
                           title="<?= GetMessage("AUTH_REMEMBER_ME") ?>"><? echo GetMessage("AUTH_REMEMBER_SHORT") ?>
                    </label>
                <?endif;?>
            </div>

            <div class="auth-form-row">
                <input class="btn" type="submit" name="Login" value="<?= GetMessage("AUTH_LOGIN_BUTTON") ?>"/>
            </div>



<!--            --><?// if ($arResult["NEW_USER_REGISTRATION"] == "Y"): ?>
<!--                <noindex><a href="--><?php //= $arResult["AUTH_REGISTER_URL"] ?><!--"-->
<!--                            rel="nofollow">--><?php //= GetMessage("AUTH_REGISTER") ?><!--</a></noindex>-->
<!--            --><?//endif ?>
<!---->
<!---->
<!--            <noindex><a href="--><?php //= $arResult["AUTH_FORGOT_PASSWORD_URL"] ?><!--"-->
<!--                        rel="nofollow">--><?php //= GetMessage("AUTH_FORGOT_PASSWORD_2") ?><!--</a></noindex>-->


        </form>
    <?
    else:
        ?>
        <form action="<?= $arResult["AUTH_URL"] ?>">
            <table width="95%">
                <tr>
                    <td align="center">
                        <?= $arResult["USER_NAME"] ?><br/>
                        [<?= $arResult["USER_LOGIN"] ?>]<br/>
                        <a href="<?= $arResult["PROFILE_URL"] ?>"
                           title="<?= GetMessage("AUTH_PROFILE") ?>"><?= GetMessage("AUTH_PROFILE") ?></a><br/>
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        <? foreach ($arResult["GET"] as $key => $value):?>
                            <input type="hidden" name="<?= $key ?>" value="<?= $value ?>"/>
                        <? endforeach ?>
                        <?= bitrix_sessid_post() ?>
                        <input type="hidden" name="logout" value="yes"/>
                        <input type="submit" name="logout_butt" value="<?= GetMessage("AUTH_LOGOUT_BUTTON") ?>"/>
                    </td>
                </tr>
            </table>
        </form>
    <? endif ?>
</div>
