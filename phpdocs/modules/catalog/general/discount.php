<?
use Bitrix\Main\Loader;
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/index.php
 * @author Bitrix
 */
class CAllCatalogDiscount
{
	const TYPE_PERCENT = 'P';
	const TYPE_FIX = 'F';
	const TYPE_SALE = 'S';

	const ENTITY_ID = 0;
	const CURRENT_FORMAT = 2;
	const OLD_FORMAT = 1;

	static protected $arCacheProduct = array();
	static protected $arCacheDiscountFilter = array();
	static protected $arCacheDiscountResult = array();
	static protected $arCacheProductSectionChain = array();
	static protected $arCacheProductSections = array();
	static protected $arCacheProductProperties = array();
	static protected $cacheDiscountHandlers = array();
	static protected $usedModules = array();

	static public function GetDiscountTypes($boolFull = false)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			return array(
				self::TYPE_PERCENT => GetMessage('BT_CAT_DISCOUNT_TYPE_PERCENT'),
				self::TYPE_FIX => GetMessage('BT_CAT_DISCOUNT_TYPE_FIX'),
				self::TYPE_SALE => GetMessage('BT_CAT_DISCOUNT_TYPE_SALE_EXT'),
			);
		}
		return array(
			self::TYPE_PERCENT,
			self::TYPE_FIX,
			self::TYPE_SALE,
		);
	}

	static public function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION, $DB, $USER;

		$boolResult = true;
		$arMsg = array();

		$ACTION = strtoupper($ACTION);
		if ($ACTION != 'UPDATE' && $ACTION != 'ADD')
			return false;

		if (!is_array($arFields))
			return false;

		$boolValueType = false;
		$boolValue = false;
		$arCurrent = array(
			'VALUE' => 0,
			'VALUE_TYPE' => ''
		);

		$clearFields = array(
			'ID',
			'~ID',
			'UNPACK',
			'~UNPACK',
			'~CONDITIONS',
			'USE_COUPONS',
			'~USE_COUPONS',
			'HANDLERS',
			'~HANDLERS',
			'~TYPE',
			'~VERSION',
			'TIMESTAMP_X',
			'DATE_CREATE',
			'~DATE_CREATE',
			'~MODIFIED_BY',
			'~CREATED_BY'
		);
		if ($ACTION == 'UPDATE')
			$clearFields[] = 'CREATED_BY';
		$arFields = array_filter($arFields, 'CCatalogDiscount::clearFields');
		foreach ($clearFields as &$fieldName)
		{
			if (isset($arFields[$fieldName]))
				unset($arFields[$fieldName]);
		}
		unset($fieldName, $clearFields);

		$arFields['TYPE'] = self::ENTITY_ID;
		$arFields['VERSION'] = self::CURRENT_FORMAT;

		if ($ACTION == 'ADD')
		{
			$boolValueType = true;
			$boolValue = true;

			$defaultValues = array(
				'ACTIVE' => 'Y',
				'RENEWAL' => 'N',
				'MAX_USES' => 0,
				'COUNT_USES' => 0,
				'SORT' => 100,
				'MAX_DISCOUNT' => 0,
				'VALUE_TYPE' => self::TYPE_PERCENT,
				'MIN_ORDER_SUM' => 0,
				'PRIORITY' => 1,
				'LAST_DISCOUNT' => 'Y'
			);
			$arFields = array_merge($defaultValues, $arFields);
			unset($defaultValues);

			if (!isset($arFields['SITE_ID']))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'SITE_ID', 'text' => GetMessage("KGD_EMPTY_SITE"));
			}
			if (!isset($arFields['CURRENCY']))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('KGD_EMPTY_CURRENCY'));
			}
			if (!isset($arFields['NAME']))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('KGD_EMPTY_NAME'));
			}
			if (!isset($arFields['VALUE']))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'VALUE', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_VALUE'));
			}
			if (!isset($arFields['CONDITIONS']))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_EMPTY_CONDITIONS'));
			}
			$arFields['USE_COUPONS'] = 'N';
		}

		if ($ACTION == 'UPDATE')
		{
			$ID = (int)$ID;
			if ($ID <= 0)
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'ID', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_ID', array('#ID#', $ID)));
			}
			else
			{
				$boolValueType = isset($arFields['VALUE_TYPE']);
				$boolValue = isset($arFields['VALUE']);
				if ($boolValueType != $boolValue)
				{
					$rsDiscounts = CCatalogDiscount::GetList(
						array(),
						array('ID' => $ID),
						false,
						false,
						array('ID', 'VALUE_TYPE', 'VALUE')
					);
					if ($arCurrent = $rsDiscounts->Fetch())
					{
						$arCurrent['VALUE'] = doubleval($arCurrent['VALUE']);
					}
					else
					{
						$boolResult = false;
						$arMsg[] = array('id' => 'ID', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_ID', array('#ID#', $ID)));
					}
				}
			}
		}

		if ($boolResult)
		{
			if (isset($arFields['SITE_ID']))
			{
				if (empty($arFields['SITE_ID']))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'SITE_ID', 'text' => GetMessage('KGD_EMPTY_SITE'));
				}
			}
			if (isset($arFields['CURRENCY']))
			{
				if (empty($arFields['CURRENCY']))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('KGD_EMPTY_CURRENCY'));
				}
			}
			if (isset($arFields['NAME']))
			{
				$arFields['NAME'] = trim($arFields['NAME']);
				if ($arFields['NAME'] === '')
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('KGD_EMPTY_NAME'));
				}
			}
			if (isset($arFields['ACTIVE']))
			{
				$arFields['ACTIVE'] = ($arFields['ACTIVE'] != 'N' ? 'Y' : 'N');
			}
			if (isset($arFields['ACTIVE_FROM']))
			{
				if (!$DB->IsDate($arFields['ACTIVE_FROM'], false, LANGUAGE_ID, 'FULL'))
				{
					$arFields['ACTIVE_FROM'] = false;
				}
			}
			if (isset($arFields['ACTIVE_TO']))
			{
				if (!$DB->IsDate($arFields['ACTIVE_TO'], false, LANGUAGE_ID, 'FULL'))
				{
					$arFields['ACTIVE_TO'] = false;
				}
			}
			if (isset($arFields['RENEWAL']))
			{
				$arFields['RENEWAL'] = ($arFields['RENEWAL'] == 'Y' ? 'Y' : 'N');
			}
			if (isset($arFields['MAX_USES']))
			{
				$arFields['MAX_USES'] = (int)$arFields['MAX_USES'];
				if ($arFields['MAX_USES'] < 0)
					$arFields['MAX_USES'] = 0;
			}
			if (isset($arFields['COUNT_USES']))
			{
				$arFields['COUNT_USES'] = (int)$arFields['COUNT_USES'];
				if ($arFields['COUNT_USES'] < 0)
					$arFields['COUNT_USES'] = 0;
			}
			if (isset($arFields['CATALOG_COUPONS']))
			{
				if (empty($arFields['CATALOG_COUPONS']) && !is_array($arFields['CATALOG_COUPONS']))
					unset($arFields['CATALOG_COUPONS']);
			}
			if (isset($arFields['SORT']))
			{
				$arFields['SORT'] = (int)$arFields['SORT'];
				if ($arFields['SORT'] <= 0)
					$arFields['SORT'] = 100;
			}
			if (isset($arFields['MAX_DISCOUNT']))
			{
				$arFields['MAX_DISCOUNT'] = str_replace(',', '.', $arFields['MAX_DISCOUNT']);
				$arFields['MAX_DISCOUNT'] = doubleval($arFields['MAX_DISCOUNT']);
				if ($arFields['MAX_DISCOUNT'] < 0)
					$arFields['MAX_DISCOUNT'] = 0;
			}

			if ($boolValueType)
			{
				if (!in_array($arFields['VALUE_TYPE'], CCatalogDiscount::GetDiscountTypes()))
					$arFields['VALUE_TYPE'] = self::TYPE_PERCENT;
			}
			if ($boolValue)
			{
				$arFields['VALUE'] = str_replace(',', '.', $arFields['VALUE']);
				$arFields['VALUE'] = doubleval($arFields['VALUE']);
				if ($arFields['VALUE'] <= 0)
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'VALUE', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_VALUE'));
				}
			}
			if ($ACTION == 'UPDATE')
			{
				if ($boolValue != $boolValueType)
				{
					if (!$boolValue)
					{
						$arFields['VALUE'] = $arCurrent['VALUE'];
						$boolValue = true;
					}
					if (!$boolValueType)
					{
						$arFields['VALUE_TYPE'] = $arCurrent['VALUE_TYPE'];
						$boolValueType = true;
					}
				}
			}
			if ($boolValue && $boolValueType)
			{
				if ($arFields['VALUE_TYPE'] == self::TYPE_PERCENT && $arFields['VALUE'] > 100)
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'VALUE', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_VALUE"));
				}
			}
			if (isset($arFields['MIN_ORDER_SUM']))
			{
				$arFields['MIN_ORDER_SUM'] = str_replace(',', '.', $arFields['MIN_ORDER_SUM']);
				$arFields['MIN_ORDER_SUM'] = doubleval($arFields['MIN_ORDER_SUM']);
			}
			if (isset($arFields['PRIORITY']))
			{
				$arFields['PRIORITY'] = (int)$arFields['PRIORITY'];
				if (0 >= $arFields['PRIORITY'])
					$arFields['PRIORITY'] = 1;
			}
			if (isset($arFields['LAST_DISCOUNT']))
			{
				$arFields['LAST_DISCOUNT'] = ($arFields['LAST_DISCOUNT'] != 'N' ? 'Y' : 'N');
			}
		}
		if ($boolResult)
		{
			if (isset($arFields['CONDITIONS']))
			{
				if (empty($arFields['CONDITIONS']))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_EMPTY_CONDITIONS"));
				}
				else
				{
					$usedHandlers = array();
					$boolCond = true;
					$strEval = '';
					if (!is_array($arFields['CONDITIONS']))
					{
						if (!CheckSerializedData($arFields['CONDITIONS']))
						{
							$boolCond = false;
							$boolResult = false;
							$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_CONDITIONS"));
						}
						else
						{
							$arFields['CONDITIONS'] = unserialize($arFields['CONDITIONS']);
							if (empty($arFields['CONDITIONS']) || !is_array($arFields['CONDITIONS']))
							{
								$boolCond = false;
								$boolResult = false;
								$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_CONDITIONS"));
							}
						}
					}
					if ($boolCond)
					{
						$obCond = new CCatalogCondTree();
						$boolCond = $obCond->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_CATALOG, array());
						if (!$boolCond)
						{
							return false;
						}
						$strEval = $obCond->Generate($arFields['CONDITIONS'], array('FIELD' => '$arProduct'));
						if (empty($strEval) || 'false' == $strEval)
						{
							$boolCond = false;
							$boolResult = false;
							$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_CONDITIONS"));
						}
						else
						{
							$usedHandlers = $obCond->GetConditionHandlers();
						}
					}
					if ($boolCond)
					{
						$arFields['UNPACK'] = $strEval;
						$arFields['CONDITIONS'] = serialize($arFields['CONDITIONS']);
						if (!empty($usedHandlers))
							$arFields['HANDLERS'] = $usedHandlers;

						if ('mysql' == strtolower($DB->type))
						{
							if (64000 < CUtil::BinStrlen($arFields['UNPACK']) || 64000 < CUtil::BinStrlen($arFields['CONDITIONS']))
							{
								$boolResult = false;
								$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_CONDITIONS_TOO_LONG'));
								unset($arFields['UNPACK']);
								$arFields['CONDITIONS'] = unserialize($arFields['CONDITIONS']);
							}
						}
					}
				}
			}
		}

		$intUserID = 0;
		$boolUserExist = CCatalog::IsUserExists();
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		$arFields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!isset($arFields['MODIFIED_BY']) || (int)$arFields["MODIFIED_BY"] <= 0)
				$arFields["MODIFIED_BY"] = $intUserID;
		}
		if ($ACTION == 'ADD')
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!isset($arFields['CREATED_BY']) || (int)$arFields["CREATED_BY"] <= 0)
					$arFields["CREATED_BY"] = $intUserID;
			}
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	
	/**
	* <p>Метод добавляет новую скидку в соответствии с данными из массива arFields.</p>
	*
	*
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой скидки, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения. Допустимые ключи: <ul> <li> <b>SITE_ID</b> - сайт, обязательное
	* поле;</li> <li> <b>ACTIVE</b> - флаг активности;</li> <li> <b>NAME</b> - название
	* скидки, обязательное поле;</li> <li> <b>COUPON</b> - код купона;</li> <li> <b>SORT</b> -
	* индекс сортировки;</li> <li> <b>MAX_DISCOUNT</b> - максимальная величина
	* скидки;</li> <li> <b>VALUE_TYPE</b> - тип скидки (P - в процентах, F -
	* фиксированная величина, S - фиксированная цена);</li> <li> <b>VALUE</b> -
	* величина скидки;</li> <li> <b>CURRENCY</b> - валюта, обязательное поле;</li> <li>
	* <b>RENEWAL</b> - флаг "Скидка на продление";</li> <li> <b>ACTIVE_FROM</b> - дата начала
	* действия скидки;</li> <li> <b>ACTIVE_TO</b> - дата окончания действия
	* скидки;</li> <li> <b>IBLOCK_IDS</b> - массив кодов инфоблоков, на которые
	* действует скидка (если скидка действует не на все инфоблоки). Ключ
	* является устаревшим с версии <b>12.0.0</b>;</li> <li> <b>PRODUCT_IDS</b> - массив
	* кодов товаров, на которые действует скидка (если скидка действует
	* не на все товары). Ключ является устаревшим с версии <b>12.0.0</b>;</li> <li>
	* <b>SECTION_IDS</b> - массив кодов групп товаров, на которые действует
	* скидка (если скидка действует не на все группы товары). Ключ
	* является устаревшим с версии <b>12.0.0</b>;</li> <li> <b>GROUP_IDS</b> - массив
	* кодов групп пользователей, на которые действует скидка (если
	* скидка действует не на все группы пользователей);</li> <li>
	* <b>CATALOG_GROUP_IDS</b> - массив кодов типов цен, на которые действует
	* скидка (если скидка действует не на все типы цен).</li> <li>
	* <b>CATALOG_COUPONS</b> - массив купонов скидки.</li> <li> <b>PRIORITY</b> - приоритет
	* применимости;</li> <li> <b>CONDITIONS</b> - массив для создания условий
	* использования скидки. Ключ доступен с версии <b>12.0.0</b>. <br><br> Если он
	* задан и не пуст, то массивы <b>PRODUCT_IDS</b>, <b>SECTION_IDS</b> и <b>IBLOCK_IDS</b>
	* использоваться не будут. Чтобы задать параметры скидки через эти
	* 3 ключа, то <b>CONDITIONS</b> в массиве <b>arFields</b> должен отсутствовать. <br><br>
	* Каждое условие массива <b>CONDITIONS</b> описывается массивом следующей
	* структуры: <ul> <li> <i>CLASS_ID</i> - идентификатор (строка);</li> <li> <i>DATA =&gt;
	* array()</i> - массив параметров условий;</li> <li> <i>CHILDREN =&gt; array()</i> - массив
	* подусловий, каждое из которых является массивом аналогичной
	* структуры, где ключами являются значения 0,1,2,3,.. </li> </ul> <br>
	* Возможные логические условия: <ul> <li>Equal - равно;</li> <li>Not - не
	* равно;</li> <li>Great - больше;</li> <li>Less - меньше;</li> <li>EqGr - больше либо
	* равно;</li> <li>EqLs - меньше либо равно.</li> </ul> <br> Наименования условий:
	* <ul> <li>CondIBElement - товар;</li> <li>CondIBIBlock - инфоблок;</li> <li>CondIBSection -
	* раздел;</li> <li>CondIBCode - символьный код;</li> <li>CondIBXmlID - внешний код;</li>
	* <li>CondIBName - название;</li> <li>CondIBActive - активность;</li> <li>CondIBDateActiveFrom -
	* начало активности;</li> <li>CondIBDateActiveTo - окончание активности;</li>
	* <li>CondIBSort - сортировка;</li> <li>CondIBPreviewText - описание для анонса;</li>
	* <li>CondIBDetailText - детальное описание;</li> <li>CondIBDateCreate - дата создания;</li>
	* <li>CondIBCreatedBy - автор;</li> <li>CondIBTimestampX - дата изменения;</li> <li>CondIBModifiedBy -
	* изменивший;</li> <li>CondIBTags - теги;</li> <li>CondCatQuantity - количество товара на
	* складе;</li> <li>CondCatWeight - вес товара;</li> <li>CondCatVatID - НДС;</li> <li>CondCatVatIncluded
	* - НДС включен в цену.</li> </ul> <br> Кроме того, возможна привязка
	* условий к свойствам товара. <br><br> Верхний элемент массива
	* <b>CONDITIONS</b> всегда один и тот же (для скидок каталога может быть
	* получен методом <b>CCatalogCondTree::GetDefaultConditions()</b>): <pre class="syntax"> array( 'CLASS_ID'
	* =&gt; 'CondGroup', 'DATA' =&gt; array('All' =&gt; 'AND', 'True' =&gt; 'True'), 'CHILDREN' =&gt; array() ); </pre> </li>
	* </ul>
	*
	*
	*
	* @return bool <p>Метод возвращает код вставленной записи или <i>false</i> в случае
	* ошибки.</p>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* Получить детальную информацию об ошибке при сохранении можно следующим образом:
	* 
	* 
	* $ID = CCatalogDiscount::Add($arFields);
	* $res = $ID&gt;0;
	* if (!$res) { 
	*     $ex = $APPLICATION-&gt;GetException();  
	*     $ex-&gt;GetString(); 
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php
	* @author Bitrix
	*/
	static public function Add($arFields)
	{
		foreach (GetModuleEvents("catalog", "OnBeforeDiscountAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}

		$mxRows = self::__ParseArrays($arFields);
		if (empty($mxRows) || !is_array($mxRows))
			return false;

		$boolNewVersion = true;
		if (!array_key_exists('CONDITIONS', $arFields))
		{
			self::__ConvertOldConditions('ADD', $arFields);
			$boolNewVersion = false;
		}

		$ID = CCatalogDiscount::_Add($arFields);
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		if ($boolNewVersion)
		{
			$arValuesList = self::__GetConditionValues($arFields);
			if (is_array($arValuesList) && !empty($arValuesList))
			{
				self::__GetOldOneEntity($arFields, $arValuesList, 'IBLOCK_IDS', 'CondIBIBlock');
				self::__GetOldOneEntity($arFields, $arValuesList, 'SECTION_IDS', 'CondIBSection');
				self::__GetOldOneEntity($arFields, $arValuesList, 'PRODUCT_IDS', 'CondIBElement');
			}
		}

		if (!CCatalogDiscount::__UpdateSubdiscount($ID, $mxRows, $arFields['ACTIVE']))
			return false;

		CCatalogDiscount::__UpdateOldEntities($ID, $arFields, false);

		if (array_key_exists('CATALOG_COUPONS', $arFields))
		{
			if (!is_array($arFields["CATALOG_COUPONS"]))
			{
				$arFields["CATALOG_COUPONS"] = array(
					"DISCOUNT_ID" => $ID,
					"ACTIVE" => "Y",
					"ONE_TIME" => "Y",
					"COUPON" => $arFields["CATALOG_COUPONS"],
					"DATE_APPLY" => false
				);
			}

			$arKeys = array_keys($arFields["CATALOG_COUPONS"]);
			if (!is_array($arFields["CATALOG_COUPONS"][$arKeys[0]]))
				$arFields["CATALOG_COUPONS"] = array($arFields["CATALOG_COUPONS"]);

			foreach ($arFields["CATALOG_COUPONS"] as &$arOneCoupon)
			{
				if (!empty($arOneCoupon['COUPON']))
				{
					$arOneCoupon['DISCOUNT_ID'] = $ID;
					CCatalogDiscountCoupon::Add($arOneCoupon, false);
				}
				if (isset($arOneCoupon))
					unset($arOneCoupon);
			}
		}

		foreach (GetModuleEvents("catalog", "OnDiscountAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры скидки с кодом ID в соответствии с данными из массива arFields.</p>
	*
	*
	*
	*
	* @param int $ID  Код скидки.
	*
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой скидки, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения. Допустимые ключи: <ul> <li> <b>SITE_ID</b> - сайт;</li> <li> <b>ACTIVE</b> -
	* флаг активности;</li> <li> <b>NAME</b> - название скидки;</li> <li> <b>COUPON</b> - код
	* купона;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>MAX_DISCOUNT</b> -
	* максимальная величина скидки;</li> <li> <b>VALUE_TYPE</b> - тип скидки (P - в
	* процентах, F - фиксированная величина, S - Установить цену на
	* товар);</li> <li> <b>VALUE</b> - величина скидки;</li> <li> <b>CURRENCY</b> - валюта;</li> <li>
	* <b>RENEWAL</b> - флаг "Скидка на продление";</li> <li> <b>ACTIVE_FROM</b> - дата начала
	* действия скидки;</li> <li> <b>ACTIVE_TO</b> - дата окончания действия
	* скидки;</li> <li> <b>IBLOCK_IDS</b> - массив кодов инфоблоков, на которые
	* действует скидка (если скидка действует не на все инфоблоки). Ключ
	* является устаревшим с версии <b>12.0.0</b>;</li> <li> <b>PRODUCT_IDS</b> - массив
	* кодов товаров, на которые действует скидка (если скидка действует
	* не на все товары). Ключ является устаревшим с версии <b>12.0.0</b>;</li> <li>
	* <b>SECTION_IDS</b> - массив кодов групп товаров, на которые действует
	* скидка (если скидка действует не на все группы товары). Ключ
	* является устаревшим с версии <b>12.0.0</b>;</li> <li> <b>GROUP_IDS</b> - массив
	* кодов групп пользователей, на которые действует скидка (если
	* скидка действует не на все группы пользователей);</li> <li>
	* <b>CATALOG_GROUP_IDS</b> - массив кодов типов цен, на которые действует
	* скидка (если скидка действует не на все типы цен);</li> <li> <b>CONDITIONS</b> -
	* массив для изменения условий использования скидки. Массив
	* перезаписывается, поэтому при обновлении скидки следует
	* добавлять в массив все необходимые данные. Ключ доступен с версии
	* <b>12.0.0</b>. <br><br> Если он задан и не пуст, то массивы <b>PRODUCT_IDS</b>,
	* <b>SECTION_IDS</b> и <b>IBLOCK_IDS</b> использоваться не будут. Чтобы задать
	* параметры скидки через эти 3 ключа, то <b>CONDITIONS</b> в массиве <b>arFields</b>
	* должен отсутствовать, а старые данные будут изменены в
	* соответствии <b>PRODUCT_IDS</b>, <b>SECTION_IDS</b> и <b>IBLOCK_IDS</b>. <br><br> Каждое
	* условие массива <b>CONDITIONS</b> описывается массивом следующей
	* структуры: <ul> <li> <i>CLASS_ID</i> - идентификатор (строка);</li> <li> <i>DATA =&gt;
	* array()</i> - массив параметров условий;</li> <li> <i>CHILDREN =&gt; array()</i> - массив
	* подусловий, каждое из которых является массивом аналогичной
	* структуры, где ключами являются значения 0,1,2,3,.. </li> </ul> <br>
	* Возможные логические условия: <ul> <li>Equal - равно;</li> <li>Not - не
	* равно;</li> <li>Great - больше;</li> <li>Less - меньше;</li> <li>EqGr - больше либо
	* равно;</li> <li>EqLs - меньше либо равно.</li> </ul> <br> Наименования условий:
	* <ul> <li>CondIBElement - товар;</li> <li>CondIBIBlock - инфоблок;</li> <li>CondIBSection -
	* раздел;</li> <li>CondIBCode - символьный код;</li> <li>CondIBXmlID - внешний код;</li>
	* <li>CondIBName - название;</li> <li>CondIBActive - активность;</li> <li>CondIBDateActiveFrom -
	* начало активности;</li> <li>CondIBDateActiveTo - окончание активности;</li>
	* <li>CondIBSort - сортировка;</li> <li>CondIBPreviewText - описание для анонса;</li>
	* <li>CondIBDetailText - детальное описание;</li> <li>CondIBDateCreate - дата создания;</li>
	* <li>CondIBCreatedBy - автор;</li> <li>CondIBTimestampX - дата изменения;</li> <li>CondIBModifiedBy -
	* изменивший;</li> <li>CondIBTags - теги;</li> <li>CondCatQuantity - количество товара на
	* складе;</li> <li>CondCatWeight - вес товара;</li> <li>CondCatVatID - НДС;</li> <li>CondCatVatIncluded
	* - НДС включен в цену.</li> </ul> <br> Кроме того, возможна привязка
	* условий к свойствам товара. <br><br> Верхний элемент массива
	* <b>CONDITIONS</b> всегда один и тот же (для скидок каталога может быть
	* получен методом <b>CCatalogCondTree::GetDefaultConditions()</b>): <pre class="syntax"> array( 'CLASS_ID'
	* =&gt; 'CondGroup', 'DATA' =&gt; array('All' =&gt; 'AND', 'True' =&gt; 'True'), 'CHILDREN' =&gt; array() ); </pre> </li>
	* </ul>
	*
	*
	*
	* @return bool <p>Метод возвращает код измененной записи или <i>false</i> в случае
	* ошибки.</p>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* Получить детальную информацию об ошибке при изменении можно следующим образом:
	* 
	* 
	* $res = CCatalogDiscount::Update($ID, $arFields);  
	* if (!$res) { 
	*     $ex = $APPLICATION-&gt;GetException();  
	*     $ex-&gt;GetString(); 
	* }
	* 
	* $arFields = array(
	*    "SITE_ID" =&gt; "s1",
	*    "MAX_DISCOUNT" =&gt; 0,
	*    "VALUE" =&gt; 15,
	*    "ACTIVE" =&gt; "Y",
	*    "CONDITIONS" =&gt;  array (
	*       'CLASS_ID' =&gt; 'CondGroup',
	*       'DATA' =&gt;
	*       array (
	*          'All' =&gt; 'AND',
	*          'True' =&gt; 'True',
	*       ),
	*       'CHILDREN' =&gt;
	*       array (
	*          0 =&gt;
	*          array (
	*             'CLASS_ID' =&gt; 'CondIBElement',
	*             'DATA' =&gt;
	*             array (
	*                'logic' =&gt; 'Equal',
	*                'value' =&gt; 2975, //товар с ID=2975
	*             ),
	*          ),
	*          1 =&gt;
	*          array (
	*             'CLASS_ID' =&gt; 'CondCatQuantity',
	*             'DATA' =&gt;
	*             array (
	*                'logic' =&gt; 'Equal',
	*                'value' =&gt; 10, //остаток на складе равен 10
	*             ),
	*          ),
	*       ),  
	*    )
	* );
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php
	* @author Bitrix
	*/
	static public function Update($ID, $arFields)
	{
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		foreach (GetModuleEvents("catalog", "OnBeforeDiscountUpdate", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array($ID, &$arFields)))
				return false;
		}

		$boolExistUserGroups = (isset($arFields['GROUP_IDS']) && is_array($arFields['GROUP_IDS']));
		$boolExistPriceTypes = (isset($arFields['CATALOG_GROUP_IDS']) && is_array($arFields['CATALOG_GROUP_IDS']));
		$boolUpdateRestrictions = $boolExistUserGroups || $boolExistPriceTypes || isset($arFields['ACTIVE']);

		$mxRows = false;
		if ($boolUpdateRestrictions)
		{
			if (!$boolExistUserGroups)
			{
				if (!CCatalogDiscount::__FillArrays($ID, $arFields, 'GROUP_IDS'))
					return false;
			}
			if (!$boolExistPriceTypes)
			{
				if (!CCatalogDiscount::__FillArrays($ID, $arFields, 'CATALOG_GROUP_IDS'))
					return false;
			}
			$mxRows = self::__ParseArrays($arFields);
			if (empty($mxRows) || !is_array($mxRows))
				return false;
		}

		$boolNewVersion = true;
		if (!array_key_exists('CONDITIONS', $arFields))
		{
			self::__ConvertOldConditions('UPDATE', $arFields);
			$boolNewVersion = false;
		}

		if (!CCatalogDiscount::_Update($ID, $arFields))
			return false;

		if ($boolNewVersion)
		{
			$arValuesList = self::__GetConditionValues($arFields);
			if (is_array($arValuesList) && !empty($arValuesList))
			{
				self::__GetOldOneEntity($arFields, $arValuesList, 'IBLOCK_IDS', 'CondIBIBlock');
				self::__GetOldOneEntity($arFields, $arValuesList, 'SECTION_IDS', 'CondIBSection');
				self::__GetOldOneEntity($arFields, $arValuesList, 'PRODUCT_IDS', 'CondIBElement');
			}
		}

		if ($boolUpdateRestrictions)
		{
			if (!CCatalogDiscount::__UpdateSubdiscount($ID, $mxRows, (isset($arFields['ACTIVE']) ? $arFields['ACTIVE'] : '')))
				return false;
		}

		CCatalogDiscount::__UpdateOldEntities($ID, $arFields, true);

		if (array_key_exists('CATALOG_COUPONS', $arFields))
		{
			if (!is_array($arFields["CATALOG_COUPONS"]))
			{
				$arFields["CATALOG_COUPONS"] = array(
					"DISCOUNT_ID" => $ID,
					"ACTIVE" => "Y",
					"ONE_TIME" => "Y",
					"COUPON" => $arFields["CATALOG_COUPONS"],
					"DATE_APPLY" => false
				);
			}

			$arKeys = array_keys($arFields["CATALOG_COUPONS"]);
			if (!is_array($arFields["CATALOG_COUPONS"][$arKeys[0]]))
				$arFields["CATALOG_COUPONS"] = array($arFields["CATALOG_COUPONS"]);

			foreach ($arFields["CATALOG_COUPONS"] as &$arOneCoupon)
			{
				if (!empty($arOneCoupon['COUPON']))
				{
					$arOneCoupon['DISCOUNT_ID'] = $ID;
					CCatalogDiscountCoupon::Add($arOneCoupon, false);
				}
				if (isset($arOneCoupon))
					unset($arOneCoupon);
			}
		}

		foreach (GetModuleEvents("catalog", "OnDiscountUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return $ID;
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::SetCoupon()
*/
	
	/**
	* <p>Метод добавляет код купона <i> coupon</i> в массив доступных для получения скидки купонов текущего покупателя. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @param string $coupon  Код купона.
	*
	*
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного добавления кода
	* купона и <i>false</i> в случае ошибки.</p> <h4>Примечание</h4><p>С версии 12.0
	* считаются устаревшим. Оставлен для совместимости. Рекомендуется
	* использовать <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/setcoupon.php">аналогичный
	* метод</a> класса <b>CCatalogDiscountCoupon</b>.</p> <br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.setcoupon.php
	* @author Bitrix
	*/
	static public function SetCoupon($coupon)
	{
		return CCatalogDiscountCoupon::SetCoupon($coupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::GetCoupons()
*/
	
	/**
	* <p>Метод возвращает массив доступных для получения скидки купонов текущего покупателя. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @return array <p>Метод возвращает массив купонов текущего пользователя.</p>
	* <h4>Примечание</h4><p>С версии 12.0 метод считается устаревшим.
	* Оставлен для совместимости. Рекомендуется использовать <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getcoupons.php">аналогичный
	* метод</a> класса <b>CCatalogDiscountCoupon</b>.</p> <br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getcoupons.php
	* @author Bitrix
	*/
	static public function GetCoupons()
	{
		return CCatalogDiscountCoupon::GetCoupons();
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::EraseCoupon()
*/
	static public function EraseCoupon($strCoupon)
	{
		return CCatalogDiscountCoupon::EraseCoupon($strCoupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::ClearCoupon()
*/
	
	/**
	* <p>Метод очищает массив купонов, введенных текущим покупателем. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @return void <p>Метод не возвращает значений.</p> <h4>Примечание</h4><p>С версии 12.0
	* метод считается устаревшим. Оставлен для совместимости.
	* Рекомендуется использовать <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/clearcoupon.php">аналогичный
	* метод</a> класса <b>CCatalogDiscountCoupon</b>.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.clearcoupon.php
	* @author Bitrix
	*/
	static public function ClearCoupon()
	{
		CCatalogDiscountCoupon::ClearCoupon();
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::SetCouponByManage()
*/
	static public function SetCouponByManage($intUserID,$strCoupon)
	{
		return CCatalogDiscountCoupon::SetCouponByManage($intUserID,$strCoupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::GetCouponsByManage()
*/
	static public function GetCouponsByManage($intUserID)
	{
		return CCatalogDiscountCoupon::GetCouponsByManage($intUserID);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::EraseCouponByManage()
*/
	static public function EraseCouponByManage($intUserID,$strCoupon)
	{
		return CCatalogDiscountCoupon::EraseCouponByManage($intUserID,$strCoupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::ClearCouponsByManage()
*/
	static public function ClearCouponsByManage($intUserID)
	{
		return CCatalogDiscountCoupon::ClearCouponsByManage($intUserID);
	}

	static public function OnCurrencyDelete($Currency)
	{
		if (empty($Currency)) return false;

		$dbDiscounts = CCatalogDiscount::GetList(array(), array("CURRENCY" => $Currency), false, false, array("ID"));
		while ($arDiscounts = $dbDiscounts->Fetch())
		{
			CCatalogDiscount::Delete($arDiscounts["ID"]);
		}

		return true;
	}

	static public function OnGroupDelete($GroupID)
	{
		global $DB;
		$GroupID = (int)$GroupID;

		return $DB->Query("DELETE FROM b_catalog_discount2group WHERE GROUP_ID = ".$GroupID, true);
	}

/*
* @deprecated deprecated since catalog 12.0.0
*/
	static public function GenerateDataFile($ID)
	{
	}

/*
* @deprecated deprecated since catalog 12.0.0
*/
	static public function ClearFile($ID, $strDataFileName = false)
	{
	}

	
	/**
	* <p>Метод вычисляет скидку на цену с кодом productPriceID товара для пользователя, принадлежащего группам пользователей arUserGroups.</p>
	*
	*
	*
	*
	* @param int $productPriceID  Код цены.</bod
	*
	*
	*
	* @param  $array  Массив групп, которым принадлежит пользователь. Для текущего
	* пользователя он возвращается методом $USER-&gt;GetUserGroupArray()
	*
	*
	*
	* @param arUserGroup $s = array()[ Флаг "Продление подписки".
	*
	*
	*
	* @param string $renewal = "N"[ Сайт (по умолчанию текущий).
	*
	*
	*
	* @param string $siteID = false[ Массив купонов, которые влияют на выборку скидок. Если задано
	* значение <i>false</i>, то массив купонов будет взят из <b>
	* CCatalogDiscountCoupon::GetCoupons</b>
	*
	*
	*
	* @param array $arDiscountCoupons = false]]]] 
	*
	*
	*
	* @return bool <p>Метод возвращает массив ассоциативных массивов скидок или
	* <i>false</i> в случае ошибки. В массиве содержится ассоциативный
	* массив параметров максимальной процентной скидки (если есть) и
	* ассоциативный массив параметров максимальной фиксированной
	* скидки (если есть). <a name="examples"></a> </p>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $dbPrice = CPrice::GetList(
	*     array("QUANTITY_FROM" =&gt; "ASC", "QUANTITY_TO" =&gt; "ASC", 
	*           "SORT" =&gt; "ASC"),
	*     array("PRODUCT_ID" =&gt; $ID),
	*     false,
	*     false,
	*     array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", 
	*           "QUANTITY_FROM", "QUANTITY_TO")
	* );
	* while ($arPrice = $dbPrice-&gt;Fetch())
	* {
	*     $arDiscounts = CCatalogDiscount::GetDiscountByPrice(
	*             $arPrice["ID"],
	*             $USER-&gt;GetUserGroupArray(),
	*             "N",
	*             SITE_ID
	*         );
	*     $discountPrice = CCatalogProduct::CountPriceWithDiscount(
	*             $arPrice["PRICE"],
	*             $arPrice["CURRENCY"],
	*             $arDiscounts
	*         );
	*     $arPrice["DISCOUNT_PRICE"] = $discountPrice;
	* 
	*     echo "&lt;pre&gt;&amp;quot;;
	*     print_r($arPrice);
	*     echo &amp;quot;&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyprice.php
	* @author Bitrix
	*/
	static public function GetDiscountByPrice($productPriceID, $arUserGroups = array(), $renewal = "N", $siteID = false, $arDiscountCoupons = false)
	{
		global $APPLICATION;

		foreach (GetModuleEvents("catalog", "OnGetDiscountByPrice", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($productPriceID, $arUserGroups, $renewal, $siteID, $arDiscountCoupons));
			if (true !== $mxResult)
				return $mxResult;
		}

		$productPriceID = (int)$productPriceID;
		if ($productPriceID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_DISC_ERR_PRICE_ID_ABSENT"), "NO_PRICE_ID");
			return false;
		}

		$dbPrice = CPrice::GetListEx(
			array(),
			array("ID" => $productPriceID),
			false,
			false,
			array("ID", "PRODUCT_ID", "CATALOG_GROUP_ID", "ELEMENT_IBLOCK_ID")
		);
		if ($arPrice = $dbPrice->Fetch())
		{
			return CCatalogDiscount::GetDiscount($arPrice["PRODUCT_ID"], $arPrice["ELEMENT_IBLOCK_ID"], $arPrice["CATALOG_GROUP_ID"], $arUserGroups, $renewal, $siteID, $arDiscountCoupons);
		}
		else
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $productPriceID, GetMessage("BT_MOD_CATALOG_DISC_ERR_PRICE_ID_NOT_FOUND")), "NO_PRICE");
			return false;
		}
	}

	
	/**
	* <p>Метод вычисляет скидку на товар с кодом productID для пользователя, принадлежащего группам пользователей arUserGroups.</p>
	*
	*
	*
	*
	* @param int $productID = 0[ Код товара.
	*
	*
	*
	* @param array $arUserGroups = array()[ Массив групп, которым принадлежит пользователь. Для текущего
	* пользователя он возвращается методом $USER-&gt;GetUserGroupArray()
	*
	*
	*
	* @param string $renewal = "N"[ Флаг "Продление подписки"
	*
	*
	*
	* @param array $arCatalogGroups = array()[ Массив типов цен, для которых искать скидку.
	*
	*
	*
	* @param string $siteID = false[ Сайт (по умолчанию текущий)
	*
	*
	*
	* @param array $arDiscountCoupons = false]]]] Массив купонов, которые влияют на выборку скидок. Если задано
	* значение <i>false</i>, то массив купонов будет взят из <b>
	* CCatalogDiscountCoupon::GetCoupons</b>
	*
	*
	*
	* @return mixed <p>Метод возвращает массив ассоциативных массивов скидок или
	* <i>false</i> в случае ошибки. В массиве содержится ассоциативный
	* массив параметров максимальной процентной скидки (если есть) и
	* ассоциативный массив параметров максимальной фиксированной
	* скидки (если есть).</p> <a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arDiscounts = CCatalogDiscount::GetDiscountByProduct(
	*         150,
	*         $USER-&gt;GetUserGroupArray(),
	*         "N",
	*         2,
	*         SITE_ID
	*     );
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyproduct.php
	* @author Bitrix
	*/
	static public function GetDiscountByProduct($productID = 0, $arUserGroups = array(), $renewal = "N", $arCatalogGroups = array(), $siteID = false, $arDiscountCoupons = false)
	{
		global $APPLICATION;

		foreach (GetModuleEvents("catalog", "OnGetDiscountByProduct", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($productID, $arUserGroups, $renewal, $arCatalogGroups, $siteID, $arDiscountCoupons));
			if (true !== $mxResult)
				return $mxResult;
		}

		$productID = (int)$productID;
		if ($productID <= 0)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $productID, GetMessage("BT_MOD_CATALOG_DISC_ERR_ELEMENT_ID_NOT_FOUND")), "NO_ELEMENT");
			return false;
		}

		$intIBlockID = CIBlockElement::GetIBlockByID($productID);
		if ($intIBlockID === false)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $productID, GetMessage("BT_MOD_CATALOG_DISC_ERR_ELEMENT_ID_NOT_FOUND")), "NO_ELEMENT");
			return false;
		}

		return CCatalogDiscount::GetDiscount($productID, $intIBlockID, $arCatalogGroups, $arUserGroups, $renewal, $siteID, $arDiscountCoupons);
	}

	static public function GetDiscount($intProductID, $intIBlockID, $arCatalogGroups = array(), $arUserGroups = array(), $strRenewal = "N", $siteID = false, $arDiscountCoupons = false, $boolSKU = true, $boolGetIDS = false)
	{
		global $DB;
		global $APPLICATION;

		foreach (GetModuleEvents("catalog", "OnGetDiscount", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($intProductID, $intIBlockID, $arCatalogGroups, $arUserGroups, $strRenewal, $siteID, $arDiscountCoupons, $boolSKU, $boolGetIDS));
			if ($mxResult !== true)
				return $mxResult;
		}

		$boolSKU = ($boolSKU === true);
		$boolGetIDS = ($boolGetIDS === true);

		$intProductID = (int)$intProductID;
		if ($intProductID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_DISC_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_DISC_ERR_IBLOCK_ID_ABSENT"), "NO_IBLOCK_ID");
			return false;
		}

		if (!is_array($arCatalogGroups))
		{
			$arCatalogGroups = array($arCatalogGroups);
		}
		if (!empty($arCatalogGroups))
		{
			CatalogClearArray($arCatalogGroups);
		}

		if (!is_array($arUserGroups))
		{
			$arUserGroups = array($arUserGroups);
		}
		$arUserGroups[] = 2;
		if (!empty($arUserGroups))
		{
			CatalogClearArray($arUserGroups);
		}

		$strRenewal = ($strRenewal == "Y" ? "Y" : "N");

		if ($siteID === false)
			$siteID = SITE_ID;

		if ($arDiscountCoupons === false)
			$arDiscountCoupons = CCatalogDiscountCoupon::GetCoupons();

		$arSKU = false;
		$arSKUExt = false;
		if ($boolSKU)
		{
			$arSKUExt = CCatalogSKU::GetInfoByOfferIBlock($intIBlockID);
			$boolSKU = !empty($arSKUExt);
		}

		$arResult = array();
		$arResultID = array();

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		if (!isset(self::$arCacheDiscountFilter[$strCacheKey]))
		{
			$arFilter = array(
				'PRICE_TYPE_ID' => $arCatalogGroups,
				'USER_GROUP_ID' => $arUserGroups,
			);
			$arDiscountIDs = CCatalogDiscount::__GetDiscountID($arFilter);
			if (!empty($arDiscountIDs))
			{
				sort($arDiscountIDs);
			}
			self::$arCacheDiscountFilter[$strCacheKey] = $arDiscountIDs;
		}
		else
		{
			$arDiscountIDs = self::$arCacheDiscountFilter[$strCacheKey];
		}

		$arProduct = array();

		if (!empty($arDiscountIDs))
		{
			$boolGenerate = false;
			if (empty(self::$cacheDiscountHandlers))
			{
				self::$cacheDiscountHandlers = CCatalogDiscount::getDiscountHandlers($arDiscountIDs);
			}
			else
			{
				$needDiscountHandlers = array();
				foreach ($arDiscountIDs as &$discountID)
				{
					if (!isset(self::$cacheDiscountHandlers[$discountID]))
						$needDiscountHandlers[] = $discountID;
				}
				unset($discountID);
				if (!empty($needDiscountHandlers))
				{
					$discountHandlersList = CCatalogDiscount::getDiscountHandlers($needDiscountHandlers);
					if (!empty($discountHandlersList))
					{
						foreach ($discountHandlersList as $discountID => $discountHandlers)
						{
							self::$cacheDiscountHandlers[$discountID] = $discountHandlers;
						}
						unset($discountHandlers, $discountID);
					}
					unset($discountHandlersList);
				}
				unset($needDiscountHandlers);
			}

			$strCacheKey = 'D'.implode('_', $arDiscountIDs).'-'.'S'.$siteID.'-R'.$strRenewal;
			if (!empty($arDiscountCoupons))
			{
				$strCacheKey .= '-C'.implode('|', $arDiscountCoupons);
			}
			$strCacheKey = md5($strCacheKey);

			if (!isset(self::$arCacheDiscountResult[$strCacheKey]))
			{
				$arSelect = array(
					'ID', 'TYPE', 'SITE_ID', 'ACTIVE', 'ACTIVE_FROM', 'ACTIVE_TO',
					'RENEWAL', 'NAME', 'SORT', 'MAX_DISCOUNT', 'VALUE_TYPE', 'VALUE', 'CURRENCY',
					'PRIORITY', 'LAST_DISCOUNT',
					'COUPON', 'COUPON_ONE_TIME', 'COUPON_ACTIVE', 'UNPACK'
				);
				$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
				$arFilter = array(
					'ID' => $arDiscountIDs,
					'SITE_ID' => $siteID,
					'TYPE' => self::ENTITY_ID,
					'RENEWAL' => $strRenewal,
					'+<=ACTIVE_FROM' => $strDate,
					'+>=ACTIVE_TO' => $strDate
				);

				if (is_array($arDiscountCoupons))
				{
					$arFilter["+COUPON"] = $arDiscountCoupons;
				}

				$arDiscountList = array();
				CTimeZone::Disable();
				$rsPriceDiscounts = CCatalogDiscount::GetList(
					array(),
					$arFilter,
					false,
					false,
					$arSelect
				);
				CTimeZone::Enable();
				while ($arPriceDiscount = $rsPriceDiscounts->Fetch())
				{
					$arPriceDiscount['HANDLERS'] = array();
					$arDiscountList[] = $arPriceDiscount;
				}
				self::$arCacheDiscountResult[$strCacheKey] = $arDiscountList;
			}
			else
			{
				$arDiscountList = self::$arCacheDiscountResult[$strCacheKey];
			}

			if (!empty($arDiscountList))
			{
				$discountApply = array();
				foreach ($arDiscountList as &$arPriceDiscount)
				{
					if (!isset($discountApply[$arPriceDiscount['ID']]) && $arPriceDiscount['COUPON_ACTIVE'] != 'N')
					{
						if (!$boolGenerate)
						{
							if (!isset(self::$arCacheProduct[$intProductID]))
							{
								$arProduct = array('ID' => $intProductID, 'IBLOCK_ID' => $intIBlockID);
								if (!self::__GenerateFields($arProduct))
									return false;
								if ($boolSKU)
								{
									if (!self::__GenerateParent($arProduct, $arSKUExt))
										$boolSKU = false;
								}
								$boolGenerate = true;
								self::$arCacheProduct[$intProductID] = $arProduct;
							}
							else
							{
								$boolGenerate = true;
								$arProduct = self::$arCacheProduct[$intProductID];
							}
						}
						$discountApply[$arPriceDiscount['ID']] = true;
						$applyFlag = true;
						if (isset(self::$cacheDiscountHandlers[$arPriceDiscount['ID']]))
						{
							$arPriceDiscount['HANDLERS'] = self::$cacheDiscountHandlers[$arPriceDiscount['ID']];
							$moduleList = self::$cacheDiscountHandlers[$arPriceDiscount['ID']]['MODULES'];
							if (!empty($moduleList))
							{
								foreach ($moduleList as &$moduleID)
								{
									if (!isset(self::$usedModules[$moduleID]))
									{
										self::$usedModules[$moduleID] = Loader::includeModule($moduleID);
									}
									if (!self::$usedModules[$moduleID])
									{
										$applyFlag = false;
										break;
									}
								}
								unset($moduleID);
							}
							unset($moduleList);
						}
						if ($applyFlag && CCatalogDiscount::__Unpack($arProduct, $arPriceDiscount['UNPACK']))
						{
							unset($arPriceDiscount['UNPACK']);
							$arResult[] = $arPriceDiscount;
							$arResultID[] = $arPriceDiscount['ID'];
						}
					}
				}
				if (isset($arPriceDiscount))
					unset($arPriceDiscount);
				unset($discountApply);
			}
		}

		if (!$boolGetIDS)
		{
			$arDiscSave = CCatalogDiscountSave::GetDiscount(array(
				'USER_ID' => 0,
				'USER_GROUPS' => $arUserGroups,
				'SITE_ID' => $siteID
			));
			if (!empty($arDiscSave))
			{
				$arResult = (!empty($arResult) ? array_merge($arResult, $arDiscSave) : $arDiscSave);
			}
		}
		else
		{
			$arResult = $arResultID;
		}

		foreach (GetModuleEvents("catalog", "OnGetDiscountResult", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$arResult));
		}

		return $arResult;
	}

	static public function HaveCoupons($ID, $excludeID = 0)
	{
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$arFilter = array("DISCOUNT_ID" => $ID);

		$excludeID = (int)$excludeID;
		if ($excludeID > 0)
			$arFilter["!ID"] = $excludeID;

		$dbRes = CCatalogDiscountCoupon::GetList(array(), $arFilter, false, array("nTopCount" => 1), array("ID"));
		if ($dbRes->Fetch())
			return true;
		else
			return false;
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::OnSetCouponList()
*/
	static public function OnSetCouponList($intUserID, $arCoupons, $arModules)
	{
		return CCatalogDiscountCoupon::OnSetCouponList($intUserID, $arCoupons, $arModules);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::OnClearCouponList()
*/
	static public function OnClearCouponList($intUserID, $arCoupons, $arModules)
	{
		return CCatalogDiscountCoupon::OnClearCouponList($intUserID, $arCoupons, $arModules);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::OnDeleteCouponList()
*/
	static public function OnDeleteCouponList($intUserID, $arModules)
	{
		return CCatalogDiscountCoupon::OnDeleteCouponList($intUserID, $arModules);
	}

	static public function GetDiscountForProduct($arProduct, $arParams = false)
	{
		global $DB;

		$arResult = array();
		$arResultID = array();
		if (is_array($arProduct) && !empty($arProduct))
		{
			if (!is_array($arParams))
				$arParams = array();

			if (!isset($arProduct['ID']))
				$arProduct['ID'] = 0;
			$arProduct['ID'] = (int)$arProduct['ID'];
			if (!isset($arProduct['IBLOCK_ID']))
				$arProduct['IBLOCK_ID'] = 0;
			$arProduct['IBLOCK_ID'] = (int)$arProduct['IBLOCK_ID'];
			if ($arProduct['IBLOCK_ID'] <= 0)
				return $arResult;

			$arFieldsParams = array();
			if (isset($arParams['TIME_ZONE']))
				$arFieldsParams['TIME_ZONE'] = $arParams['TIME_ZONE'];
			if (isset($arParams['PRODUCT']))
				$arFieldsParams['PRODUCT'] = $arParams['PRODUCT'];
			$boolGenerate = false;

			$arSelect = array("ID", "SITE_ID", "SORT", "NAME", "VALUE_TYPE", "VALUE", "CURRENCY", 'UNPACK');
			if (isset($arParams['DISCOUNT_FIELDS']) && !empty($arParams['DISCOUNT_FIELDS']) && is_array($arParams['DISCOUNT_FIELDS']))
				$arSelect = $arParams['DISCOUNT_FIELDS'];
			if (!in_array('UNPACK', $arSelect))
				$arSelect[] = 'UNPACK';

			$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
			if (isset($arParams['CURRENT_DATE']))
				$strDate = $arParams['CURRENT_DATE'];

			$strRenewal = 'N';
			if (isset($arParams['RENEWAL']))
			{
				$strRenewal = $arParams['RENEWAL'];
			}
			if ('Y' != $strRenewal)
				$strRenewal = 'N';

			$arSiteList = array();
			if (isset($arParams['SITE_ID']))
			{
				if (!is_array($arParams['SITE_ID']))
					$arParams['SITE_ID'] = array($arParams['SITE_ID']);
				if (!empty($arParams['SITE_ID']))
					$arSiteList = $arParams['SITE_ID'];
			}
			if (empty($arSiteList))
			{
				$rsIBlockSites = CIBlock::GetSite($arProduct['IBLOCK_ID']);
				while ($arIBlockSite = $rsIBlockSites->Fetch())
				{
					$arSiteList[] = $arIBlockSite['SITE_ID'];
				}
			}

			$arFilter = array(
				"SITE_ID" => $arSiteList,
				"TYPE" => self::ENTITY_ID,
				"ACTIVE" => "Y",
				"RENEWAL" => $strRenewal,
				"+<=ACTIVE_FROM" => $strDate,
				"+>=ACTIVE_TO" => $strDate,
				'COUPON' => ''
			);
			CTimeZone::Disable();
			$rsPriceDiscounts = CCatalogDiscount::GetList(
				array(),
				$arFilter,
				false,
				false,
				$arSelect
			);
			CTimeZone::Enable();
			while ($arPriceDiscount = $rsPriceDiscounts->Fetch())
			{
				if ($arPriceDiscount['COUPON_ACTIVE'] != 'N')
				{
					if (!$boolGenerate)
					{
						if (!isset(self::$arCacheProduct[$arProduct['ID']]))
						{
							if (!self::__GenerateFields($arProduct, $arFieldsParams))
								return $arResult;
							$boolGenerate = true;
							self::$arCacheProduct[$arProduct['ID']] = $arProduct['ID'];
						}
						else
						{
							$arProduct = self::$arCacheProduct[$arProduct['ID']];
						}
					}
					if (CCatalogDiscount::__Unpack($arProduct, $arPriceDiscount['UNPACK']))
					{
						unset($arPriceDiscount['UNPACK']);
						$arResult[] = $arPriceDiscount;
						$arResultID[] = $arPriceDiscount['ID'];
					}
				}
			}

			if (isset($arParams['SKU']) && 'Y' == $arParams['SKU'])
			{
				$arSKU = (
					isset($arParams['SKU_PARAMS'])
					? $arParams['SKU_PARAMS']
					: CCatalogSKU::GetInfoByOfferIBlock($arProduct['IBLOCK_ID'])
				);
				if (!empty($arSKU) && is_array($arSKU))
				{
					$arParent = array();
					$arParent['ID'] = 0;
					$arParent['IBLOCK_ID'] = $arSKU['PRODUCT_IBLOCK_ID'];
					if (
						isset($arProduct['PROPERTY_'.$arSKU['SKU_PROPERTY_ID'].'_VALUE'])
						&& is_array($arProduct['PROPERTY_'.$arSKU['SKU_PROPERTY_ID'].'_VALUE'])
					)
					{
						$intParentID = (int)current($arProduct['PROPERTY_'.$arSKU['SKU_PROPERTY_ID'].'_VALUE']);
						if ($intParentID > 0)
						{
							$arParent['ID'] = $intParentID;
						}
					}
					$arParentParams = array();
					if (isset($arParams['TIME_ZONE']))
						$arParentParams['TIME_ZONE'] = $arParams['TIME_ZONE'];
					if (isset($arParams['DISCOUNT_FIELDS']))
						$arParentParams['DISCOUNT_FIELDS'] = $arParams['DISCOUNT_FIELDS'];
					$arParentParams['RENEWAL'] = $strRenewal;
					$arParentParams['SITE_ID'] = $arSiteList;
					$arParentParams['CURRENT_DATE'] = $strDate;
					$arDiscountParent = self::GetDiscountForProduct($arParent, $arParentParams);
					if (!empty($arDiscountParent))
					{
						if (empty($arResult))
						{
							$arResult = $arDiscountParent;
						}
						else
						{
							foreach ($arDiscountParent as &$arOneParentDiscount)
							{
								if (in_array($arOneParentDiscount['ID'], $arResultID))
									continue;
								$arResult[] = $arOneParentDiscount;
								$arResultID[] = $arOneParentDiscount['ID'];
							}
							if (isset($arOneParentDiscount))
								unset($arOneParentDiscount);
						}
					}
				}
			}
		}
		return $arResult;
	}

	static public function GetRestrictions($arParams, $boolKeys = true, $boolRevert = true)
	{
		$boolKeys = !!$boolKeys;
		$boolRevert = !!$boolRevert;
		if (!is_array($arParams) || empty($arParams))
			return array();
		$arFilter = array('RESTRICTIONS' => true);
		if (isset($arParams['USER_GROUPS']) && !empty($arParams['USER_GROUPS']))
		{
			$arFilter['USER_GROUP_ID'] = $arParams['USER_GROUPS'];
		}
		if (isset($arParams['PRICE_TYPES']) && !empty($arParams['PRICE_TYPES']))
		{
			$arFilter['PRICE_TYPE_ID'] = $arParams['PRICE_TYPES'];
		}
		if ($boolKeys)
		{
			return CCatalogDiscount::__GetDiscountID($arFilter);
		}
		else
		{
			$arResult = CCatalogDiscount::__GetDiscountID($arFilter);
			if (!empty($arResult) && !empty($arResult['RESTRICTIONS']))
			{
				if ($boolRevert)
				{
					foreach ($arResult['RESTRICTIONS'] as &$arOneDiscount)
					{
						$arOneDiscount['USER_GROUP'] = array_keys($arOneDiscount['USER_GROUP']);
						$arOneDiscount['PRICE_TYPE'] = array_keys($arOneDiscount['PRICE_TYPE']);
					}
					if (isset($arOneDiscount))
						unset($arOneDiscount);
				}
			}
			return $arResult;
		}
	}

	static public function CheckDiscount($arProduct, $arDiscount)
	{
		if (empty($arProduct) || !is_array($arProduct))
			return false;
		if (empty($arDiscount) || !is_array($arDiscount) || !isset($arDiscount['UNPACK']))
			return false;
		return CCatalogDiscount::__Unpack($arProduct, $arDiscount['UNPACK']);
	}

	static public function ExtendBasketItems(&$arBasket, $arExtend)
	{
		$arFields = array(
			'ID',
			'IBLOCK_ID',
			'XML_ID',
			'CODE',
			'TAGS',
			'NAME'
		);
		$arCatFields = array(
			'ID',
			'QUANTITY'
		);

		$boolFields = false;
		if (isset($arExtend['catalog']['fields']))
			$boolFields = (boolean)$arExtend['catalog']['fields'];
		$boolProps = false;
		if (isset($arExtend['catalog']['props']))
			$boolProps = (boolean)$arExtend['catalog']['props'];
		if ($boolFields || $boolProps)
		{
			$arMap = array();
			$arIDS = array();
			foreach ($arBasket as $strKey => $arOneRow)
			{
				if (isset($arOneRow['MODULE']) && 'catalog' == $arOneRow['MODULE'])
				{
					$intProductID = (int)$arOneRow['PRODUCT_ID'];
					if ($intProductID > 0)
					{
						$arIDS[$intProductID] = true;
						if (!isset($arMap[$intProductID]))
							$arMap[$intProductID] = array();
						$arMap[$intProductID][] = $strKey;
					}
				}
			}
			if (!empty($arIDS))
			{
				$arBasketResult = array();
				$iblockGroup = array();
				$arIDS = array_keys($arIDS);
				self::SetProductSectionsCache($arIDS);
				$rsItems = CIBlockElement::GetList(array(), array('ID' => $arIDS), false, false, $arFields);
				while ($arItem = $rsItems->Fetch())
				{
					$arBasketData = array();
					$arItem['ID'] = (int)$arItem['ID'];
					$arItem['IBLOCK_ID'] = (int)$arItem['IBLOCK_ID'];
					if (!isset($iblockGroup[$arItem['IBLOCK_ID']]))
						$iblockGroup[$arItem['IBLOCK_ID']] = array();
					$iblockGroup[$arItem['IBLOCK_ID']][] = $arItem['ID'];
					if ($boolFields)
					{
						$arBasketData['ID'] = $arItem['ID'];
						$arBasketData['IBLOCK_ID'] = $arItem['IBLOCK_ID'];
						$arBasketData['NAME'] = $arItem['NAME'];
						$arBasketData['XML_ID'] = (string)$arItem['XML_ID'];
						$arBasketData['CODE'] = (string)$arItem['CODE'];
						$arBasketData['TAGS'] = (string)$arItem['TAGS'];
						$arProductSections = self::__GetSectionList($arItem['IBLOCK_ID'], $arItem['ID']);
						if ($arProductSections !== false)
							$arBasketData['SECTION_ID'] = $arProductSections;
						else
							$arBasketData['SECTION_ID'] = array();
					}
					if ($boolProps)
					{
						$arBasketData['PROPERTIES'] = array();
					}
					$arBasketResult[$arItem['ID']] = $arBasketData;
				}
				if ($boolProps && !empty($iblockGroup))
				{
					foreach ($iblockGroup as $iblockID => $iblockItems)
					{
						$filter = array(
							'ID' => $iblockItems,
							'IBLOCK_ID' =>$iblockID
						);
						CIBlockElement::GetPropertyValuesArray($arBasketResult, $iblockID, $filter);
					}
					unset($iblockItems, $iblockID);
					foreach ($arBasketResult as &$basketItem)
					{
						self::__ConvertProperties($basketItem, $basketItem['PROPERTIES'], array('TIME_ZONE' => 'N'));
					}
					unset($basketItem);
				}
				$rsProducts = CCatalogProduct::GetList(array(), array('ID' => $arIDS), false, false, $arCatFields);
				while ($arProduct = $rsProducts->Fetch())
				{
					$arProduct['ID'] = (int)$arProduct['ID'];
					if (!isset($arBasketResult[$arProduct['ID']]))
						$arBasketResult[$arProduct['ID']] = array();
					$arBasketResult[$arProduct['ID']]['CATALOG_QUANTITY'] = doubleval($arProduct['QUANTITY']);
				}
				if (!empty($iblockGroup))
				{
					foreach ($iblockGroup as $iblockID => $iblockItems)
					{
						$sku = CCatalogSKU::GetInfoByOfferIBlock($iblockID);
						if (!empty($sku))
						{
							foreach ($iblockItems as $itemID)
							{
								$isSku = self::__GenerateParent($arBasketResult[$itemID], $sku);
							}
							unset($isSku, $itemID);
						}
					}
					unset($sku, $iblockItems, $iblockID);
				}

				if (!empty($arBasketResult))
				{
					foreach ($arBasketResult as $intProductID => $arBasketData)
					{
						foreach ($arMap[$intProductID] as $mxRowID)
						{
							$arBasket[$mxRowID]['CATALOG'] = $arBasketData;
						}
					}
				}
			}
		}
	}

	protected function __GenerateFields(&$arProduct, $arParams = false)
	{
		$boolResult = false;
		if (!empty($arProduct) && is_array($arProduct))
		{
			if (!isset($arProduct['IBLOCK_ID']))
				$arProduct['IBLOCK_ID'] = 0;
			$arProduct['IBLOCK_ID'] = (int)$arProduct['IBLOCK_ID'];
			if ($arProduct['IBLOCK_ID'] > 0)
			{
				if (!is_array($arParams))
					$arParams = array();

				if (!isset($arProduct['ID']))
					$arProduct['ID'] = 0;
				$arProduct['ID'] = (int)$arProduct['ID'];
				if ($arProduct['ID'] > 0)
				{
					if (isset($arParams['PRODUCT']) && $arParams['PRODUCT'] == 'Y')
					{
						$arDefaultProduct = array(
							'DATE_ACTIVE_FROM' => '',
							'DATE_ACTIVE_TO' => '',
							'SORT' => 0,
							'PREVIEW_TEXT' => '',
							'DETAIL_TEXT' => '',
							'TAGS' => '',
							'DATE_CREATE' => '',
							'TIMESTAMP_X' => '',
							'CREATED_BY' => 0,
							'MODIFIED_BY' => 0,
							'CATALOG_QUANTITY' => '',
							'CATALOG_WEIGHT' => '',
							'CATALOG_VAT_ID' => '',
							'CATALOG_VAT_INCLUDED' => ''
						);
						$arProduct = array_merge($arDefaultProduct, $arProduct);

						static $intTimeOffset = false;
						if (false === $intTimeOffset)
							$intTimeOffset = CTimeZone::GetOffset();
						if (isset($arParams['TIME_ZONE']) && 'N' == $arParams['TIME_ZONE'])
							$intTimeOffset = 0;

						if (!isset($arProduct['SECTION_ID']))
						{
							$arProductSections = self::__GetSectionList($arProduct['IBLOCK_ID'], $arProduct['ID']);
							if (false !== $arProductSections)
								$arProduct['SECTION_ID'] = $arProductSections;
							else
								$arProduct['SECTION_ID'] = array();
						}
						else
						{
							if (!is_array($arProduct['SECTION_ID']))
								$arProduct['SECTION_ID'] = array($arProduct['SECTION_ID']);
							CatalogClearArray($arProduct['SECTION_ID']);
						}

						if (!empty($arProduct['DATE_ACTIVE_FROM']))
						{
							$intStackTimestamp = (int)$arProduct['DATE_ACTIVE_FROM'];
							if ($intStackTimestamp.'!' != $arProduct['DATE_ACTIVE_FROM'].'!')
								$arProduct['DATE_ACTIVE_FROM'] = (int)MakeTimeStamp($arProduct['DATE_ACTIVE_FROM']) - $intTimeOffset;
							else
								$arProduct['DATE_ACTIVE_FROM'] = $intStackTimestamp;
						}

						if (!empty($arProduct['DATE_ACTIVE_TO']))
						{
							$intStackTimestamp = (int)$arProduct['DATE_ACTIVE_TO'];
							if ($intStackTimestamp.'!' != $arProduct['DATE_ACTIVE_TO'].'!')
								$arProduct['DATE_ACTIVE_TO'] = (int)MakeTimeStamp($arProduct['DATE_ACTIVE_TO']) - $intTimeOffset;
							else
								$arProduct['DATE_ACTIVE_TO'] = $intStackTimestamp;
						}

						$arProduct['SORT'] = (int)$arProduct['SORT'];

						if (!empty($arProduct['DATE_CREATE']))
						{
							$intStackTimestamp = (int)$arProduct['DATE_CREATE'];
							if ($intStackTimestamp.'!' != $arProduct['DATE_CREATE'].'!')
								$arProduct['DATE_CREATE'] = (int)MakeTimeStamp($arProduct['DATE_CREATE']) - $intTimeOffset;
							else
								$arProduct['DATE_CREATE'] = $intStackTimestamp;
						}

						if (!empty($arProduct['TIMESTAMP_X']))
						{
							$intStackTimestamp = (int)$arProduct['TIMESTAMP_X'];
							if ($intStackTimestamp.'!' != $arProduct['TIMESTAMP_X'].'!')
								$arProduct['TIMESTAMP_X'] = (int)MakeTimeStamp($arProduct['TIMESTAMP_X']) - $intTimeOffset;
							else
								$arProduct['TIMESTAMP_X'] = $intStackTimestamp;
						}

						$arProduct['CREATED_BY'] = (int)$arProduct['CREATED_BY'];
						$arProduct['MODIFIED_BY'] = (int)$arProduct['MODIFIED_BY'];

						if (isset($arProduct['QUANTITY']))
						{
							$arProduct['CATALOG_QUANTITY'] = $arProduct['QUANTITY'];
							unset($arProduct['QUANTITY']);
						}
						if ('' != $arProduct['CATALOG_QUANTITY'])
							$arProduct['CATALOG_QUANTITY'] = doubleval($arProduct['CATALOG_QUANTITY']);

						if (isset($arProduct['WEIGHT']))
						{
							$arProduct['CATALOG_WEIGHT'] = $arProduct['WEIGHT'];
							unset($arProduct['WEIGHT']);
						}
						if ('' != $arProduct['CATALOG_WEIGHT'])
						$arProduct['CATALOG_WEIGHT'] = doubleval($arProduct['CATALOG_WEIGHT']);

						if (isset($arProduct['VAT_ID']))
						{
							$arProduct['CATALOG_VAT_ID'] = $arProduct['VAT_ID'];
							unset($arProduct['VAT_ID']);
						}
						if ('' != $arProduct['CATALOG_VAT_ID'])
							$arProduct['CATALOG_VAT_ID'] = (int)$arProduct['CATALOG_VAT_ID'];

						if (isset($arProduct['VAT_INCLUDED']))
						{
							$arProduct['CATALOG_VAT_INCLUDED'] = $arProduct['VAT_INCLUDED'];
							unset($arProduct['VAT_INCLUDED']);
						}

						$arPropParams = array();
						if (isset($arParams['TIME_ZONE']) && 'N' == $arParams['TIME_ZONE'])
							$arPropParams['TIME_ZONE'] = 'N';

						if (isset($arProduct['PROPERTIES']))
						{
							if (!empty($arProduct['PROPERTIES']) && is_array($arProduct['PROPERTIES']))
							{
								self::__ConvertProperties($arProduct, $arProduct['PROPERTIES'], $arPropParams);
							}
							unset($arProduct['PROPERTIES']);
						}
					}
					else
					{
						$arSelect = array('ID', 'IBLOCK_ID', 'CODE', 'XML_ID', 'NAME', 'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO',
							'SORT', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'DATE_CREATE', 'DATE_CREATE_UNIX', 'CREATED_BY', 'TIMESTAMP_X', 'TIMESTAMP_X_UNIX', 'MODIFIED_BY', 'TAGS', 'CATALOG_QUANTITY');
						CTimeZone::Disable();
						$rsProducts = CIBlockElement::GetList(array(), array('ID' => $arProduct['ID'], 'IBLOCK_ID' => $arProduct['IBLOCK_ID']), false, false, $arSelect);
						CTimeZone::Enable();
						if (!($obProduct = $rsProducts->GetNextElement(false,true)))
							return $boolResult;

						$arProduct = array();
						$arProductFields = $obProduct->GetFields();

						$arProduct['ID'] = (int)$arProductFields['ID'];
						$arProduct['IBLOCK_ID'] = (int)$arProductFields['IBLOCK_ID'];

						$arProduct['SECTION_ID'] = array();
						$arProductSections = self::__GetSectionList($arProduct['IBLOCK_ID'], $arProduct['ID']);
						if (false !== $arProductSections)
							$arProduct['SECTION_ID'] = $arProductSections;

						$arProduct['CODE'] = (string)$arProductFields['~CODE'];
						$arProduct['XML_ID'] = (string)$arProductFields['~XML_ID'];
						$arProduct['NAME'] = $arProductFields['~NAME'];

						$arProduct['ACTIVE'] = $arProductFields['ACTIVE'];

						$arProduct['DATE_ACTIVE_FROM'] = (string)$arProductFields['DATE_ACTIVE_FROM'];
						if (!empty($arProduct['DATE_ACTIVE_FROM']))
							$arProduct['DATE_ACTIVE_FROM'] = (int)MakeTimeStamp($arProduct['DATE_ACTIVE_FROM']);

						$arProduct['DATE_ACTIVE_TO'] = (string)$arProductFields['DATE_ACTIVE_TO'];
						if (!empty($arProduct['DATE_ACTIVE_TO']))
							$arProduct['DATE_ACTIVE_TO'] = (int)MakeTimeStamp($arProduct['DATE_ACTIVE_TO']);

						$arProduct['SORT'] = (int)$arProductFields['SORT'];

						$arProduct['PREVIEW_TEXT'] = (string)$arProductFields['~PREVIEW_TEXT'];
						$arProduct['DETAIL_TEXT'] = (string)$arProductFields['~DETAIL_TEXT'];
						$arProduct['TAGS'] = (string)$arProductFields['~TAGS'];

						if (isset($arProductFields['DATE_CREATE_UNIX']))
						{
							$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE_UNIX'];
							if ('' != $arProduct['DATE_CREATE'])
								$arProduct['DATE_CREATE'] = (int)$arProduct['DATE_CREATE'];
						}
						else
						{
							$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE'];
							if ('' != $arProduct['DATE_CREATE'])
								$arProduct['DATE_CREATE'] = (int)MakeTimeStamp($arProduct['DATE_CREATE']);
						}

						if (isset($arProductFields['TIMESTAMP_X_UNIX']))
						{
							$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X_UNIX'];
							if ('' != $arProduct['TIMESTAMP_X'])
								$arProduct['TIMESTAMP_X'] = (int)$arProduct['TIMESTAMP_X'];
						}
						else
						{
							$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X'];
							if ('' != $arProduct['TIMESTAMP_X'])
								$arProduct['TIMESTAMP_X'] = (int)MakeTimeStamp($arProduct['TIMESTAMP_X']);
						}

						$arProduct['CREATED_BY'] = (int)$arProductFields['CREATED_BY'];
						$arProduct['MODIFIED_BY'] = (int)$arProductFields['MODIFIED_BY'];

						$arProduct['CATALOG_QUANTITY'] = (string)$arProductFields['CATALOG_QUANTITY'];
						if ('' != $arProduct['CATALOG_QUANTITY'])
							$arProduct['CATALOG_QUANTITY'] = doubleval($arProduct['CATALOG_QUANTITY']);
						$arProduct['CATALOG_WEIGHT'] = (string)$arProductFields['CATALOG_WEIGHT'];
						if ('' != $arProduct['CATALOG_WEIGHT'])
							$arProduct['CATALOG_WEIGHT'] = doubleval($arProduct['CATALOG_WEIGHT']);

						$arProduct['CATALOG_VAT_ID'] = (string)$arProductFields['CATALOG_VAT_ID'];
						if ('' != $arProduct['CATALOG_VAT_ID'])
							$arProduct['CATALOG_VAT_ID'] = (int)$arProduct['CATALOG_VAT_ID'];

						$arProduct['CATALOG_VAT_INCLUDED'] = (string)$arProductFields['CATALOG_VAT_INCLUDED'];

						unset($arProductFields);
						if (!isset(self::$arCacheProductProperties[$arProduct['ID']]))
						{
							$arProps = $obProduct->GetProperties(array(), array('ACTIVE' => 'Y'));
						}
						else
						{
							$arProps = self::$arCacheProductProperties[$arProduct['ID']];
						}
						self::__ConvertProperties($arProduct, $arProps, array('TIME_ZONE' => 'N'));
						if (isset(self::$arCacheProductProperties[$arProduct['ID']]))
							unset(self::$arCacheProductProperties[$arProduct['ID']]);
						if (isset(self::$arCacheProductSections[$arProduct['ID']]))
							unset(self::$arCacheProductSections[$arProduct['ID']]);
					}
				}
				else
				{
					$arProduct['ID'] = 0;
					if (!isset($arProduct['SECTION_ID']))
						$arProduct['SECTION_ID'] = array();
					if (!is_array($arProduct['SECTION_ID']))
						$arProduct['SECTION_ID'] = array($arProduct['SECTION_ID']);
					CatalogClearArray($arProduct['SECTION_ID']);

					$arProduct['DATE_ACTIVE_FROM'] = '';
					$arProduct['DATE_ACTIVE_TO'] = '';
					$arProduct['SORT'] = 500;

					$arProduct['PREVIEW_TEXT'] = '';
					$arProduct['DETAIL_TEXT'] = '';
					$arProduct['TAGS'] = '';

					$arProduct['DATE_CREATE'] = '';
					$arProduct['TIMESTAMP_X'] = '';

					$arProduct['CREATED_BY'] = 0;
					$arProduct['MODIFIED_BY'] = 0;

					$arProduct['CATALOG_QUANTITY'] = '';
					$arProduct['CATALOG_WEIGHT'] = '';
					$arProduct['CATALOG_VAT_ID'] = '';
					$arProduct['CATALOG_VAT_INCLUDED'] = '';
				}
				$boolResult = true;
			}
		}
		return $boolResult;
	}

	protected function __GetSectionList($intIBlockID, $intProductID)
	{
		$mxResult = false;
		$intIBlockID = (int)$intIBlockID;
		$intProductID = (int)$intProductID;
		if ($intIBlockID > 0 && $intProductID > 0)
		{
			$mxResult = array();
			$arProductSections = array();
			if (!isset(self::$arCacheProductSections[$intProductID]))
			{
				$rsSections = CIBlockElement::GetElementGroups($intProductID, true, array("ID", "IBLOCK_SECTION_ID", "IBLOCK_ELEMENT_ID"));
				while ($arSection = $rsSections->Fetch())
				{
					$arSection['ID'] = (int)$arSection['ID'];
					$arSection['IBLOCK_SECTION_ID'] = (int)$arSection['IBLOCK_SECTION_ID'];
					$arProductSections[] = $arSection;
				}
				if (isset($arSection))
					unset($arSection);
				self::$arCacheProductSections[$intProductID] = $arProductSections;
			}
			else
			{
				$arProductSections = self::$arCacheProductSections[$intProductID];
			}
			if (!empty($arProductSections))
			{
				foreach ($arProductSections as &$arSection)
				{
					$mxResult[$arSection['ID']] = true;
					if (0 < $arSection['IBLOCK_SECTION_ID'])
					{
						if (!isset(self::$arCacheProductSectionChain[$arSection['ID']]))
						{
							self::$arCacheProductSectionChain[$arSection['ID']] = array();
							$rsParents = CIBlockSection::GetNavChain($intIBlockID, $arSection['ID'], array('ID'));
							while ($arParent = $rsParents->Fetch())
							{
								$arParent['ID'] = (int)$arParent['ID'];
								$mxResult[$arParent['ID']] = true;
								self::$arCacheProductSectionChain[$arSection['ID']][] = $arParent["ID"];
							}
						}
						else
						{
							foreach (self::$arCacheProductSectionChain[$arSection['ID']] as $intOneID)
							{
								$mxResult[$intOneID] = true;
							}
							if (isset($intOneID))
								unset($intOneID);
						}
					}
				}
				if (isset($arSection))
					unset($arSection);
			}
			if (!empty($mxResult))
			{
				$mxResult = array_keys($mxResult);
				sort($mxResult);
			}
		}
		return $mxResult;
	}

	protected function __ConvertProperties(&$arProduct, &$arProps, $arParams = false)
	{
		if (!empty($arProps) && is_array($arProps))
		{
			if (!is_array($arParams))
				$arParams = array();
			static $intTimeOffset = false;
			if (false === $intTimeOffset)
				$intTimeOffset = CTimeZone::GetOffset();
			if (isset($arParams['TIME_ZONE']) && 'N' == $arParams['TIME_ZONE'])
				$intTimeOffset = 0;

			foreach ($arProps as &$arOneProp)
			{
				if ('F' == $arOneProp['PROPERTY_TYPE'])
					continue;
				$boolCheck = false;
				if ('N' == $arOneProp['MULTIPLE'])
				{
					if (isset($arOneProp['USER_TYPE']) && !empty($arOneProp['USER_TYPE']))
					{
						switch($arOneProp['USER_TYPE'])
						{
							case 'DateTime':
								$arOneProp['VALUE'] = (string)$arOneProp['VALUE'];
								if ('' != $arOneProp['VALUE'])
								{
									$intStackTimestamp = (int)$arOneProp['VALUE'];
									if ($intStackTimestamp.'!' != $arOneProp['VALUE'].'!')
										$arOneProp['VALUE'] = (int)MakeTimeStamp($arOneProp['VALUE']) - $intTimeOffset;
									else
										$arOneProp['VALUE'] = $intStackTimestamp;
								}
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
								$boolCheck = true;
								break;
						}
					}
					if (!$boolCheck)
					{
						if ('L' == $arOneProp['PROPERTY_TYPE'])
						{
							$arOneProp['VALUE_ENUM_ID'] = (int)$arOneProp['VALUE_ENUM_ID'];
							if (0 < $arOneProp['VALUE_ENUM_ID'])
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE_ENUM_ID'];
							else
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = -1;
						}
						elseif ('E' == $arOneProp['PROPERTY_TYPE'] || 'G' == $arOneProp['PROPERTY_TYPE'])
						{
							$arOneProp['VALUE'] = (int)$arOneProp['VALUE'];
							if (0 < $arOneProp['VALUE'])
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
							else
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = -1;
						}
						else
						{
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
						}
					}
				}
				else
				{
					if (isset($arOneProp['USER_TYPE']) && !empty($arOneProp['USER_TYPE']))
					{
						switch($arOneProp['USER_TYPE'])
						{
							case 'DateTime':
								$arValues = array();
								if (is_array($arOneProp['VALUE']) && !empty($arOneProp['VALUE']))
								{
									foreach ($arOneProp['VALUE'] as &$strOneValue)
									{
										$strOneValue = (string)$strOneValue;
										if ('' != $strOneValue)
										{
											$intStackTimestamp = (int)$strOneValue;
											if ($intStackTimestamp.'!' != $strOneValue.'!')
												$strOneValue = (int)MakeTimeStamp($strOneValue) - $intTimeOffset;
											else
												$strOneValue = $intStackTimestamp;
										}
										$arValues[] = $strOneValue;
									}
									if (isset($strOneValue))
										unset($strOneValue);
								}
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arValues;
								$boolCheck = true;
								break;
						}
					}
					if (!$boolCheck)
					{
						if ('L' == $arOneProp['PROPERTY_TYPE'])
						{
							$arValues = array();
							if (is_array($arOneProp['VALUE_ENUM_ID']) && !empty($arOneProp['VALUE_ENUM_ID']))
							{
								foreach ($arOneProp['VALUE_ENUM_ID'] as &$intOneValue)
								{
									$intOneValue = (int)$intOneValue;
									if (0 < $intOneValue)
										$arValues[] = $intOneValue;
								}
								if (isset($intOneValue))
									unset($intOneValue);
							}
							if (empty($arValues))
								$arValues = array(-1);
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arValues;
						}
						elseif ('E' == $arOneProp['PROPERTY_TYPE'] || 'G' == $arOneProp['PROPERTY_TYPE'])
						{
							$arValues = array();
							if (is_array($arOneProp['VALUE']) && !empty($arOneProp['VALUE']))
							{
								foreach ($arOneProp['VALUE'] as &$intOneValue)
								{
									$intOneValue = (int)$intOneValue;
									if (0 < $intOneValue)
										$arValues[] = $intOneValue;
								}
								if (isset($intOneValue))
									unset($intOneValue);
							}
							if (empty($arValues))
								$arValues = array(-1);
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arValues;
						}
						else
						{
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
						}
					}
				}
				if (!is_array($arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE']))
					$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = array($arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE']);
			}
			if (isset($arOneProp))
				unset($arOneProp);
		}
	}

	protected function __GenerateParent(&$product, $sku)
	{
		if (!isset($product['PROPERTY_'.$sku['SKU_PROPERTY_ID'].'_VALUE']))
			return false;
		$parentID = (int)current($product['PROPERTY_'.$sku['SKU_PROPERTY_ID'].'_VALUE']);
		if ($parentID <= 0)
			return false;
		if (!isset(self::$arCacheProduct[$parentID]))
		{
			$parent = array('ID' => $parentID, 'IBLOCK_ID' => $sku['PRODUCT_IBLOCK_ID']);
			if (!self::__GenerateFields($parent))
				return false;
			self::$arCacheProduct[$parentID] = $parent;
		}
		else
		{
			$parent = self::$arCacheProduct[$parentID];
		}
		foreach ($parent as $key => $value)
		{
			if ($key == 'SECTION_ID')
			{
				$product[$key] = array_merge($product[$key], $value);
			}
			elseif (strncmp($key, 'PROPERTY_', 9) == 0)
			{
				$product[$key] = $value;
			}
			elseif (strncmp($key, 'CATALOG_', 8) != 0)
			{
				$product['PARENT_'.$key] = $value;
			}
		}
		unset($value, $key, $parent);
		return true;
	}

	protected function __ParseArrays(&$arFields)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$arResult = array(
		);

		if (!self::__CheckOneEntity($arFields, 'GROUP_IDS'))
		{
			$arMsg[] = array('id' => 'GROUP_IDS', "text" => GetMessage('BT_MOD_CATALOG_DISC_ERR_PARSE_USER_GROUP'));
			$boolResult = false;
		}
		if (!self::__CheckOneEntity($arFields, 'CATALOG_GROUP_IDS'))
		{
			$arMsg[] = array('id' => 'CATALOG_GROUP_IDS', "text" => GetMessage('BT_MOD_CATALOG_DISC_ERR_PARSE_PRICE_TYPE'));
			$boolResult = false;
		}

		if ($boolResult)
		{
			$arTempo = array(
				'USER_GROUP_ID' => $arFields['GROUP_IDS'],
				'PRICE_TYPE_ID' => $arFields['CATALOG_GROUP_IDS'],
			);

			$arOrder = array(
				'USER_GROUP_ID',
				'PRICE_TYPE_ID',
			);

			self::__ArrayMultiple($arOrder, $arResult, $arTempo);
			unset($arTempo);
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
			return $boolResult;
		}
		else
		{
			return $arResult;
		}
	}

	protected function __CheckOneEntity(&$arFields, $strEntityID)
	{
		$boolResult = false;
		$strEntityID = trim(strval($strEntityID));
		if (!empty($strEntityID))
		{
			if (is_array($arFields) && !empty($arFields))
			{
				if (is_set($arFields, $strEntityID))
				{
					if (!is_array($arFields[$strEntityID]))
						$arFields[$strEntityID] = array($arFields[$strEntityID]);
					$arValid = array();
					foreach ($arFields[$strEntityID] as &$value)
					{
						$value = (int)$value;
						if ($value > 0)
							$arValid[] = $value;
					}
					if (isset($value))
						unset($value);
					if (!empty($arValid))
					{
						$arValid = array_unique($arValid);
					}
					$arFields[$strEntityID] = $arValid;

					if (empty($arFields[$strEntityID]))
					{
						$arFields[$strEntityID] = array(-1);
					}
				}
				else
				{
					$arFields[$strEntityID] = array(-1);
				}
			}
			else
			{
				$arFields[$strEntityID] = array(-1);
			}
			$boolResult = true;
		}
		return $boolResult;
	}

	protected function __ArrayMultiple($arOrder, &$arResult, $arTuple, $arTemp = array())
	{
		if (empty($arTuple))
		{
			$arResult[] = array(
				'EQUAL' => array_combine($arOrder, $arTemp),
			);
		}
		else
		{
			$head = array_shift($arTuple);
			$arTemp[] = false;
			if (is_array($head))
			{
				if (empty($head))
				{
					$arTemp[count($arTemp)-1] = -1;
					self::__ArrayMultiple($arOrder, $arResult, $arTuple, $arTemp);
				}
				else
				{
					foreach ($head as &$value)
					{
						$arTemp[count($arTemp)-1] = $value;
						self::__ArrayMultiple($arOrder, $arResult, $arTuple, $arTemp);
					}
					if (isset($value))
						unset($value);
				}
			}
			else
			{
				$arTemp[count($arTemp)-1] = $head;
				self::__ArrayMultiple($arOrder, $arResult, $arTuple, $arTemp);
			}
		}
	}

	protected function __Unpack($arProduct, $strUnpack)
	{
		if (empty($strUnpack))
			return false;
		return eval('return '.$strUnpack.';');
	}

	protected function __ConvertOldConditions($strAction, &$arFields)
	{
		$strAction = ToUpper($strAction);
		if (!is_set($arFields, 'CONDITIONS'))
		{
			$arConditions = array(
				'CLASS_ID' => 'CondGroup',
				'DATA' => array(
					'All' => 'AND',
					'True' => 'True',
				),
				'CHILDREN' => array(),
			);
			$intEntityCount = 0;

			$arIBlockList = self::__ConvertOldOneEntity($arFields, 'IBLOCK_IDS');
			if (!empty($arIBlockList))
			{
				$intEntityCount++;
			}

			$arSectionList = self::__ConvertOldOneEntity($arFields, 'SECTION_IDS');
			if (!empty($arSectionList))
			{
				$intEntityCount++;
			}

			$arElementList = self::__ConvertOldOneEntity($arFields, 'PRODUCT_IDS');
			if (!empty($arElementList))
			{
				$intEntityCount++;
			}

			if (0 < $intEntityCount)
			{
				self::__AddOldOneEntity($arConditions, 'CondIBIBlock', $arIBlockList, (1 == $intEntityCount));
				self::__AddOldOneEntity($arConditions, 'CondIBSection', $arSectionList, (1 == $intEntityCount));
				self::__AddOldOneEntity($arConditions, 'CondIBElement', $arElementList, (1 == $intEntityCount));
			}

			if ('ADD' == $strAction)
			{
				$arFields['CONDITIONS'] = $arConditions;
			}
			else
			{
				if (0 < $intEntityCount)
				{
					$arFields['CONDITIONS'] = $arConditions;
				}
			}
		}
	}

	protected function __ConvertOldOneEntity(&$arFields, $strEntityID)
	{
		$arResult = false;
		if (!empty($strEntityID))
		{
			$arResult = array();
			if (isset($arFields[$strEntityID]))
			{
				if (!is_array($arFields[$strEntityID]))
					$arFields[$strEntityID] = array($arFields[$strEntityID]);
				foreach ($arFields[$strEntityID] as &$value)
				{
					$value = (int)$value;
					if ($value > 0)
						$arResult[] = $value;
				}
				if (isset($value))
					unset($value);
				if (!empty($arResult))
				{
					$arResult = array_values(array_unique($arResult));
				}
			}
		}
		return $arResult;
	}

	protected function __AddOldOneEntity(&$arConditions, $strCondID, $arEntityValues, $boolOneEntity)
	{
		if (!empty($strCondID))
		{
			$boolOneEntity = (true == $boolOneEntity ? true : false);
			if (!empty($arEntityValues))
			{
				if (1 < count($arEntityValues))
				{
					$arList = array();
					foreach ($arEntityValues as &$intItemID)
					{
						$arList[] = array(
							'CLASS_ID' => $strCondID,
							'DATA' => array(
								'logic' => 'Equal',
								'value' => $intItemID
							),
						);
					}
					if (isset($intItemID))
						unset($intItemID);
					if ($boolOneEntity)
					{
						$arConditions = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
					else
					{
						$arConditions['CHILDREN'][] = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
				}
				else
				{
					$arConditions['CHILDREN'][] = array(
						'CLASS_ID' => $strCondID,
						'DATA' => array(
							'logic' => 'Equal',
							'value' => current($arEntityValues)
						),
					);
				}
			}
		}
	}

	protected function __GetConditionValues(&$arFields)
	{
		$arResult = false;
		if (isset($arFields['CONDITIONS']) && !empty($arFields['CONDITIONS']))
		{
			$arConditions = false;
			if (!is_array($arFields['CONDITIONS']))
			{
				if (CheckSerializedData($arFields['CONDITIONS']))
				{
					$arConditions = unserialize($arFields['CONDITIONS']);
				}
			}
			else
			{
				$arConditions = $arFields['CONDITIONS'];
			}

			if (is_array($arConditions) && !empty($arConditions))
			{
				$obCond = new CCatalogCondTree();
				$boolCond = $obCond->Init(BT_COND_MODE_SEARCH, BT_COND_BUILD_CATALOG, array());
				if ($boolCond)
				{
					$arResult = $obCond->GetConditionValues($arConditions);
				}
			}
		}
		return $arResult;
	}

	protected function __GetOldOneEntity(&$arFields, &$arCondList, $strEntityID, $strCondID)
	{
		if (is_array($arCondList) && !empty($arCondList))
		{
			$arFields[$strEntityID] = array();
			if (isset($arCondList[$strCondID]) && !empty($arCondList[$strCondID]) && is_array($arCondList[$strCondID]))
			{
				if (isset($arCondList[$strCondID]['VALUES']) && !empty($arCondList[$strCondID]['VALUES']) && is_array($arCondList[$strCondID]['VALUES']))
				{
					$arCheck = array();
					foreach ($arCondList[$strCondID]['VALUES'] as &$intValue)
					{
						$intValue = (int)$intValue;
						if (0 < $intValue)
							$arCheck[] = $intValue;
					}
					if (isset($intValue))
						unset($intValue);
					$arCheck = array_values(array_unique($arCheck));
					$arFields[$strEntityID] = $arCheck;
				}
			}
		}
	}

	protected function __UpdateOldOneEntity($intID, &$arFields, $arParams, $boolUpdate)
	{
		global $DB;

		$boolUpdate = (false === $boolUpdate ? false : true);
		$intID = (int)$intID;
		if ($intID <= 0)
			return;
		if (!empty($arParams) && is_array($arParams))
		{
			if (!empty($arParams['ENTITY_ID']) && !empty($arParams['TABLE_ID']) && !empty($arParams['FIELD_ID']))
			{
				if (isset($arFields[$arParams['ENTITY_ID']]))
				{
					if ($boolUpdate)
					{
						$DB->Query("DELETE FROM ".$arParams['TABLE_ID']." WHERE DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
					if (!empty($arFields[$arParams['ENTITY_ID']]))
					{
						foreach ($arFields[$arParams['ENTITY_ID']] as &$intValue)
						{
							$strSql = "INSERT INTO ".$arParams['TABLE_ID']."(DISCOUNT_ID, ".$arParams['FIELD_ID'].") VALUES(".$intID.", ".$intValue.")";
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						}
						if (isset($intValue))
							unset($intValue);
					}
				}
			}
		}
	}

	public static function SetDiscountFilterCache($arDiscountIDs, $arCatalogGroups, $arUserGroups)
	{
		if (!is_array($arDiscountIDs))
			return false;
		CatalogClearArray($arDiscountIDs);

		if (!is_array($arCatalogGroups))
			return false;
		CatalogClearArray($arCatalogGroups);
		if (empty($arCatalogGroups))
			return false;

		if (!is_array($arUserGroups))
			return false;
		CatalogClearArray($arUserGroups);
		if (empty($arUserGroups))
			return false;

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		self::$arCacheDiscountFilter[$strCacheKey] = $arDiscountIDs;

		return true;
	}

	public static function SetAllDiscountFilterCache($arDiscountCache, $boolNeedClear = true)
	{
		if (empty($arDiscountCache) || !is_array($arDiscountCache))
			return false;
		$boolNeedClear = !!$boolNeedClear;
		foreach ($arDiscountCache as $strCacheKey => $arDiscountIDs)
		{
			if (!is_array($arDiscountIDs))
				continue;
			if ($boolNeedClear)
				CatalogClearArray($arDiscountIDs);
			self::$arCacheDiscountFilter[$strCacheKey] = $arDiscountIDs;
		}
		return true;
	}

	public static function GetDiscountFilterCache($arCatalogGroups, $arUserGroups)
	{
		if (!is_array($arCatalogGroups))
			return false;
		CatalogClearArray($arCatalogGroups);
		if (empty($arCatalogGroups))
			return false;

		if (!is_array($arUserGroups))
			return false;
		CatalogClearArray($arUserGroups);
		if (empty($arUserGroups))
			return false;

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		return (isset(self::$arCacheDiscountFilter[$strCacheKey]) ? self::$arCacheDiscountFilter[$strCacheKey] : false);
	}

	public static function IsExistsDiscountFilterCache($arCatalogGroups, $arUserGroups)
	{
		if (!is_array($arCatalogGroups))
			return false;
		CatalogClearArray($arCatalogGroups);
		if (empty($arCatalogGroups))
			return false;

		if (!is_array($arUserGroups))
			return false;
		CatalogClearArray($arUserGroups);
		if (empty($arUserGroups))
			return false;

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		return isset(self::$arCacheDiscountFilter[$strCacheKey]);
	}

	public static function GetDiscountFilterCacheByKey($strCacheKey)
	{
		if (empty($strCacheKey))
			return false;
		$strCacheKey = md5($strCacheKey);
		return (isset(self::$arCacheDiscountFilter[$strCacheKey]) ? self::$arCacheDiscountFilter[$strCacheKey] : false);
	}

	public static function IsExistsDiscountFilterCacheByKey($strCacheKey)
	{
		if (empty($strCacheKey))
			return false;
		$strCacheKey = md5($strCacheKey);
		return isset(self::$arCacheDiscountFilter[$strCacheKey]);
	}

	public static function GetDiscountFilterCacheKey($arCatalogGroups, $arUserGroups, $boolNeedClear = true)
	{
		$boolNeedClear = !!$boolNeedClear;
		if ($boolNeedClear)
		{
			if (!is_array($arCatalogGroups))
				return false;
			CatalogClearArray($arCatalogGroups);
			if (empty($arCatalogGroups))
				return false;

			if (!is_array($arUserGroups))
				return false;
			CatalogClearArray($arUserGroups);
			if (empty($arUserGroups))
				return false;
		}

		return md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
	}

	public static function SetDiscountResultCache($arDiscountList, $arDiscountIDs, $strSiteID, $strRenewal)
	{
		if (!is_array($arDiscountList))
			return false;
		if (!is_array($arDiscountIDs))
			return false;
		CatalogClearArray($arDiscountIDs);
		if (empty($arDiscountIDs))
			return false;
		if ('' == $strSiteID)
			return false;
		$strRenewal = ('Y' == $strRenewal ? 'Y' : 'N');
		$strCacheKey = md5('D'.implode('_', $arDiscountIDs).'-'.'S'.$strSiteID.'-R'.$strRenewal);
		self::$arCacheDiscountResult[$strCacheKey] = $arDiscountIDs;

		return true;
	}

	public static function SetAllDiscountResultCache($arDiscountResultCache)
	{
		if (empty($arDiscountResultCache) || !is_array($arDiscountResultCache))
			return false;
		foreach ($arDiscountResultCache as $strCacheKey => $arDiscountIDs)
		{
			self::$arCacheDiscountResult[$strCacheKey] = $arDiscountIDs;
		}
		return true;

	}

	public static function GetDiscountResultCacheKey($arDiscountIDs, $strSiteID, $strRenewal, $boolNeedClear = true)
	{
		$boolNeedClear = !!$boolNeedClear;
		if ($boolNeedClear)
		{
			if (!is_array($arDiscountIDs))
				return false;
			CatalogClearArray($arDiscountIDs);
			if (empty($arDiscountIDs))
				return false;

			if ('' == $strSiteID)
				return false;
			$strRenewal = ('Y' == $strRenewal ? 'Y' : 'N');
		}
		return md5('D'.implode('_', $arDiscountIDs).'-'.'S'.$strSiteID.'-R'.$strRenewal);
	}

	public static function SetDiscountProductCache($arItem, $arParams = array())
	{
		if (empty($arItem) || !is_array($arItem))
		{
			return;
		}
		if (!empty($arParams) && isset($arParams['GET_BY_ID']) && $arParams['GET_BY_ID'] == 'Y')
		{
			$filter = array('ID' => $arItem);
			if (isset($arParams['IBLOCK_ID']))
			{
				$filter['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
			}
			$select = array('ID', 'IBLOCK_ID', 'CODE', 'XML_ID', 'NAME', 'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO',
				'SORT', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'DATE_CREATE', 'DATE_CREATE_UNIX', 'CREATED_BY', 'TIMESTAMP_X', 'TIMESTAMP_X_UNIX',
				'MODIFIED_BY', 'TAGS', 'CATALOG_QUANTITY'
			);
			CTimeZone::Disable();
			$rsProducts = CIBlockElement::GetList(array(), $filter, false, false, $select);
			CTimeZone::Enable();
			while ($arProductFields = $rsProducts->GetNext(false, true))
			{
				$arProduct = array();

				$arProduct['ID'] = (int)$arProductFields['ID'];
				$arProduct['IBLOCK_ID'] = (int)$arProductFields['IBLOCK_ID'];

				$arProduct['SECTION_ID'] = array();
				$arProductSections = self::__GetSectionList($arProduct['IBLOCK_ID'], $arProduct['ID']);
				if (false !== $arProductSections)
					$arProduct['SECTION_ID'] = $arProductSections;

				$arProduct['CODE'] = (string)$arProductFields['~CODE'];
				$arProduct['XML_ID'] = (string)$arProductFields['~XML_ID'];
				$arProduct['NAME'] = $arProductFields['~NAME'];

				$arProduct['ACTIVE'] = $arProductFields['ACTIVE'];

				$arProduct['DATE_ACTIVE_FROM'] = (string)$arProductFields['DATE_ACTIVE_FROM'];
				if (!empty($arProduct['DATE_ACTIVE_FROM']))
					$arProduct['DATE_ACTIVE_FROM'] = (int)MakeTimeStamp($arProduct['DATE_ACTIVE_FROM']);

				$arProduct['DATE_ACTIVE_TO'] = (string)$arProductFields['DATE_ACTIVE_TO'];
				if (!empty($arProduct['DATE_ACTIVE_TO']))
					$arProduct['DATE_ACTIVE_TO'] = (int)MakeTimeStamp($arProduct['DATE_ACTIVE_TO']);

				$arProduct['SORT'] = (int)$arProductFields['SORT'];

				$arProduct['PREVIEW_TEXT'] = (string)$arProductFields['~PREVIEW_TEXT'];
				$arProduct['DETAIL_TEXT'] = (string)$arProductFields['~DETAIL_TEXT'];
				$arProduct['TAGS'] = (string)$arProductFields['~TAGS'];

				if (isset($arProductFields['DATE_CREATE_UNIX']))
				{
					$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE_UNIX'];
					if ('' != $arProduct['DATE_CREATE'])
						$arProduct['DATE_CREATE'] = (int)$arProduct['DATE_CREATE'];
				}
				else
				{
					$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE'];
					if ('' != $arProduct['DATE_CREATE'])
						$arProduct['DATE_CREATE'] = (int)MakeTimeStamp($arProduct['DATE_CREATE']);
				}

				if (isset($arProductFields['TIMESTAMP_X_UNIX']))
				{
					$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X_UNIX'];
					if ('' != $arProduct['TIMESTAMP_X'])
						$arProduct['TIMESTAMP_X'] = (int)$arProduct['TIMESTAMP_X'];
				}
				else
				{
					$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X'];
					if ('' != $arProduct['TIMESTAMP_X'])
						$arProduct['TIMESTAMP_X'] = (int)MakeTimeStamp($arProduct['TIMESTAMP_X']);
				}

				$arProduct['CREATED_BY'] = (int)$arProductFields['CREATED_BY'];
				$arProduct['MODIFIED_BY'] = (int)$arProductFields['MODIFIED_BY'];

				$arProduct['CATALOG_QUANTITY'] = (string)$arProductFields['CATALOG_QUANTITY'];
				if ('' != $arProduct['CATALOG_QUANTITY'])
					$arProduct['CATALOG_QUANTITY'] = doubleval($arProduct['CATALOG_QUANTITY']);
				$arProduct['CATALOG_WEIGHT'] = (string)$arProductFields['CATALOG_WEIGHT'];
				if ('' != $arProduct['CATALOG_WEIGHT'])
					$arProduct['CATALOG_WEIGHT'] = doubleval($arProduct['CATALOG_WEIGHT']);

				$arProduct['CATALOG_VAT_ID'] = (string)$arProductFields['CATALOG_VAT_ID'];
				if ('' != $arProduct['CATALOG_VAT_ID'])
					$arProduct['CATALOG_VAT_ID'] = (int)$arProduct['CATALOG_VAT_ID'];

				$arProduct['CATALOG_VAT_INCLUDED'] = (string)$arProductFields['CATALOG_VAT_INCLUDED'];

				if (!isset(self::$arCacheProductProperties[$arProduct['ID']]))
				{
					$propsList = array(
						$arProduct['ID'] => array()
					);
					CIBlockElement::GetPropertyValuesArray(
						$propsList,
						$arProduct['IBLOCK_ID'],
						array('ID' => $arProduct['ID'], 'IBLOCK_ID' => $arProduct['IBLOCK_ID'])
					);
					self::$arCacheProductProperties[$arProduct['ID']] = $propsList[$arProduct['ID']];
					unset($propsList);
				}
				$arProps = self::$arCacheProductProperties[$arProduct['ID']];

				self::__ConvertProperties($arProduct, $arProps, array('TIME_ZONE' => 'N'));
				if (isset(self::$arCacheProductProperties[$arProduct['ID']]))
					unset(self::$arCacheProductProperties[$arProduct['ID']]);
				if (isset(self::$arCacheProductSections[$arProduct['ID']]))
					unset(self::$arCacheProductSections[$arProduct['ID']]);

				$sku = CCatalogSKU::GetInfoByOfferIBlock($arProduct['IBLOCK_ID']);
				if (!empty($sku))
				{
					if (!self::__GenerateParent($arProduct, $sku))
						$sku = false;
				}
				self::$arCacheProduct[$arProduct['ID']] = $arProduct;
			}
		}
		else
		{
			if (!isset(self::$arCacheProduct[$arItem['ID']]))
			{
				$arParams = array(
					'PRODUCT' => 'Y'
				);
				if (!self::__GenerateFields($arItem, $arParams))
				{
					return;
				}
				$sku = CCatalogSKU::GetInfoByOfferIBlock($arItem['IBLOCK_ID']);
				if (!empty($sku))
				{
					if (!self::__GenerateParent($arItem, $sku))
						$sku = false;
				}
				self::$arCacheProduct[$arItem['ID']] = $arItem;
			}
		}
	}

	public static function SetProductSectionsCache($arItemIDs)
	{
		if (empty($arItemIDs) || !is_array($arItemIDs))
			return;
		CatalogClearArray($arItemIDs);
		if (empty($arItemIDs))
			return;
		if (empty(self::$arCacheProductSections))
		{
			self::$arCacheProductSections = array_fill_keys($arItemIDs, array());
		}
		else
		{
			foreach ($arItemIDs as &$intOneID)
			{
				self::$arCacheProductSections[$intOneID] = array();
			}
			unset($intOneID);
		}
		$rsSections = CIBlockElement::GetElementGroups($arItemIDs, true, array("ID", "IBLOCK_SECTION_ID", "IBLOCK_ELEMENT_ID"));
		while ($arSection = $rsSections->Fetch())
		{
			$arSection['ID'] = (int)$arSection['ID'];
			$arSection['IBLOCK_SECTION_ID'] = (int)$arSection['IBLOCK_SECTION_ID'];
			$arSection['IBLOCK_ELEMENT_ID'] = (int)$arSection['IBLOCK_ELEMENT_ID'];
			self::$arCacheProductSections[$arSection['IBLOCK_ELEMENT_ID']][] = $arSection;
		}
		unset($arSection, $rsSections);
	}

	public static function SetProductPropertiesCache($intProductID, $arProps)
	{
		$intProductID = (int)$intProductID;
		if ($intProductID <= 0)
			return;
		if (!is_array($arProps))
			return;
		self::$arCacheProductProperties[$intProductID] = $arProps;
	}

	public static function ClearDiscountCache($arTypes)
	{
		if (empty($arTypes) || !is_array($arTypes))
			return;
		if (isset($arTypes['PRODUCT']))
			self::$arCacheProduct = array();
		if (isset($arTypes['SECTIONS']))
			self::$arCacheProductSections = array();
		if (isset($arTypes['SECTION_CHAINS']))
			self::$arCacheProductSectionChain = array();
		if (isset($arTypes['PROPERTIES']))
			self::$arCacheProductProperties = array();
	}

	protected static function clearFields($value)
	{
		return ($value !== null);
	}
}
?>