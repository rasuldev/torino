<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

$arComponentDescription = array(
	"NAME" => GetMessage("SPCD1_PLANS"),
	"DESCRIPTION" => GetMessage("SPCD1_PLANS_ALT"),
	"ICON" => "/images/icon.gif",
	"PATH" => array(
		"ID" => "e-store",
		"NAME" => GetMessage("SPCD1_SALE"),
		"CHILD" => array(
			"ID" => "affiliate",
			"NAME" => GetMessage("SPCD1_AFFILIATE")
		)
	),
);
?>