<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("IBLOCK_ELEMENT_ADD_LIST_NAME"),
	"DESCRIPTION" => GetMessage("IBLOCK_ELEMENT_ADD_LIST_DESCRIPTION"),
	"ICON" => "/images/eaddlist.gif",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "iblock_element_add",
			"CHILD" => array(
				"ID" => "element_add_cmpx",
			),
		),
	),
);
?>