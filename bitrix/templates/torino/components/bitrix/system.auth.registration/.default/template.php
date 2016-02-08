<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<div class="container">

        <div class="row">
            <h2 class="hed text-center">
                <b><?=GetMessage("AUTH_REGISTER")?></b><br>
                <?  ShowMessage($arParams["~AUTH_RESULT"]); ?><br>
            </h2>
        </div>

        <div class="bx-auth" id="registrationpanel">
        <?//  ShowMessage($arParams["~AUTH_RESULT"]); ?>
        <?if($arResult["USE_EMAIL_CONFIRMATION"] === "Y" && is_array($arParams["AUTH_RESULT"]) &&  $arParams["AUTH_RESULT"]["TYPE"] === "OK"):?>
        <p><?echo GetMessage("AUTH_EMAIL_SENT")?></p>
        <?else:?>

        <?if($arResult["USE_EMAIL_CONFIRMATION"] === "Y"):?>
            <p><?echo GetMessage("AUTH_EMAIL_WILL_BE_SENT")?></p>
        <?endif?>
        <noindex>
        <form  class="text-center" method="post" action="<?=$arResult["AUTH_URL"]?>" name="bform">
        <?
        if (strlen($arResult["BACKURL"]) > 0)
        {
        ?>
            <input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
        <?
        }
        ?>
            <input type="hidden" name="AUTH_FORM" value="Y" />
            <input type="hidden" name="TYPE" value="REGISTRATION" />

        <table class="table-responsive data-table bx-registration-table" id="regtable">
            <?/*<thead>
                <tr>
                    <td colspan="2"><b><?=GetMessage("AUTH_REGISTER")?></b></td>
                </tr>
            </thead> */?>
            <tbody>
                <tr>
                    <td><?=GetMessage("AUTH_NAME")?></td>
                    <td><input type="text" name="USER_NAME" maxlength="50" value="<?=$arResult["USER_NAME"]?>" class="bx-auth-input" /></td>
                </tr>
                <tr>
                    <td><?=GetMessage("AUTH_LAST_NAME")?></td>
                    <td><input type="text" name="USER_LAST_NAME" maxlength="50" value="<?=$arResult["USER_LAST_NAME"]?>" class="bx-auth-input" /></td>
                </tr>
                <tr>
                    <td><span class="starrequired">*</span><?=GetMessage("AUTH_LOGIN_MIN")?></td>
                    <td><input type="text" name="USER_LOGIN" maxlength="50" value="<?=$arResult["USER_LOGIN"]?>" class="bx-auth-input" /></td>
                </tr>
                <tr>
                    <td><span class="starrequired">*</span><?=GetMessage("AUTH_PASSWORD_REQ")?></td>
                    <td><input type="password" name="USER_PASSWORD" maxlength="50" value="<?=$arResult["USER_PASSWORD"]?>" class="bx-auth-input" />
        <?if($arResult["SECURE_AUTH"]):?>
                        <span class="bx-auth-secure" id="bx_auth_secure" title="<?echo GetMessage("AUTH_SECURE_NOTE")?>" style="display:none">
                            <div class="bx-auth-secure-icon"></div>
                        </span>
                        <noscript>
                        <span class="bx-auth-secure" title="<?echo GetMessage("AUTH_NONSECURE_NOTE")?>">
                            <div class="bx-auth-secure-icon bx-auth-secure-unlock"></div>
                        </span>
                        </noscript>
        <script type="text/javascript">
        document.getElementById('bx_auth_secure').style.display = 'inline-block';
        </script>
        <?endif?>
                    </td>
                </tr>
                <tr>
                    <td><span class="starrequired">*</span><?=GetMessage("AUTH_CONFIRM")?></td>
                    <td><input type="password" name="USER_CONFIRM_PASSWORD" maxlength="50" value="<?=$arResult["USER_CONFIRM_PASSWORD"]?>" class="bx-auth-input" /></td>
                </tr>
                <tr>
                    <td><?if($arResult["EMAIL_REQUIRED"]):?><span class="starrequired">*</span><?endif?><?=GetMessage("AUTH_EMAIL")?></td>
                    <td><input type="text" name="USER_EMAIL" maxlength="255" value="<?=$arResult["USER_EMAIL"]?>" class="bx-auth-input" /></td>
                </tr>
        <?// ********************* User properties ***************************************************?>
        <?if($arResult["USER_PROPERTIES"]["SHOW"] == "Y"):?>
            <tr><td colspan="2"><?=strlen(trim($arParams["USER_PROPERTY_NAME"])) > 0 ? $arParams["USER_PROPERTY_NAME"] : GetMessage("USER_TYPE_EDIT_TAB")?></td></tr>
            <?foreach ($arResult["USER_PROPERTIES"]["DATA"] as $FIELD_NAME => $arUserField):?>
            <tr><td><?if ($arUserField["MANDATORY"]=="Y"):?><span class="starrequired">*</span><?endif;
                ?><?=$arUserField["EDIT_FORM_LABEL"]?>:</td><td>
                    <?$APPLICATION->IncludeComponent(
                        "bitrix:system.field.edit",
                        $arUserField["USER_TYPE"]["USER_TYPE_ID"],
                        array("bVarsFromForm" => $arResult["bVarsFromForm"], "arUserField" => $arUserField, "form_name" => "bform"), null, array("HIDE_ICONS"=>"Y"));?></td></tr>
            <?endforeach;?>
        <?endif;?>
        <?// ******************** /User properties ***************************************************

            /* CAPTCHA */
            if ($arResult["USE_CAPTCHA"] == "Y")
            {
                ?>
                <tr>
                    <td colspan="2"><b><?=GetMessage("CAPTCHA_REGF_TITLE")?></b></td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <input type="hidden" name="captcha_sid" value="<?=$arResult["CAPTCHA_CODE"]?>" />
                        <img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
                    </td>
                </tr>
                <tr>
                    <td><span class="starrequired">*</span><?=GetMessage("CAPTCHA_REGF_PROMT")?>:</td>
                    <td><input type="text" name="captcha_word" maxlength="50" value="" /></td>
                </tr>
                <?
            }
            /* CAPTCHA */
            ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-center">
                        <br>
                        <p><?echo $arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"];?></p>
                        <p><span class="starrequired">*</span><?=GetMessage("AUTH_REQ")?></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="text-center">
                        <br>
                        <input type="submit" class="btn btn-success btn-lg" name="Register" value="<?=GetMessage("AUTH_REGISTER")?>">
                        <br>
                    </td>
                </tr>
            </tfoot>
        </table>


        <p class="text-center"> <br/><br/>
            Если Вы уже зарегистрированный пользователь, то пожалуйста
            <a class="systhref" href="<?=$arResult["AUTH_AUTH_URL"]?>" rel="nofollow"><b>Авторизуйтесь</b></a>.
            <br/><br/><br/><br/>
        </p>

        </form>
        </noindex>
        <script type="text/javascript">
            document.bform.USER_NAME.focus();
        </script>

        <?endif?>
        </div>


</div>