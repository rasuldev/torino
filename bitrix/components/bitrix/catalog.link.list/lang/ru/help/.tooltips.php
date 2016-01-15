<?
$MESS["IBLOCK_TYPE_TIP"] = "Из выпадающего списка выбирается один из созданных в системе типов инфоблоков. После нажатия кнопки <b><i>ок</i></b> будут подгружены инфоблоки, созданные для выбранного типа.";
$MESS["IBLOCK_ID_TIP"] = "Выбирается один из инфоблоков установленного типа. Если пункт (другое)->, то необходимо указать ID инфоблока в поле рядом.";
$MESS["LINK_PROPERTY_SID_TIP"] = "Свойство, по которому элементы связаны, выбирается из списка или указывается в виде кода в поле рядом.";
$MESS["ELEMENT_ID_TIP"] = "Поле содержит код, в котором передается ID элемента, для которого выводится список связанных элементов.";
$MESS["ELEMENT_SORT_FIELD_TIP"] = "Указывается поле, по которому будет происходить сортировка элементов.";
$MESS["ELEMENT_SORT_ORDER_TIP"] = "В каком направлении будут отсортированы элементы, по возрастанию или по убыванию.";
$MESS["FILTER_NAME_TIP"] = "Задается имя переменной, в которую передается массив параметров из фильтра. Служит для определения выходящих из фильтра элементов. Поле может быть оставлено пустым, тогда используется значение по умолчанию.";
$MESS["SECTION_URL_TIP"] = "Указывается путь к странице с описанием раздела инфоблока.";
$MESS["DETAIL_URL_TIP"] = "Указывается путь к странице с детальным описанием элемента инфоблока.";
$MESS["BASKET_URL_TIP"] = "Указывается путь к странице с корзиной покупателя.";
$MESS["ACTION_VARIABLE_TIP"] = "В данном поле указывается имя переменной, в которой передается действие: ADD_TO_COMPARE_LIST, ADD2BASKET и т.д. Значение поля по умолчанию <i>action</i>.";
$MESS["PRODUCT_ID_VARIABLE_TIP"] = "Имя переменной, в которой будет передаваться ID товара.";
$MESS["SECTION_ID_VARIABLE_TIP"] = "Имя переменной, в которой будет передаваться ID раздела инфоблока.";
$MESS["DISPLAY_PANEL_TIP"] = "При установленной опции кнопки будут отображены в режиме редактирования сайта на административной панели и в наборе кнопок области редактирования данного компонента.";
$MESS["SET_TITLE_TIP"] = "При установленной опции в качестве заголовка страницы будет установлено название раздела связанного инфоблока.";
$MESS["PAGE_ELEMENT_COUNT_TIP"] = "Данное число определяет количество выводимых элементов на странице.";
$MESS["PROPERTY_CODE_TIP"] = "Среди свойств инфоблока можно выбрать те, которые будут отображены при показе элементов в таблице. При выборе пункта (не выбрано)->  и без указания кодов свойств в строках ниже, свойства выведены не будут.";
$MESS["PRICE_CODE_TIP"] = "Устанавливается, какой из типов цен будут выведен в каталоге. Если ни один из типов не выбран, то цена и кнопки <i>Купить</i> и <i>В корзину</i> не будет показаны.";
$MESS["USE_PRICE_COUNT_TIP"] = "При установленной опции для каждого товара выбираются все диапазоны цен, существующие у товара";
$MESS["SHOW_PRICE_COUNT_TIP"] = "Если снята опция \"Использовать вывод цен с диапазонами\", т.е. для каждого товара выводится только одна цена, но в каталоге есть товары с расширенным управлением ценами (зависимость цены от количества), необходимо указать - для какого количества товара искать цену. На товары, чья цена не зависит от количества, эта опция не влияет.";
$MESS["CACHE_TYPE_TIP"] = "<i>Авто</i>: действует при включенном кешировании в течение заданного времени;<br /><i>Кешировать</i>: для кеширования необходимо определить только время кеширования;<br /><i>Не кешировать</i>: кеширования нет в любом случае.";
$MESS["CACHE_TIME_TIP"] = "Поле служит для указания времени кеширования в секундах.";
$MESS["CACHE_FILTER_TIP"] = "При установленной опции каждый результат, полученный из фильтра, будет кешироваться.";
$MESS["DISPLAY_TOP_PAGER_TIP"] = "При отмеченной опции навигация по страницам будет выведена наверху страницы.";
$MESS["DISPLAY_BOTTOM_PAGER_TIP"] = "При отмеченной опции навигация по страницам будет выведена внизу страницы.";
$MESS["PAGER_TITLE_TIP"] = "В данном поле указывается название категорий, по которым происходит перемещение по элементам.";
$MESS["PAGER_SHOW_ALWAYS_TIP"] = "Если данный флаг отмечен, то постраничная навигация будет выводиться, даже если все элементы помещаются на одной странице.";
$MESS["PAGER_TEMPLATE_TIP"] = "В данном поле указывается имя шаблона постраничной навигации. Если поле пусто, то выбирается шаблон по умолчанию (.default). Также в системе задан шаблон <i>orange</i>.";
$MESS["PAGER_DESC_NUMBERING_TIP"] = "Механизм используют, если при добавлении элемента инфоблока, он всегда попадает наверх списка. Таким образом, меняется лишь последняя страница. Все предыдущие можно надолго закешировать.";
$MESS["PAGER_DESC_NUMBERING_CACHE_TIME_TIP"] = "Время кеширования первых страниц в секундах при использовании обратной навигации.";
$MESS["AJAX_MODE_TIP"] = "Включение для компонента режима AJAX.";
$MESS["AJAX_OPTION_SHADOW_TIP"] = "При выполнении перехода будет затенена область, которая должна измениться.";
$MESS["AJAX_OPTION_JUMP_TIP"] = "Если пользователь совершит AJAX-переход, то по окончании загрузки произойдет прокрутка к началу компонента.";
$MESS["AJAX_OPTION_STYLE_TIP"] = "При совершении AJAX-переходов будет происходить подгрузка и обработка списка стилей, вызванных компонентом.";
$MESS["AJAX_OPTION_HISTORY_TIP"] = "Когда пользователь выполняет AJAX-переходы, то при включенной опции можно использовать кнопки браузера \"Назад\" и \"Вперед\".";
$MESS["PRICE_VAT_INCLUDE_TIP"] = "При отмеченной опции цены будут показаны с учетом НДС.";
?>