<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class FlexMenuItem
{
	public static function getCustomLink($id_linksmenutop, $id_lang, $id_shop) {		
		$sql = 'SELECT l.id_pk_flexmenu_links, l.id_lang, l.id_shop, l.label, l.link, ll.new_window FROM '._DB_PREFIX_.'pk_flexmenu_links_lang l
				LEFT JOIN '._DB_PREFIX_.'pk_flexmenu_links ll ON (l.id_pk_flexmenu_links = ll.id_pk_flexmenu_links AND ll.id_shop='.(int)$id_shop.') WHERE l.id_pk_flexmenu_links='.$id_linksmenutop.' AND l.id_lang = '.(int)$id_lang.' AND l.id_shop='.$id_shop;
		return Db::getInstance()->executeS($sql);
	}
	
	public static function gets($id_lang, $id_menu = null, $id_shop)
	{
		$sql = 'SELECT l.id_pk_flexmenu_menus, l.position, l.active, ll.label
				FROM '._DB_PREFIX_.'pk_flexmenu_menus l
				LEFT JOIN '._DB_PREFIX_.'pk_flexmenu_menus_lang ll ON (l.id_pk_flexmenu_menus = ll.id_pk_flexmenu_menus AND ll.id_shop='.(int)$id_shop.')
				WHERE 1 '.((!is_null($id_menu)) ? ' AND l.id_pk_flexmenu_menus = "'.(int)$id_menu.'"' : '').'
				AND l.id_shop IN (0, '.(int)$id_shop.') ORDER BY l.position ASC';

		return Db::getInstance()->executeS($sql);
	}
	
	
	public static function getsfm($id_lang, $id_shop)
	{
		$sql = 'SELECT l.id_pk_flexmenu_menus, l.position, l.active, l.narrow, l.main_image, ll.label, li.left, li.main, li.right, li.bottom, li.bottom_title, li.left_products, li.left_image, li.left_video, li.left_image_link, li.left_products_title, li.right_products, li.right_image, li.right_video, li.right_image_link, li.right_products_title, li.main_products, li.main_links, li.main_cmsp, li.main_video, li.bottom_image, li.bottom_video, li.bottom_image_link, li.bottom_links, li.state_left, li.state_main, li.state_right, li.state_bottom
				FROM '._DB_PREFIX_.'pk_flexmenu_menus l
				LEFT JOIN '._DB_PREFIX_.'pk_flexmenu_menus_lang ll ON (l.id_pk_flexmenu_menus = ll.id_pk_flexmenu_menus AND ll.id_shop='.(int)$id_shop.')
				LEFT JOIN '._DB_PREFIX_.'pk_flexmenu_submenus li ON (l.id_pk_flexmenu_menus = li.id_menu)
				WHERE l.id_shop IN (0, '.(int)$id_shop.') AND l.active = 1   ORDER BY l.position ASC';
				
		return Db::getInstance()->executeS($sql);
	}
	
	

	public static function get($id_menu, $id_lang, $id_shop)
	{
		return self::gets($id_lang, $id_menu, $id_shop);
	}
	
	public static function get_last_position($id_shop)
	{
		$sql = 'SELECT MAX(`position`) as max_position
				FROM '._DB_PREFIX_.'pk_flexmenu_menus WHERE id_shop IN (0, '.(int)$id_shop.')';
		return Db::getInstance()->getValue($sql);
	}
	
	
	public static function getinside($id_menu)
	{
		$sql = 'SELECT *
				FROM '._DB_PREFIX_.'pk_flexmenu_submenus l
				WHERE  id_menu = "'.(int)$id_menu.'"';
		$ret =  Db::getInstance()->getRow($sql);
		return $ret;
	}
	
	public static function moveUp($id_menu, $id_shop)
	{
		$sql = 'SELECT position
				FROM '._DB_PREFIX_.'pk_flexmenu_menus WHERE 1 '.((!is_null($id_menu)) ? ' AND id_pk_flexmenu_menus = "'.(int)$id_menu.'"' : '').'
				AND id_shop IN (0, '.(int)$id_shop.')';
		
		$curr_position = Db::getInstance()->getValue($sql);
		
		$sql = 'SELECT MAX(`position`)
				FROM '._DB_PREFIX_.'pk_flexmenu_menus  WHERE position < '.(int)$curr_position.' AND id_shop IN (0, '.(int)$id_shop.')';
		$prev_position = Db::getInstance()->getValue($sql);
		
		$sql = 'SELECT id_pk_flexmenu_menus
				FROM '._DB_PREFIX_.'pk_flexmenu_menus  WHERE position = '.(int)$prev_position.' AND id_shop IN (0, '.(int)$id_shop.')';
		$prev_menu_id = Db::getInstance()->getValue($sql);
		
		if(isset($prev_position) && isset($prev_menu_id)){
		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				'position'=>(int)$prev_position,
				'id_shop' => (int)$id_shop
			),
			'id_pk_flexmenu_menus = '.(int)$id_menu
		);
		
		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				'position'=>(int)$curr_position,
				'id_shop' => (int)$id_shop
			),
			'id_pk_flexmenu_menus = '.(int)$prev_menu_id
		);
		
		}
		
	}
	
	public static function moveDown($id_menu, $id_shop)
	{
		$sql = 'SELECT position
				FROM '._DB_PREFIX_.'pk_flexmenu_menus WHERE 1 '.((!is_null($id_menu)) ? ' AND id_pk_flexmenu_menus = "'.(int)$id_menu.'"' : '').'
				AND id_shop IN (0, '.(int)$id_shop.')';
		
		$curr_position = Db::getInstance()->getValue($sql);
		
		$sql = 'SELECT MIN(`position`)
				FROM '._DB_PREFIX_.'pk_flexmenu_menus  WHERE position > '.(int)$curr_position.' AND id_shop IN (0, '.(int)$id_shop.')';
		$next_position = Db::getInstance()->getValue($sql);
		
		$sql = 'SELECT id_pk_flexmenu_menus
				FROM '._DB_PREFIX_.'pk_flexmenu_menus  WHERE position = '.(int)$next_position.' AND id_shop IN (0, '.(int)$id_shop.')';
		$next_menu_id = Db::getInstance()->getValue($sql);
		
		
		if(isset($next_position) && isset($next_menu_id)){
		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				'position'=>(int)$next_position,
				'id_shop' => (int)$id_shop
			),
			'id_pk_flexmenu_menus = '.(int)$id_menu
		);
		
		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				'position'=>(int)$curr_position,
				'id_shop' => (int)$id_shop
			),
			'id_pk_flexmenu_menus = '.(int)$next_menu_id
		);
		
		}
		
	}
 	
 	public static function getLinkLang($id_menu, $id_shop)
	{
		$ret = Db::getInstance()->executeS('
			SELECT l.id_pk_flexmenu_menus, l.position, l.active, l.narrow, l.main_image, ll.label
			FROM '._DB_PREFIX_.'pk_flexmenu_menus l
			LEFT JOIN '._DB_PREFIX_.'pk_flexmenu_menus_lang ll ON (l.id_pk_flexmenu_menus = ll.id_pk_flexmenu_menus AND ll.id_shop='.(int)$id_shop.')
			WHERE 1
			'.((!is_null($id_menu)) ? ' AND l.id_pk_flexmenu_menus = "'.(int)$id_menu.'"' : '').'
			AND l.id_shop IN (0, '.(int)$id_shop.')
		');

		$link = array();
		$label = array();
		$new_window = false;
		$position = 0;
		$active = 0;
		$narrow = 0;
		$main_image = "";
		
		foreach ($ret as $line)
		{
			$label = Tools::safeOutput($line['label']);
			$position = $line['position'];
			$active = $line['active'];
			$narrow = $line['narrow'];
			$main_image = $line['main_image'];
		}
		return array('label' => $label, 'position' => $position, 'active' => $active, 'narrow' => $narrow, 'main_image' => $main_image);
	}

	public static function getCustomLinkLang($id_linksmenutop, $id_shop) {
		$ret = Db::getInstance() -> executeS('
			SELECT l.id_pk_flexmenu_links, l.new_window, ll.link, ll.label, ll.id_lang
			FROM ' . _DB_PREFIX_ . 'pk_flexmenu_links l
			LEFT JOIN ' . _DB_PREFIX_ . 'pk_flexmenu_links_lang ll ON (l.id_pk_flexmenu_links = ll.id_pk_flexmenu_links AND ll.id_shop=' . (int)$id_shop . ')
			WHERE 1
			' . ((!is_null($id_linksmenutop)) ? ' AND l.id_pk_flexmenu_links = "' . (int)$id_linksmenutop . '"' : '') . '
			AND l.id_shop IN (0, ' . (int)$id_shop . ')
		');

		$link = array();
		$label = array();
		$new_window = false;

		foreach ($ret as $line) {
			$link[$line['id_lang']] = Tools::safeOutput($line['link']);
			$label[$line['id_lang']] = Tools::safeOutput($line['label']);
			$new_window = (bool)$line['new_window'];
		}

		return array('link' => $link, 'label' => $label, 'new_window' => $new_window);
	}


	public static function add($link, $label, $active, $id_shop, $submenu, $lang)
	{
		if(!is_array($label))
			return false;
		if(!is_array($link))
			return false;

		$position= self::get_last_position($id_shop);
		Db::getInstance()->insert(
			'pk_flexmenu_menus',
			array(
				'active'=>(int)$active,
				'position'=>(int)$position + 1,
				'id_shop' => (int)$id_shop
			)
		);
		
		$id_menu = Db::getInstance()->Insert_ID();
			Db::getInstance()->insert(
			'pk_flexmenu_submenus',
			array(
				'id_menu'=>(int)$id_menu,
				'left'=> $submenu['left_panel'],
				'main'=> $submenu['main_panel'],
				'right'=> $submenu['right_panel'],
				'bottom' => $submenu['bottom_panel'],
				'left_image'=> $submenu['left_panel_image'],
				'left_products'=> $submenu['left_panel_products'],
				'right_image'=> $submenu['right_panel_image'],
				'right_products'=> $submenu['right_panel_products'],
				'main_links'=> $submenu['main_panel_links'],
				'main_cmsp'=> $submenu['main_panel_cmsp'],
				'main_products'=> $submenu['main_panel_products'],
				'bottom_links'=> $submenu['bottom_panel_links'],
				'bottom_products' => $submenu['bottom_panel_products']
			)
		);

		foreach ($label as $id_lang=>$label)
		Db::getInstance()->insert(
			'pk_flexmenu_menus_lang',
			array(
				'id_pk_flexmenu_menus'=>(int)$id_menu,
				'id_lang'=>(int)$id_lang,
				'id_shop'=>(int)$id_shop,
				'label'=>pSQL($label),
			)
		);
	}

	public static function ajaxAddName($id_menu, $label, $id_shop, $id_lang)
	{

		if ($id_menu == "NONE") {
			
			$position = self::get_last_position($id_shop);

			Db::getInstance()->insert(
				'pk_flexmenu_menus',
				array(
					'active'=>1,
					'position'=>(int)$position + 1,
					'id_shop' =>(int)$id_shop,
					'narrow' => 0,
					'main_image' => ""
				)
			);

			$id_menu = Db::getInstance()->Insert_ID();

			Db::getInstance()->insert(
				'pk_flexmenu_menus_lang',
				array(
					'id_pk_flexmenu_menus'=>(int)$id_menu,
					'id_lang'=>(int)$id_lang,
					'id_shop'=>(int)$id_shop,
					'label'=>pSQL($label)
				)
			);

			Db::getInstance()->insert(
			'pk_flexmenu_submenus',
			array(
				'id_menu'=>(int)$id_menu,
				'left'=> "IMAGE",
				'main'=> "LINKS",
				'right'=> "IMAGE",
				'bottom' => "LINKS"
				)
			);

		} else {			
			
			$sql = 'SELECT COUNT(*)	FROM `'._DB_PREFIX_.'pk_flexmenu_menus_lang` WHERE `id_pk_flexmenu_menus` = '.(int)$id_menu.' AND id_shop = '.(int)$id_shop;
			$res = Db::getInstance()->getValue($sql);

			if ($res) {
				Db::getInstance()->update(
					'pk_flexmenu_menus_lang',
					array(
						'label'=>pSQL($label)
					),
					'id_pk_flexmenu_menus = '.(int)$id_menu.' AND id_shop = '.(int)$id_shop
				);		
			} else {
				Db::getInstance()->insert(
					'pk_flexmenu_menus_lang',
					array(
						'id_pk_flexmenu_menus'=>(int)$id_menu,
						'id_lang'=>(int)$id_lang,
						'id_shop'=>(int)$id_shop,
						'label'=>pSQL($label)
					)
				);	
			}
		}
		echo $id_menu;		
	}
	/*public static function ajaxAddLink($id_menu, $link, $id_lang, $id_shop)
	{

		//$id_shop = (int)Context::getContext()->shop->id;			
			
		$sql = 'SELECT COUNT(*)	FROM `'._DB_PREFIX_.'pk_flexmenu_menus_lang` WHERE `id_menu` = '.(int)$id_menu.' AND id_lang = '.(int)$id_lang.' AND id_shop = '.(int)$id_shop;
		$res = Db::getInstance()->getValue($sql);

		if ($res) {
			Db::getInstance()->update(
				'pk_flexmenu_menus_lang',
				array(
					'link'=>pSQL($link)
				),
				'id_menu = '.(int)$id_menu.' AND id_lang = '.(int)$id_lang.' AND id_shop = '.(int)$id_shop
			);		
		} else {
			Db::getInstance()->insert(
				'pk_flexmenu_menus_lang',
				array(
					'id_menu'=>(int)$id_menu,
					'id_lang'=>(int)$id_lang,
					'id_shop'=>(int)$id_shop,
					'link'=>pSQL($link)
				)
			);	
		}	
	}*/
	public static function ajaxUpdateState($id_menu, $val, $id_shop)
	{

		//$id_shop = (int)Context::getContext()->shop->id;					

		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				'active'=>$val
			),
			'id_pk_flexmenu_menus = '.(int)$id_menu.' AND id_shop = '.(int)$id_shop
		);				

	}

	public static function ajaxUpdateMenuType($id_menu, $val, $id_shop)
	{
		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				'narrow'=>$val
			),
			'id_pk_flexmenu_menus = '.(int)$id_menu.' AND id_shop = '.(int)$id_shop
		);				

	}


	public static function update($link, $labels, $active, $id_shop, $submenu, $id_menu)
	{
		
		if(!is_array($labels))
			return false;
		if(!is_array($link))
			return false;
		if(!is_array($submenu))
			return false;

		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				'active'=>(int)$active,
				'id_shop' => (int)$id_shop
			),
			'id_pk_flexmenu_menus = '.(int)$id_menu
		);
		
		Db::getInstance()->update(
			'pk_flexmenu_submenus',
			array(
				'id_menu'=>(int)$id_menu,
				'left'=> $submenu['left_panel'],
				'main'=> $submenu['main_panel'],
				'right'=> $submenu['right_panel'],
				'bottom' => $submenu['bottom_panel'],
				'left_image'=> $submenu['left_panel_image'],
				'left_products'=> $submenu['left_panel_products'],
				'right_image'=> $submenu['right_panel_image'],
				'right_products'=> $submenu['right_panel_products'],
				'main_links'=> $submenu['main_panel_links'],
				'main_cmsp'=> $submenu['main_panel_cmsp'],
				'main_products'=> $submenu['main_panel_products'],
				'bottom_links'=> $submenu['bottom_panel_links'],
				'bottom_products' => $submenu['bottom_panel_products']
			),
			'id_menu = '.(int)$id_menu
		);
		

		foreach ($labels as $id_lang => $label)
			Db::getInstance()->update(
				'pk_flexmenu_menus_lang',
				array(
					'id_shop'=>(int)$id_shop,
					'label'=>pSQL($label),
					'link'=>pSQL($link[$id_lang])
				),
				'id_pk_flexmenu_menus = '.(int)$id_menu
			);
	}


	public static function remove($id_menu, $id_shop)
	{
		Db::getInstance()->delete('pk_flexmenu_menus', 'id_pk_flexmenu_menus = '.(int)$id_menu);
		Db::getInstance()->delete('pk_flexmenu_menus_lang', 'id_pk_flexmenu_menus = '.(int)$id_menu);
		Db::getInstance()->delete('pk_flexmenu_submenus', 'id_menu = '.(int)$id_menu);
	}

	public static function getLinks($id_lang, $id_linksmenutop = null, $id_shop) {

		$sql = 'SELECT l.id_pk_flexmenu_links, l.new_window, ll.link, ll.label
				FROM ' . _DB_PREFIX_ . 'pk_flexmenu_links l
				LEFT JOIN ' . _DB_PREFIX_ . 'pk_flexmenu_links_lang ll ON (l.id_pk_flexmenu_links = ll.id_pk_flexmenu_links AND ll.id_lang = ' . (int)$id_lang . ' AND ll.id_shop=' . (int)$id_shop . ')
				WHERE 1 ' . ((!is_null($id_linksmenutop)) ? ' AND l.id_pk_flexmenu_links = "' . (int)$id_linksmenutop . '"' : '') . '
				AND l.id_shop IN (0, ' . (int)$id_shop . ')';
		return Db::getInstance()->executeS($sql);
	}

	public static function getBlogLinks($id_lang) {
		if (Module::isInstalled('ph_simpleblog')) {
			$sql = 'SELECT id_simpleblog_category, name, link_rewrite FROM '._DB_PREFIX_.'simpleblog_category_lang WHERE id_lang = '.$id_lang;
			$res = Db::getInstance()->executeS($sql);
		} else {
			$res = false;
		}
		return $res;
	}

	public static function getBlogLink($id_lang, $id) {
		if (Module::isInstalled('ph_simpleblog')) {
			$sql = 'SELECT name, link_rewrite FROM '._DB_PREFIX_.'simpleblog_category_lang WHERE id_simpleblog_category = '.$id.' AND id_lang = '.$id_lang;
			$res = Db::getInstance()->executeS($sql);
		} else {
			$res = false;
		}
		return $res;
	}

	public static function getLink($id_linksmenutop, $id_lang, $id_shop) {
		return self::gets($id_lang, $id_linksmenutop, $id_shop);
	}

	public static function addLink($link, $label, $newWindow = 0, $id_shop) {
		if (!is_array($label))
			return false;
		if (!is_array($link))
			return false;

		Db::getInstance()->insert('pk_flexmenu_links', array('new_window' => (int)$newWindow, 'id_shop' => (int)$id_shop));
		$id_linksmenutop = Db::getInstance()->Insert_ID();

		foreach ($label as $id_lang => $label)
			Db::getInstance()->insert('pk_flexmenu_links_lang', array('id_pk_flexmenu_links' => (int)$id_linksmenutop, 'id_lang' => (int)$id_lang, 'id_shop' => (int)$id_shop, 'label' => pSQL($label), 'link' => pSQL($link[$id_lang])));
	}

	public static function updateLink($link, $labels, $newWindow = 0, $id_shop, $id_link) {
		if (!is_array($labels))
			return false;
		if (!is_array($link))
			return false;

		Db::getInstance()->update('pk_flexmenu_links', array('new_window' => (int)$newWindow, 'id_shop' => (int)$id_shop), 'id_pk_flexmenu_links = '.(int)$id_link);

		Db::getInstance()->delete('pk_flexmenu_links_lang', 'id_pk_flexmenu_links = ' . (int)$id_link);
		foreach ($labels as $id_lang => $label) {
			Db::getInstance()->insert('pk_flexmenu_links_lang', array('id_pk_flexmenu_links' => (int)$id_link, 'id_lang' => (int)$id_lang, 'id_shop' => (int)$id_shop, 'label' => pSQL($label), 'link' => pSQL($link[$id_lang])));
		}
	}

	public static function removeLink($id_linksmenutop, $id_shop) {
		Db::getInstance()->delete('pk_flexmenu_links', 'id_pk_flexmenu_links = ' . (int)$id_linksmenutop . ' AND id_shop = ' . (int)$id_shop);
		Db::getInstance()->delete('pk_flexmenu_links_lang', 'id_pk_flexmenu_links = ' . (int)$id_linksmenutop);
	}

	public static function updateMenusTable($field, $data, $menuID) {
		Db::getInstance()->update(
			'pk_flexmenu_menus',
			array(
				$field=>(int)$data
			),
			'id_menu = '.(int)$menuID
		);

	}
	public static function updateSectionState($section, $state, $menuID) {
		Db::getInstance()->update(
			'pk_flexmenu_submenus',
			array(
				$section=>(int)$state
			),
			'id_menu = '.(int)$menuID
		);

	}
	public static function updateSectionType($section, $type, $menuID) {
		Db::getInstance()->update(
			'pk_flexmenu_submenus',
			array(
				$section=>$type
			),
			'id_menu = '.(int)$menuID	
		);

	}
	public static function updateSectionData($section, $data, $menuID) {
		
		if ($section == "main_image") {
			$table = "pk_flexmenu_menus";
			$column = "id_pk_flexmenu_menus";
		} else {
			$table = "pk_flexmenu_submenus";
			$column = "id_menu";
		}
		$wht = array("http:", "https:");
		if (($section == "main_video") || ($section == "left_video") || ($section == "right_video") || ($section == "bottom_video"))
			$data = str_replace($wht, "", $data);

		Db::getInstance()->update(
			$table,
			array(
				$section=>$data
			),
			$column.' = '.(int)$menuID	
		);
	}
	public static function removeData($field, $menuID) {
		Db::getInstance()->update(
			'pk_flexmenu_submenus',
			array(
				$field=>""			
			),
			'id_menu = '.(int)$menuID	
		);
	}
	public static function removeImage($field, $menuID) {

		if ($field = "main") {
			$table = "pk_flexmenu_menus";
			$column = "id_pk_flexmenu_menus";
			$fields = array(
				$field."_image"=>"",
			);
		} else {
			$table = "pk_flexmenu_submenus";
			$column = "id_menu";
			$fields = array(
				$field."_image"=>"",
				$field."_image_link"=>"",
			);
		}
		$sql = 'SELECT '.$field.'_image FROM '._DB_PREFIX_.$table.' WHERE '.$column.'='.(int)$menuID;
		$imgname = Db::getInstance()->executeS($sql);
		
		$res = Db::getInstance()->update(
			$table,
			$fields,
			$column.' = '.(int)$menuID	
		);
		if ($res == true) unlink(_PS_MODULE_DIR_."pk_flexmenu/uploads/".$imgname[0][$field."_image"]);
	}
	
	public static function removeItem($field, $menuID, $item) {

		$sql = 'SELECT '.$field.' FROM '._DB_PREFIX_.'pk_flexmenu_submenus WHERE id_menu='.(int)$menuID;

		$existingList = Db::getInstance()->executeS($sql);
		$items = explode(",", $existingList[0][$field]);
		foreach ($items as $k => $itm) {
			if ($itm == $item) unset($items[$k]);
		}
		$readyList = implode(",", $items);
		return Db::getInstance()->update(
			'pk_flexmenu_submenus',
			array(
				$field=>$readyList
			),
			'id_menu = '.(int)$menuID	
		);
	}

}

?>
