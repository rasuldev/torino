<?
class CAllSaleOrderUserPropsValue
{
	function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if(CSaleLocation::isLocationProMigrated())
		{
			$strSql =
				"SELECT V.ID, V.USER_PROPS_ID, V.ORDER_PROPS_ID, V.NAME, ".CSaleOrderPropsValue::getPropertyValueFieldSelectSql('V').", P.TYPE ".
				"FROM b_sale_user_props_value V ".
				"INNER JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID) ".
				CSaleOrderPropsValue::getLocationTableJoinSql('V').
				"WHERE V.ID = ".$ID."";
		}
		else
		{
			$strSql =
				"SELECT * ".
				"FROM b_sale_user_props_value ".
				"WHERE ID = ".$ID."";
		}

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_user_props_value WHERE ID = ".$ID."", true);
	}

	function DeleteAll($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_user_props_value WHERE USER_PROPS_ID = ".$ID."", true);
	}
	
	function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		// need to check here if we got CODE or ID came
		if(isset($arFields['VALUE']) && ((string) $arFields['VALUE'] != '') && CSaleLocation::isLocationProMigrated())
		{
			$propValue = self::GetByID($ID);

			if($propValue['TYPE'] == 'LOCATION')
			{
				$arFields['VALUE'] = CSaleLocation::tryTranslateIDToCode($arFields['VALUE']);
			}
		}

		$strUpdate = $DB->PrepareUpdate("b_sale_user_props_value", $arFields);
		$strSql = 
			"UPDATE b_sale_user_props_value SET ".
			"	".$strUpdate." ".
			"WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

}
?>