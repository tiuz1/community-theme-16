<?php
header('Access-Control-Allow-Origin: *');
require_once(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');
include(dirname(__FILE__).'/../classes/pk_flexmenu.class.php');

$flexClass = new FlexMenuItem();

if (Tools::getValue('changeState') == 1) {
	$flexClass->updateSectionState(Tools::getValue('name'), Tools::getValue('state'), Tools::getValue('menuID'));
}
if (Tools::getValue('changeType') == 1) {
	$flexClass->updateSectionType(Tools::getValue('name'), Tools::getValue('type'), Tools::getValue('menuID'));
}
if (Tools::getValue('changeData') == 1) {
	$flexClass->updateSectionData(Tools::getValue('name'), Tools::getValue('data'), Tools::getValue('menuID'));
}
if (Tools::getValue('removeData') == 1) {
	$flexClass->removeData(Tools::getValue('field'), Tools::getValue('menuID'));
}
if (Tools::getValue('removeItem') == 1) {
	$flexClass->removeItem(Tools::getValue('field'), Tools::getValue('menuID'), Tools::getValue('item'));
}
if (Tools::getValue('updateMenuItem') == 1) {	
	$flexClass->ajaxAddName(Tools::getValue('menuID'), Tools::getValue('val'), Tools::getValue('shopid'), Tools::getValue('langid'));
}
if (Tools::getValue('updateMenuState') == 1) {	
	$flexClass->ajaxUpdateState(Tools::getValue('menuID'), Tools::getValue('val'), Tools::getValue('shopid'));
}
if (Tools::getValue('updateMenuType') == 1) {	
	$flexClass->ajaxUpdateMenuType(Tools::getValue('menuID'), Tools::getValue('val'), Tools::getValue('shopid'));
}
if (Tools::getValue('removeImage') == 1) {
	$flexClass->removeImage(Tools::getValue('type'), Tools::getValue('menuID'));
}
if (Tools::getValue('updateMainImage') == 1) {	
	$flexClass->updateMenusTable(Tools::getValue('field'), Tools::getValue('data'), Tools::getValue('menuID'));
}
?>