<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("forum"))
	return;
$arTemplateParameters = array(
	"SEO_USE_AN_EXTERNAL_SERVICE" => Array(
		"NAME" => GetMessage("F_SEO_USE_AN_EXTERNAL_SERVICE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y"),
	"SEND_MAIL" => CForumParameters::GetSendMessageRights(GetMessage("F_SEND_MAIL"), "BASE", "E")
);
?>