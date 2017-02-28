<?php

if (!defined('_PS_VERSION_'))
	exit ;

require (dirname(__FILE__).'/classes/pk_flexmenu.class.php');

class pk_flexmenu extends Module {

	private $_html = '';
	private $_menu = '';
	private $user_groups;
	private $pattern = '/^([A-Z_]*)[0-9]+/';
	private $page_name = '';
	private $spacer_size = '5';
	private $_postErrors = array();
	public $imageSuffix = "default";

	public function __construct() {
		$this->name = 'pk_flexmenu';
		$this->tab = 'front_office_features';
		$this->version = '1.4.8';
		$this->author = 'promokit.eu';

		parent::__construct();
		$this->displayName = $this->l('FlexMenu');
		$this->description = $this->l('Alysum Flexible Menu');
	}

	public function install() {		
		if (!parent::install() || 
			!$this->registerHook('menu') || 
			!$this->registerHook('displayHeader') || 	
			!$this->installDB() ||
			!$this->installQuickAccess())
			return false;
		return true;
	}

	public function installDb() {		
		if (!file_exists(dirname(__FILE__).'/install.sql'))	return false;
		else if (!$sql = file_get_contents(dirname(__FILE__).'/install.sql')) return false;
		else {
			$sql = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql);
			$sql = preg_split("/;\s*[\r\n]+/", $sql);
			foreach ($sql AS $query)				
				if($query)
					if(!Db::getInstance()->execute(trim($query)))
						return false;
			return true;
		}
	}

	private function uninstallDb() {
		Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'quick_access` WHERE link = "index.php?controller=AdminModules&configure='.$this->name.'&tab_module=front_office_features&module_name='.$this->name.'"');
        Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'quick_access_lang` WHERE name = "Alysum Menu"');
		Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'pk_flexmenu_menus`');
		Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'pk_flexmenu_submenus`');
		Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'pk_flexmenu_menus_lang`');
		Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'pk_flexmenu_links`');
		Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'pk_flexmenu_links_lang`');
		return true;
	}

	public function uninstall() {
		if (!parent::uninstall() 
		|| !$this->uninstallDB())
			return false;
		return true;
	}

	public function getContent() {

		$this->context->controller->addCSS(($this->_path).'css/admin.css');
		$this->context->controller->addjQueryPlugin(array('autocomplete'));
		
		$id_lang = (int)Context::getContext()->language->id;
		$languages = $this->context->controller->getLanguages();
		$default_language = (int)Configuration::get('PS_LANG_DEFAULT');

		$labels = Tools::getValue('label') ? array_filter(Tools::getValue('label'), 'strlen') : array();
		$links_label = Tools::getValue('link') ? array_filter(Tools::getValue('link'), 'strlen') : array();
		$spacer = str_repeat('&nbsp;', $this->spacer_size);
		$divLangName = 'link_label';

		parent::_clearCache($this->name.'.tpl');
		
		if (Tools::isSubmit('submitMenuTabRemove')) {
			$id_menu = Tools::getValue('id_pk_flexmenu_menus', 0);
			FlexMenuItem::remove($id_menu, (int)Shop::getContextShopID());
			$this->_html .= $this->displayConfirmation($this->l('The Menu Item has been removed'));

		} else if (Tools::isSubmit('submitMenuItemEdit')) {
			$id_menu = Tools::getValue('id_pk_flexmenu_menus', 0);
			return $this->displayAddTabForm($id_menu);
		} else if (Tools::isSubmit('moveUp')) {
			$id_menu = Tools::getValue('id_pk_flexmenu_menus', 0);
			FlexMenuItem::moveUp($id_menu, (int)Shop::getContextShopID());
			$this->_html .= $this->displayConfirmation($this->l('Menu order changed'));
		} else if (Tools::isSubmit('moveDown')) {
			$id_menu = Tools::getValue('id_pk_flexmenu_menus', 0);
			FlexMenuItem::moveDown($id_menu, (int)Shop::getContextShopID());
			$this->_html .= $this->displayConfirmation($this->l('Menu order changed'));
		} else if (Tools::isSubmit('addLink')) {

			if ((!count($links_label)) && (!count($labels)));
			else if (!count($links_label))
				$this->_html .= $this->displayError($this->l('Please, fill the "Link" field'));
			else if (!count($labels))
				$this->_html .= $this->displayError($this->l('Please add a label'));
			else if (!isset($labels[$default_language]))
				$this->_html .= $this->displayError($this->l('Please add a label for your default language'));
			else {
				FlexMenuItem::addLink($links_label, $labels, Tools::getValue('new_window', 0), (int)Shop::getContextShopID());
				$this->_html .= $this->displayConfirmation($this->l('The link has been added'));
			}
			$update_cache = true;
		} else if (Tools::isSubmit('removeLink')) {
			$id_pk_flexmenu_links = Tools::getValue('id_pk_flexmenu_links', 0);
			FlexMenuItem::removeLink($id_pk_flexmenu_links, (int)Shop::getContextShopID());
			Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', str_replace(array('LNK' . $id_pk_flexmenu_links . ',', 'LNK' . $id_pk_flexmenu_links), '', Configuration::get('MOD_BLOCKTOPMENU_ITEMS')));
			$this->_html .= $this->displayConfirmation($this->l('The link has been removed'));
			$update_cache = true;
		} else if (Tools::isSubmit('editLink')) {
			$id_pk_flexmenu_links = (int)Tools::getValue('id_pk_flexmenu_links', 0);
			$id_shop = (int)Shop::getContextShopID();

			if (!Tools::isSubmit('link')) {				
				$link_params = FlexMenuItem::getCustomLinkLang($id_pk_flexmenu_links, $id_shop);
			} else {
				FlexMenuItem::updateLink(Tools::getValue('link'), Tools::getValue('label'), Tools::getValue('new_window', 0), (int)$id_shop, (int)$id_pk_flexmenu_links, (int)$id_pk_flexmenu_links);
				$this->_html .= $this->displayConfirmation($this->l('The link has been edited'));
			}
		} else if (Tools::isSubmit('addTab')) {
			return $this->displayAddTabForm();
		} 
		
		$this->_html .= '<fieldset class="menuItem" data-shop-id="'.(int)Shop::getContextShopID().'" data-lang-id="'.(int)$id_lang.'"><legend>'.$this->l('Main menu').'</legend><a class="button addnewitem" href="'.AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&addTab">'.$this->l('+ Add New Item').'</a>';
		$links = FlexMenuItem::gets((int)$id_lang, null, (int)Shop::getContextShopID());

		if ($num = count($links)) {
			$i = 1;			
			foreach ($links as $link) {
				$itemData = $this->getItemParams($link['label']);
				$this->_html .= '
					<div class="list_menuitem">
						'.(isset($link['active']) && ($link['active'] == 1) ? '<img src="'.$this->_path.'img/act.png" />' : ' <img src="'.$this->_path.'img/pas.png" />').'<span>'.Tools::safeOutput($itemData['name']).'</span>
							<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
								<input type="hidden" name="id_pk_flexmenu_menus" id="id_menu" value="'.(int)$link['id_pk_flexmenu_menus'].'" />
								<input type="submit" name="submitMenuItemEdit" value="'.$this->l('Edit').'" class="button editbutton" />
								<input type="submit" name="moveUp" value="'.$this->l('Up').'" class="button moveup'.($i == 1 ? " hide" : "").'" />
								<input type="submit" name="moveDown" value="'.$this->l('Down').'" class="button movedown'.($i == $num ? " hide" : "").'" />
								<input type="submit" name="submitMenuTabRemove" value="'.$this->l('Remove').'" class="button" />	
							</form>						
					</div>';
					$i++;
			}
		}

		$this->_html .= '</fieldset><br/>
		<fieldset class="menuItem" data-shop-id="'.(int)Shop::getContextShopID().'">
			<legend>'.$this->l('Custom Links').'</legend>';
			$links = FlexMenuItem::getLinks((int)$id_lang, null, (int)Shop::getContextShopID());		

			if (count($links)) {
				$i = 1;
				foreach ($links as $link) {					
				$this->_html .= '
				<div class="list_menuitem customlinks">
					<span>#'.$i.'. '.Tools::safeOutput($link['label']).'</span>
					<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
						<input type="hidden" name="id_pk_flexmenu_links" value="'.(int)$link['id_pk_flexmenu_links'].'" />
						<input type="submit" name="editLink" value="'.$this->l('Edit').'" class="button editbutton" />
						<input type="submit" name="removeLink" value="'.$this->l('Remove').'" class="button" />
					</form>
				</div>';
				$i++;
				}
			}
							
			$this->_html .= '<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post" id="form" class="flexmenu">';
			foreach ($languages as $language) {
			$this->_html .= '
				<div class="languageContainer" id="link_label_'.(int)$language['id_lang'].'" style="display: '.($language['id_lang'] == $id_lang ? 'block' : 'none').';">
					<label>'.$this->l('Label').'</label>
					<div class="margin-form">
						<input type="text" name="label['.(int)$language['id_lang'].']" id="label_'.(int)$language['id_lang'].'" size="70" class="label-lang" data-langID="'.(int)$language['id_lang'].'" value="'.(isset($link_params['label'][$language['id_lang']]) ? Tools::safeOutput($link_params['label'][$language['id_lang']]) : '').'" />'.$this->displayFlags($languages, (int)$id_lang, $divLangName, 'link_label', true);
					$this->_html .= '</div><label>'.$this->l('Link').'</label>
					<div class="margin-form">
						<input type="text" name="link['.(int)$language['id_lang'].']" id="link_'.(int)$language['id_lang'].'" value="'.(isset($link_params['link'][$language['id_lang']]) ? Tools::safeOutput($link_params['link'][$language['id_lang']]) : '').'" size="70" />
					</div>
				</div>';
			}

			$this->_html .= '<label style="clear: both;">'.$this->l('New Window').'</label>
				<div class="margin-form activeLabel">
					<input style="clear: both;" type="checkbox" name="new_window" value="1" '.(isset($link_params['new_window']) && $link_params['new_window'] ? 'checked' : '').'/>
				</div>
				<div class="margin-form">';
					if (Tools::isSubmit('id_pk_flexmenu_links'))
						$this->_html .= '<input type="hidden" name="id_pk_flexmenu_links" value="'.(int)Tools::getValue('id_pk_flexmenu_links').'" />';
					if (Tools::isSubmit('editLink'))
						$this->_html .= '<input type="submit" name="editLink" value="'.$this->l('Edit').'" class="button addlink" />&nbsp;&nbsp;';
					$this->_html .= '<input type="submit" name="addLink" value="'.$this->l('Add').'" class="button addlink" />
				</div>
			</form>';
			
		$this->_html .= '</fieldset><br/>';

		return $this->_html;
	}	

	public function displayAddTabForm($id_menu = NULL) {
				
		$id_shop = (int)Shop::getContextShopID();	
		$id_lang = (int)Context::getContext()->language->id;	
		$this->context->controller->addJS(($this->_path) . 'js/itemmanager-main.js');

		if (isset($id_menu)) {
			if (!Tools::isSubmit('link')) {
				$link_params = FlexMenuItem::getLinkLang($id_menu, $id_shop);				
				$section_settings = FlexMenuItem::getinside($id_menu);				
			}
		}
		
		$languages = $this->context->controller->getLanguages();
		$default_language = (int)Configuration::get('PS_LANG_DEFAULT');

		$labels = Tools::getValue('label') ? array_filter(Tools::getValue('label'), 'strlen') : array();
		$links_label = Tools::getValue('link') ? array_filter(Tools::getValue('link'), 'strlen') : array();
		$spacer = str_repeat('&nbsp;', $this->spacer_size);
		$divLangName = 'link_label';

		$this->_html .= '<fieldset class="menuItem" data-shop-id="'.(int)Shop::getContextShopID().'">';
			if (isset($id_menu)) {
				$this->_html .= '<legend>'.$this->l('Edit Menu Item').'</legend>';
			} else {
				$this->_html .= '<legend>'.$this->l('Add New Item').'</legend>';
			}
			if (isset($id_menu)) {
				$this->_html .= '<input type="hidden" name="id_pk_flexmenu_menus" id="id_menu" value="'.$id_menu.'" />';
			} else {
				$this->_html .= '<input type="hidden" name="id_pk_flexmenu_menus" id="id_menu" value="NONE" />';
			}
			$this->_html .= '<label>'.$this->l('Item').'</label>
			<div class="margin-form">
				<select id="topLevelLinks" data-lang="'.$id_lang.'" style="height:30px">';
					// BEGIN Categories
					$lnk = new Link();
					$this->_html .= '<optgroup label="'.$this->l('Categories').'">';					
					$this->getCategoryOption((isset($link_params['label']) ? $link_params['label'] : ''), 1, (int)$id_lang, (int)Shop::getContextShopID());
					$this->_html .= '</optgroup>';

					// BEGIN CMS
					$this->_html .= '<optgroup label="'.$this->l('CMS').'">';
					$this->getCMSOptions(0, 1, $id_lang, (isset($link_params['label']) ? $link_params['label'] : ''));
					$this->_html .= '</optgroup>';

					// BEGIN SUPPLIER
					$this->_html .= '<optgroup label="'.$this->l('Supplier').'">';
					$suppliers = Supplier::getSuppliers(false, $id_lang);
					foreach ($suppliers as $supplier)
						$this->_html .= '<option '.(isset($link_params['label']) ? ($link_params['label'] == 'SUP'.$supplier['id_supplier'] ? 'selected' : '') : '').' value="SUP'.$supplier['id_supplier'].'">'.$spacer.$supplier['name'].'</option>';
					$this->_html .= '</optgroup>';

					// BEGIN Manufacturer
					$this->_html .= '<optgroup label="'.$this->l('Manufacturer').'">';
					$manufacturers = Manufacturer::getManufacturers(false, $id_lang);
					foreach ($manufacturers as $manufacturer)
						$this->_html .= '<option '.(isset($link_params['label']) ? ($link_params['label'] == 'MAN'.$manufacturer['id_manufacturer'] ? 'selected' : '') : '').' value="MAN'.$manufacturer['id_manufacturer'].'">'.$spacer.$manufacturer['name'].'</option>';
					$this->_html .= '</optgroup>';									

					// BEGIN Shops
					if (Shop::isFeatureActive()) {
						$this->_html .= '<optgroup label="'.$this->l('Shops').'">';
						$shops = Shop::getShopsCollection();
						foreach ($shops as $shop) {
							if (!$shop->setUrl() && !$shop->getBaseURL())
								continue;
							
							$this->_html .= '<option '.(isset($link_params['label']) ? ($link_params['label'] == 'SHOP'.(int)$shop->id ? 'selected' : '') : '').' value="SHOP'.(int)$shop->id.'">'.$spacer.$shop->name.'</option>';
						}
						$this->_html .= '</optgroup>';
					}

					// BEGIN Blog
					$links = FlexMenuItem::getBlogLinks($id_lang);
					if ($links != false) {
						$this->_html .= '<optgroup label="'.$this->l('Blog').'">';				
						$this->_html .= '<option value="BLG0">'.$this->l('Blog').'</option>';
						foreach ($links as $link) {
							$this->_html .= '<option '.(isset($link_params['label']) ? ($link_params['label'] == 'BLG'.(int)$link['id_simpleblog_category'] ? 'selected' : '') : '').' value="BLG'.(int)$link['id_simpleblog_category'].'">'.$link['name'].'</option>';
						}
						$this->_html .= '</optgroup>';
					}

					// BEGIN Testimonials
					$this->_html .= '<optgroup label="'.$this->l('Testimonials').'">';				
					$this->_html .= '<option value="TST0">'.$this->l('Testimonials').'</option>';
					$this->_html .= '</optgroup>';

					// BEGIN PS links
					$this->_html .= '<optgroup label="'.$this->l('Prestashop Pages').'">';				
					$this->_html .= '<option value="PSL0">'.$this->l('Home').'</option>';
					$this->_html .= '<option value="PSL1">'.$this->l('Specials').'</option>';
					$this->_html .= '<option value="PSL2">'.$this->l('New products').'</option>';
					$this->_html .= '<option value="PSL3">'.$this->l('Best sellers').'</option>';
					$this->_html .= '<option value="PSL4">'.$this->l('Our stores').'</option>';
					$this->_html .= '<option value="PSL5">'.$this->l('Contact us').'</option>';
					$this->_html .= '<option value="PSL6">'.$this->l('Sitemap').'</option>';
					$this->_html .= '</optgroup>';

					// BEGIN Menu Top Links
					$this->_html .= '<optgroup label="'.$this->l('Menu Top Links').'">';
					$links = FlexMenuItem::getLinks($id_lang, null, (int)Shop::getContextShopID());
					foreach ($links as $link) {
						if ($link['label'] == '') {
							$link = FlexMenuItem::getLink($link['id_pk_flexmenu_links'], $default_language, (int)Shop::getContextShopID());
							$this->_html .= '<option '.(isset($link_params['label']) ? ($link_params['label'] == 'LNK'.(int)$link[0]['id_pk_flexmenu_links'] ? 'selected' : '') : '').' value="LNK'.(int)$link[0]['id_pk_flexmenu_links'].'">'.$spacer.$link[0]['label'].'</option>';
						} else
							$this->_html .= '<option '.(isset($link_params['label']) ? ($link_params['label'] == 'LNK'.(int)$link['id_pk_flexmenu_links'] ? 'selected' : '') : '').' value="LNK'.(int)$link['id_pk_flexmenu_links'].'">'.$spacer.$link['label'].'</option>';
					}
					$this->_html .= '</optgroup>';
					$this->_html .= '</select></div>
					<script>
					$.ajaxSetup({
				        type: "POST",
				        url: "'.Tools::getShopProtocol().Context::getContext()->shop->domain.Context::getContext()->shop->physical_uri.'modules/pk_flexmenu/ajax/queries.php",
				        error: function(jqXHR, exception) {
				            if (jqXHR.status === 0) {
				                alert("Not connect.\n Verify Network.");
				            } else if (jqXHR.status == 404) {
				                alert("Requested page not found. [404]");
				            } else if (jqXHR.status == 500) {
				                alert("Internal Server Error [500].");
				            } else if (jqXHR.status == 401) {
				                alert("Unauthorised access [401]");
				            } else if (jqXHR.status == 403) {
				                alert("Forbidden resouce can\'t be accessed [403]");
				            } else if (jqXHR.status == 503) {
				                alert("Service Unavailable [503]");
				            } else if (exception === "parsererror") {
				                alert("Requested JSON parse failed.");
				            } else if (exception === "timeout") {
				                alert("Time out error.");
				            } else if (exception === "abort") {
				                alert("Ajax request aborted.");
				            } else {
				                alert("Uncaught Error.\n" + jqXHR.responseText);
				            }
				        }
				    });
					</script>
			';			
			$this->_html .= '
			<div class="after-label-add-content'.(isset($link_params['label']) ? '' : ' hide').'">
				<label style="clear: both;">'.$this->l('Active').'</label>
				<div class="margin-form activeLabel">
					<input type="checkbox" name="active_item" id="active_item" value="'.((isset($link_params['active']) && ($link_params['active'] == 1)) || !isset($link_params['active']) ? '0' : '1').'" '.((isset($link_params['active']) && ($link_params['active'] == 1)) || !isset($link_params['active']) ? 'checked' : '').'/>
				</div>
				<label style="clear: both;">'.$this->l('Narrow Menu').'</label>
				<div class="margin-form activeLabel">
					<input type="checkbox" name="narrow" id="narrow" value="'.((isset($link_params['narrow']) && ($link_params['narrow'] == 1)) || !isset($link_params['narrow']) ? '0' : '1').'" '.((isset($link_params['narrow']) && ($link_params['narrow'] == 0)) || !isset($link_params['narrow']) ? '' : 'checked').'/>
				</div>
				<div class="background-section'.((isset($link_params['narrow']) && ($link_params['narrow'] == 1)) ? ' hide' : '').'">			
				<label style="clear: both;">'.$this->l('Background').'</label>
				<div class="margin-form activeLabel">
					<div id="main_image_upload" data-type-content="image">
						<form method="post" enctype="multipart/form-data"  action="'.$this->_path.'ajax/upload.php">  
							<input type="hidden" value="'.(isset($link_params["main_image"]) ? $link_params["main_image"] : '').'" name="mainimageedit">
							<input id="main_image" type="file" name="main_image" />
							<input type="submit" name="uploadMainImage" id="uploadMainImage" />
				        </form> 	
			       		<div id="main_response"></div>
						<img id="main_image_prev" src="'.(isset($link_params["main_image"]) && $link_params["main_image"] != "" ? $this->_path.'uploads/'.$link_params["main_image"] : $this->_path.'img/demo.jpg').'" alt="" class="mainimagepreview previewImage"/>						
						<a href="#" class="removemainimg '.(isset($link_params["main_image"]) && $link_params["main_image"] != "" ? "" : " hide").'">Remove</a>
					</div>
				</div>	
				</div>
				<label style="clear: both;">'.$this->l('Content').'</label>
				<div class="margin-form">
					<div id="content_panel" class="content_panel '.((isset($link_params['active']) && ($link_params['narrow'] == 1)) ? "narr":"").'">
						<div class="cleft_panel cpanel'.(isset($section_settings["state_left"]) && ($section_settings["state_left"] == 1) ? ' active' : '').'" data-section-name="left">
							<div class="section-switcher">
								<span id="left_section_switcher"><strong></strong>
								<label for="hide_left_panel" class="hide_left_panel" title="'.$this->l('Section State Switcher').'" data-section="state_left"></label>
								<input type="checkbox" name="left_panel_state" id="hide_left_panel" value="1" '.(isset($section_settings["state_left"]) && ($section_settings["state_left"] == 1) ? 'checked' : '').'/>
								<i title="'.$this->l('Edit This Section').'">'.$this->l('Left Section').'</i></span>
							</div>
							<div id="left-panel-container" class="panel-container">

								<ul class="selectors">
									<li '.(isset($section_settings["left"]) && ($section_settings["left"] == "IMAGE") ? 'class="act"' : '').'>
										<label class="panel_option" for="image_left_panel" data-type="image">'.$this->l('Image').'</label>
										<input type="radio" name="left_panel" value="IMAGE" id="image_left_panel" '.(isset($section_settings["left"]) && ($section_settings["left"] == "IMAGE") ? 'checked' : '').' />
									</li>
									<li '.(isset($section_settings["left"]) && ($section_settings["left"] == "PRD") ? 'class="act"' : '').'>
										<label class="panel_option" for="product_left_panel" data-type="products">'.$this->l('Products').'</label>	
										<input type="radio" name="left_panel" value="PRD" id="product_left_panel" '.(isset($section_settings["left"]) && ($section_settings["left"] == "PRD") ? 'checked' : '').' />
									</li>
									<li id="menuTypeVid" '.(isset($section_settings["left"]) && ($section_settings["left"] == "VID") ? 'class="act"' : '').'>
										<label class="panel_option" for="vid_left_panel" data-type="left-vid">'.$this->l('Video').'</label>	
										<input type="radio" name="left_panel" value="VID" id="vid_left_panel" '.(isset($section_settings["left"]) && ($section_settings["left"] == "VID") ? 'checked' : '').' />
									</li>
								</ul>	

								<div id="left_image_upload" class="margin_field '.(isset($section_settings["left"]) && ($section_settings["left"] == "IMAGE") ? '' : 'hide').'" data-type-content="image">
									<form method="post" enctype="multipart/form-data"  action="'.$this->_path.'ajax/upload.php">  
										<input type="hidden" value="'.(isset($section_settings["left_image"]) && ($section_settings["left"] == "IMAGE") ? $section_settings["left_image"] : '').'" name="leftimageedit">
										<input id="left_image" type="file" name="left_image" />
										<input type="submit" name="uploadLeftImage" id="uploadLeftImage" />
							        </form>
							        <div id="left_response"></div><br/>
						       		<strong>'.$this->l('Recommended image width is 180px').'</strong>
									<img id="left_image_prev" src="'.(isset($section_settings['left_image']) && $section_settings['left_image'] != "" ? $this->_path.'uploads/'.$section_settings['left_image'] : $this->_path.'img/demo.jpg').'" alt="" class="leftimagepreview previewImage"/>
									<input type="text" value="'.(isset($section_settings["left_image_link"]) && ($section_settings["left_image_link"] != "") ? $section_settings["left_image_link"] : '').'" name="leftimagelink" id="leftimagelink" data-dbname="left_image_link" class="imagelink" placeholder="Image Link..."><br/>
									<a href="#" class="removeimg">Remove</a>
								</div>				 				 
								
								<div id="leftproducts" class="margin_field products-container '.(isset($section_settings["left"]) && ($section_settings["left"] == "PRD") ? '' : 'hide').'" data-type-content="products">
									<input type="text" data-dbname="left_products_title" value="'.(isset($section_settings["left_products_title"]) && ($section_settings["left_products_title"] != "") ? $section_settings["left_products_title"] : '').'" id="left_products_title" placeholder="'.$this->l('Section title').'" class="imagelink" /><br/><br/>
									<input type="hidden" name="leftproductsitems" id="leftproductsitemsInput" value="' . (isset($section_settings['left']) && ($section_settings['left'] == "PRD") ? $section_settings['left_products'] : '').'" size="70" />
									<input type="text" value="" id="leftproduct_autocomplete_input" placeholder="'.$this->l('Product name').'" />
									<ul id="leftproductsitems" data-field="left_products">';
									if(isset($section_settings['left_products'])){
										$lfprdns = explode(",", $section_settings['left_products']);					
										foreach ($lfprdns as $lfprdn) {
											$lid = str_replace("PRD", "", $lfprdn);		
											$lproduct = new Product((int)$lid, true, (int)$this->context->language->id);			
											if (Validate::isLoadedObject($lproduct)) {
												$nm = substr($lproduct->name, 0, 18);
												if (strlen($nm) != strlen($lproduct->name)) $nm = $nm."...";
												$this->_html .= '<li data-value="PRD'.$lid.'"><img src="'.$this->getProductCover($lid).'" alt="" />'.$nm.'<br/><span class="removeThis"></span></li>'.PHP_EOL;
											}
											}
										}		
									$this->_html .= '
									</ul>
								</div>
								<div id="leftvideo" class="margin_field vid-container '.(isset($section_settings["left"]) && ($section_settings["left"] == "VID") ? '' : 'hide').'" data-type-content="left-vid">
									<input type="hidden" name="leftvid" id="leftvidInput" value="'.(isset($section_settings["left"]) && ($section_settings["left"] == "VID") ? $section_settings["left_video"] : '').'" size="70" />
									Video link:
									<input type="text" value="'.(isset($section_settings["left_video"]) ? $section_settings["left_video"] : '').'" data-dbname="left_video" id="leftvid" />
									<p>Links example:<br/> <strong>//www.youtube.com/embed/pt2Wd_e-1nQ</strong><br/> or<br/> <strong>//player.vimeo.com/video/31160843</strong></p>
								</div>
							</div>
						</div>

						<div class="main_section cpanel'.(isset($section_settings["state_main"]) && ($section_settings["state_main"] == 1) ? ' active' : '').'" data-section-name="main">
							<div class="section-switcher">
								<span id="main_section_switcher"><strong></strong>
								<label for="hide_main_panel" class="hide_main_panel" title="'.$this->l('Section State Switcher').'" data-section="state_main"></label>
								<input type="checkbox" name="hide_main_panel" id="hide_main_panel" value="1" '.(isset($section_settings["state_main"]) && ($section_settings["state_main"] == 1) ? 'checked' : '').'/>
								<i title="'.$this->l('Edit This Section').'">'.$this->l('Main Section').'</i></span>
							</div>

							<div id="main-panel-container" class="panel-container">
								<ul class="selectors">
									<li id="menuTypeLinks" '.(isset($section_settings["main"]) && ($section_settings["main"] == "LINKS") ? 'class="act"' : '').'>
										<label class="panel_option" for="links_main_panel" data-type="main-links">'.$this->l('Links').'</label>
										<input type="radio" name="main_panel" value="LINKS" id="links_main_panel" '.(isset($section_settings["main"]) && ($section_settings["main"] == "LINKS") ? 'checked' : '').' />
									</li>
									<li id="menuTypeProducts" '.(isset($section_settings["main"]) && ($section_settings["main"] == "PRD") ? 'class="act"' : '').'>
										<label class="panel_option" for="product_main_panel" data-type="main-products">'.$this->l('Products').'</label>	
										<input type="radio" name="main_panel" value="PRD" id="product_main_panel" '.(isset($section_settings["main"]) && ($section_settings["main"] == "PRD") ? 'checked' : '').' />
									</li>
									<li id="menuTypeCMSP" '.(isset($section_settings["main"]) && ($section_settings["main"] == "CMS_P") ? 'class="act"' : '').'>
										<label class="panel_option" for="cms_p_main_panel" data-type="main-cms_pages">'.$this->l('CMS Pages').'</label>	
										<input type="radio" name="main_panel" value="CMS_P" id="cms_p_main_panel" '.(isset($section_settings["main"]) && ($section_settings["main"] == "CMS_P") ? 'checked' : '').' />
									</li>
									<li id="menuTypeVid" '.(isset($section_settings["main"]) && ($section_settings["main"] == "VID") ? 'class="act"' : '').'>
										<label class="panel_option" for="cms_p_main_panel" data-type="main-vid">'.$this->l('Video').'</label>	
										<input type="radio" name="main_panel" value="VID" id="vid_main_panel" '.(isset($section_settings["main"]) && ($section_settings["main"] == "VID") ? 'checked' : '').' />
									</li>
								</ul>	

								<div id="mainlinks_links" class="margin_field '.(isset($section_settings["main"]) && ($section_settings["main"] == "LINKS") ? '' : 'hide').'" data-type-content="main-links">
									<span class="descr">'.$this->l('Pick a list of menu items').'</span><br/>
									<input type="hidden" name="mainlinksitems" id="mainlinksitemsInput" value="'.(isset($section_settings["main"]) && ($section_settings["main"] == "LINKS") ? $section_settings["main_links"] : '').'" size="70" />
									<div class="shop_source">										
										<select id="mainlinksavailableItems">';
										// BEGIN Categories
										$this->_html .= '<optgroup label="'.$this->l('Categories').'">';
										$this->getCategoryOption((isset($link_params['label']) ? $link_params['label'] : ''), 1, (int)$id_lang, (int)Shop::getContextShopID());
										$this->_html .= '</optgroup>';

										// BEGIN CMS
										$this->_html .= '<optgroup label="'.$this->l('CMS').'">';
										$this->getCMSOptions(0, 1, $id_lang, (isset($link_params['label']) ? $link_params['label'] : ''));
										$this->_html .= '</optgroup>';

										// BEGIN SUPPLIER
										$this->_html .= '<optgroup label="'.$this->l('Supplier').'">';
										$suppliers = Supplier::getSuppliers(false, $id_lang);
										foreach ($suppliers as $supplier)
											$this->_html .= '<option value="SUP'.$supplier['id_supplier'].'">'.$spacer.$supplier['name'].'</option>';
										$this->_html .= '</optgroup>';

										// BEGIN Manufacturer
										$this->_html .= '<optgroup label="'.$this->l('Manufacturer').'">';
										$manufacturers = Manufacturer::getManufacturers(false, $id_lang);
										foreach ($manufacturers as $manufacturer)
											$this->_html .= '<option value="MAN'.$manufacturer['id_manufacturer'].'">'.$spacer.$manufacturer['name'].'</option>';
										$this->_html .= '</optgroup>';

										// BEGIN Blog
										$this->_html .= '<optgroup label="'.$this->l('Blog').'">';
										$this->_html .= '<option value="BLG0">'.$this->l('Blog').'</option>';
										$links = FlexMenuItem::getBlogLinks($id_lang);
										if ($links) {
											foreach ($links as $link)
												$this->_html .= '<option value="BLG'.$link['id_simpleblog_category'].'">'.$spacer.$link['name'].'</option>';
										}
										$this->_html .= '</optgroup>';	

										// BEGIN Testimonials
										$this->_html .= '<optgroup label="'.$this->l('Testimonials').'">';				
										$this->_html .= '<option value="TST0">'.$this->l('Testimonials').'</option>';
										$this->_html .= '</optgroup>';								

										// BEGIN PS links
										$this->_html .= '<optgroup label="'.$this->l('Prestashop Pages').'">';				
										$this->_html .= '<option value="PSL0">'.$this->l('Home').'</option>';
										$this->_html .= '<option value="PSL1">'.$this->l('Specials').'</option>';
										$this->_html .= '<option value="PSL2">'.$this->l('New products').'</option>';
										$this->_html .= '<option value="PSL3">'.$this->l('Best sellers').'</option>';
										$this->_html .= '<option value="PSL4">'.$this->l('Our stores').'</option>';
										$this->_html .= '<option value="PSL5">'.$this->l('Contact us').'</option>';
										$this->_html .= '<option value="PSL6">'.$this->l('Sitemap').'</option>';
										$this->_html .= '</optgroup>';

										// BEGIN Shops
										if (Shop::isFeatureActive()) {
											$this->_html .= '<optgroup label="'.$this->l('Shops').'">';
											$shops = Shop::getShopsCollection();
											foreach ($shops as $shop) {
												if (!$shop->setUrl() && !$shop->getBaseURL())
													continue;
												$this->_html .= '<option value="SHOP'.(int)$shop->id.'">'.$spacer.$shop->name.'</option>';
											}
											$this->_html .= '</optgroup>';
										}

										// BEGIN Menu Top Links
										$this->_html .= '<optgroup label="'.$this->l('Menu Top Links').'">';
										$links = FlexMenuItem::getLinks($id_lang, null, (int)Shop::getContextShopID());
										foreach ($links as $link) {
											if ($link['label'] == '') {
												$link = FlexMenuItem::getLink($link['id_pk_flexmenu_links'], $default_language, (int)Shop::getContextShopID());
												$this->_html .= '<option value="LNK'.(int)$link[0]['id_pk_flexmenu_links'].'">'.$spacer.$link[0]['label'].'</option>';
											} else
												$this->_html .= '<option value="LNK'.(int)$link['id_pk_flexmenu_links'].'">'.$spacer.$link['label'].'</option>';
										}
										$this->_html .= '</optgroup>';
										$this->_html .= '</select>
									</div>
									<ul id="mainlinksitems" data-field="main_links">';
										$panel = "mainlinks";
										if(isset($section_settings["main_links"]))
											$this->makeMenuOption($id_menu, $panel);
										$this->_html .= '
									</ul>			
								</div>
								<div id="mainproducts" class="margin_field products-container '.(isset($section_settings["main"]) && ($section_settings["main"] == "PRD") ? '' : 'hide').'" data-type-content="main-products">
									<input type="hidden" name="mainproductsitems" id="mainproductsitemsInput" value="'.(isset($section_settings["main"]) && ($section_settings["main"] == "PRD") ? $section_settings["main_products"] : '').'" size="70" />
									'.$this->l('Product name').':</span>
									<input type="text" value="" id="mainproduct_autocomplete_input" />
									<ul id="mainproductsitems" data-field="main_products">';
										if(isset($section_settings['main_products'])){
											$main_vals = explode(",", $section_settings["main_products"]);					
											foreach ($main_vals as $val) {
												$mid = str_replace("PRD", "", $val);		
												$mproduct = new Product((int)$mid, true, $id_lang);									

												if (Validate::isLoadedObject($mproduct)) {
													$nm = substr($mproduct->name, 0, 18);
													if (strlen($nm) != strlen($mproduct->name)) $nm = $nm."...";
													$this->_html .= '<li data-value="PRD'.$mid.'"><img src="'.$this->getProductCover($mid).'" alt="" />'.$nm.'<br/><span class="removeThis"></span></li>'.PHP_EOL;
												}
												}
											}		
										$this->_html .= '
									</ul>
								</div>
								<div id="maincmspages" class="margin_field cms_p-container '.(isset($section_settings["main"]) && ($section_settings["main"] == "CMS_P") ? '' : 'hide').'" data-type-content="main-cms_pages">
									<input type="hidden" name="maincmsp" id="maincmspInput" value="'.(isset($section_settings["main"]) && ($section_settings["main"] == "CMS_P") ? $section_settings["main_cmsp"] : '').'" size="70" />
									<select id="maincmsp" data-dbname="main_cmsp">
										<optgroup label="'.$this->l('CMS').'">';
										//if (isset($section_settings["main_cmsp"]))
											$this->getCMSOptions(0, 1, $id_lang, (isset($link_params['label']) ? $link_params['label'] : ''));
										$this->_html .= '</optgroup></select>
								</div>
								<div id="maincmspages" class="margin_field vid-container '.(isset($section_settings["main"]) && ($section_settings["main"] == "VID") ? '' : 'hide').'" data-type-content="main-vid">
									<input type="hidden" name="mainvid" id="mainvidInput" value="'.(isset($section_settings["main"]) && ($section_settings["main"] == "VID") ? $section_settings["main_video"] : '').'" size="70" />
									Video link:
									<input type="text" value="'.(isset($section_settings["main_video"]) ? $section_settings["main_video"] : '').'" data-dbname="main_video" id="mainvid" />
									<p>Links example:<br/> <strong>//www.youtube.com/embed/pt2Wd_e-1nQ</strong><br/> or<br/> <strong>//player.vimeo.com/video/31160843</strong></p>
								</div>
							</div>
						</div>
					
						<div class="cright_panel cpanel'.(isset($section_settings["state_right"]) && ($section_settings["state_right"] == 1) ? ' active' : '').'" data-section-name="right">
							<div class="section-switcher">
								<span id="right_section_switcher"><strong></strong>
								<label for="hide_Left_panel" class="hide_right_panel" title="'.$this->l('Section State Switcher').'" data-section="state_right"></label>
								<input type="checkbox" name="right_panel_state" id="hide_right_panel" value="1" '.(isset($section_settings["state_right"]) && ($section_settings["state_right"] == 1) ? 'checked' : '').'/>
								<i title="'.$this->l('Edit This Section').'">'.$this->l('Right Section').'</i></span>
							</div>
							<div id="right-panel-container" class="panel-container">
								<ul class="selectors">
									<li '.(isset($section_settings["right"]) && ($section_settings["right"] == "IMAGE") ? 'class="act"' : '').'>
										<label class="panel_option" for="image_right_panel" data-type="image">'.$this->l('Image').'</label>
										<input type="radio" name="right_panel" value="IMAGE" id="image_right_panel" '.(isset($section_settings["right"]) && ($section_settings["right"] == "IMAGE") ? 'checked' : '').' />
									</li>
									<li '.(isset($section_settings["right"]) && ($section_settings["right"] == "PRD") ? 'class="act"' : '').'>
										<label class="panel_option" for="product_right_panel" data-type="products">'.$this->l('Products').'</label>	
										<input type="radio" name="right_panel" value="PRD" id="product_right_panel" '.(isset($section_settings["right"]) && ($section_settings["right"] == "PRD") ? 'checked' : '').' />
									</li>
									<li id="menuTypeVid" '.(isset($section_settings["right"]) && ($section_settings["right"] == "VID") ? 'class="act"' : '').'>
										<label class="panel_option" for="vid_right_panel" data-type="right-vid">'.$this->l('Video').'</label>	
										<input type="radio" name="right_panel" value="VID" id="vid_right_panel" '.(isset($section_settings["right"]) && ($section_settings["right"] == "VID") ? 'checked' : '').' />
									</li>
								</ul>						
								<div id="right_image_upload" class="margin_field '.(isset($section_settings["right"]) && ($section_settings["right"] == "IMAGE") ? '' : 'hide').'" data-type-content="image">
									<form method="post" enctype="multipart/form-data"  action="'.$this->_path.'ajax/upload.php">  
										<input type="hidden" value="'.(isset($section_settings["right_image"]) ? $section_settings["right_image"] : '').'" name="rightimageedit">
										<input id="right_image" type="file" name="right_image" />
										<input type="submit" name="uploadRightImage" id="uploadRightImage" />
							        </form> 	
						       		<div id="right_response"></div><br/>
						       		<strong>'.$this->l('Recommended image width is 180px').'</strong>	
									<img id="right_image_prev" src="'.(isset($section_settings["right_image"]) && $section_settings["right_image"] != "" ? $this->_path.'uploads/'.$section_settings["right_image"] : $this->_path.'img/demo.jpg').'" alt="" class="rightimagepreview previewImage"/>
									<input type="text" value="'.(isset($section_settings["right_image_link"]) && ($section_settings["right_image_link"] != "") ? $section_settings["right_image_link"] : '').'" name="rightimagelink" id="rightimagelink" data-dbname="right_image_link" class="imagelink" placeholder="Image Link..."><br/>
									<a href="#" class="removeimg">Remove</a>
								</div>
								<div id="right_products" class="margin_field '.(isset($section_settings["right"]) && ($section_settings["right"] == "PRD") ? '' : 'hide').'" data-type-content="products">
									<div id="ajax_choose_product">
										<input type="text" data-dbname="right_products_title" value="'.(isset($section_settings["right_products_title"]) && ($section_settings["right_products_title"] != "") ? $section_settings["right_products_title"] : '').'" id="right_products_title" placeholder="Section title" class="imagelink" /><br/><br/>
										<input type="text" value="" id="product_autocomplete_input" placeholder="Start to type product name" />
									<input type="hidden" name="right_product_id" id="right_product_id" value="'.(isset($section_settings["right_products"]) && ($section_settings["right"] == "PRD") ? $section_settings["right_products"] : '').'" />';
									if(isset($section_settings["right_products"])) {	
										$id = str_replace("PRD", "", $section_settings["right_products"]);		
										$product = new Product((int)$id, true, $id_lang);									
										if (Validate::isLoadedObject($product)) {										
											$productname =  $product->name.PHP_EOL;										
										}
									}
									$this->_html .= '<br/><br/>
									<ul id="right_product_id_curr" data-field="right_products">';
									if(isset($section_settings["right_products"])) {
										$right_vals = explode(",", $section_settings["right_products"]);					
										foreach ($right_vals as $val) {
											$id = str_replace("PRD", "", $val);		
											$product = new Product((int)$id, true, $id_lang);
											if (Validate::isLoadedObject($product)) {
												$nm = substr($product->name, 0, 18);
												if (strlen($nm) != strlen($product->name)) $nm = $nm."...";				
												$this->_html .= '<li data-value="PRD'.$id.'"><img src="'.$this->getProductCover($id).'" alt="" />'.$nm.'<br/><span class="removeThis"></span></li>'.PHP_EOL;
											}
											}
										}		
									$this->_html .= '
									</ul>
									</div>
								</div>
								<div id="rightvideo" class="margin_field vid-container '.(isset($section_settings["right"]) && ($section_settings["right"] == "VID") ? '' : 'hide').'" data-type-content="right-vid">
									<input type="hidden" name="rightvid" id="rightvidInput" value="'.(isset($section_settings["right"]) && ($section_settings["right"] == "VID") ? $section_settings["right_video"] : '').'" size="70" />
									Video link:
									<input type="text" value="'.(isset($section_settings["right_video"]) ? $section_settings["right_video"] : '').'" data-dbname="right_video" id="rightvid" />
									<p>Links example:<br/> <strong>//www.youtube.com/embed/pt2Wd_e-1nQ</strong><br/> or<br/> <strong>//player.vimeo.com/video/31160843</strong></p>
								</div>
							</div>
						</div>

						<div class="cbottom_panel cpanel'.(isset($section_settings["state_bottom"]) && ($section_settings["state_bottom"] == 1) ? ' active' : '').'" data-section-name="bottom">

							<div class="section-switcher">
								<span id="bottom_section_switcher"><strong></strong>
								<label for="hide_bottom_panel" class="hide_right_panel" title="'.$this->l('Section State Switcher').'" data-section="state_bottom"></label>
								<input type="checkbox" name="bottom_panel_state" id="hide_bottom_panel" value="1" '.(isset($section_settings["state_bottom"]) && ($section_settings["state_bottom"] == 1) ? 'checked' : '').'/>
								<i title="'.$this->l('Edit This Section').'">'.$this->l('Bottom Section').'</i></span>
							</div>

							<div id="bottom-panel-container" class="panel-container">

								<ul class="selectors">
									<li '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "IMAGE") ? 'class="act"' : '').'>
										<label class="panel_option" for="image_bottom_panel" data-type="image">'.$this->l('Image').'</label>
										<input type="radio" name="bottom_panel" value="IMAGE" id="image_bottom_panel" '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "IMAGE") ? 'checked' : '').' />
									</li>
									<li '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "LINKS") ? 'class="act"' : '').'>
										<label class="panel_option" for="links_bottom_panel" data-type="links">'.$this->l('Links').'</label>
										<input type="radio" name="bottom_panel" value="LINKS" id="links_bottom_panel" '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "LINKS") ? 'checked' : '').' />
									</li>
									<li id="menuTypeVid" '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "VID") ? 'class="act"' : '').'>
										<label class="panel_option" for="vid_bottom_panel" data-type="bottom-vid">'.$this->l('Video').'</label>	
										<input type="radio" name="bottom_panel" value="VID" id="vid_bottom_panel" '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "VID") ? 'checked' : '').' />
									</li>
								</ul>	

								<div id="bottom_image_upload" class="margin_field '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "IMAGE") ? '' : 'hide').'" data-type-content="image">
									<form method="post" enctype="multipart/form-data" action="'.$this->_path.'ajax/upload.php">  
										<input type="hidden" value="'.(isset($section_settings["bottom_image"]) ? $section_settings["bottom_image"] : '').'" name="bottomimageedit">
										<input id="bottom_image" type="file" name="bottom_image" />
										<input type="submit" name="uploadBottomImage" id="uploadBottomImage" />
							        </form> 
							        <div id="right_response"></div><br/>
							        <strong class="width-short'.(isset($section_settings["state_left"]) && ($section_settings["state_left"] == 1) ? '' : ' hide').'">'.$this->l('Recommended image width is 720px').'</strong>
							        <strong class="width-long'.(isset($section_settings["state_left"]) && ($section_settings["state_left"] == 1) ? ' hide' : '').'">'.$this->l('Recommended image width is 918px').'</strong>
									<img id="bottom_image_prev" src="'.(isset($section_settings["bottom_image"]) && $section_settings["bottom_image"] != "" ? $this->_path.'uploads/'.$section_settings["bottom_image"] : $this->_path.'img/demo.jpg').'" alt="" class="bottomimagepreview previewImage"/>
									<input type="text" value="'.(isset($section_settings["bottom_image_link"]) && ($section_settings["bottom_image_link"] != "") ? $section_settings["bottom_image_link"] : '').'" name="bottomimagelink" id="bottomimagelink" data-dbname="bottom_image_link" class="imagelink" placeholder="Image Link..."><br/>
									<a href="#" class="removeimg">Remove</a>
									<div id="bottom_response"></div>		
								</div>									

							 	<div id="bottom_links" class="margin_field '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "LINKS") ? '' : 'hide').'" data-type-content="links">
							 		<input type="text" value="'.(isset($section_settings["bottom_title"]) && ($section_settings["bottom_title"] != "") ? $section_settings["bottom_title"] : '').'" name="bottomimagelink" id="bottomtitle" data-dbname="bottom_title" class="imagelink" placeholder="Bottom Section Title..."><br/><br/>
									<input type="hidden" name="bottomitems" id="bottomitemsInput" value="'.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "LINKS") ? $section_settings["bottom_image"] : '').'" size="70" />
									<select id="bottomavailableItems">';
									// BEGIN Categories
									$this->_html .= '<optgroup label="'.$this->l('Categories').'">';
									$this->getCategoryOption((isset($link_params['label']) ? $link_params['label'] : ''), 1, (int)$id_lang, (int)Shop::getContextShopID());
									$this->_html .= '</optgroup>';

									// BEGIN CMS
									$this->_html .= '<optgroup label="'.$this->l('CMS').'">';
									$this->getCMSOptions(0, 1, $id_lang, "CMS1", (isset($link_params['label']) ? $link_params['label'] : ''));
									$this->_html .= '</optgroup>';

									// BEGIN SUPPLIER
									$this->_html .= '<optgroup label="'.$this->l('Supplier').'">';
									$suppliers = Supplier::getSuppliers(false, $id_lang);
									foreach ($suppliers as $supplier)
										$this->_html .= '<option value="SUP'.$supplier['id_supplier'].'">'.$spacer.$supplier['name'].'</option>';
									$this->_html .= '</optgroup>';

									// BEGIN Manufacturer
									$this->_html .= '<optgroup label="'.$this->l('Manufacturer').'">';
									$manufacturers = Manufacturer::getManufacturers(false, $id_lang);
									foreach ($manufacturers as $manufacturer)
										$this->_html .= '<option value="MAN'.$manufacturer['id_manufacturer'].'">'.$spacer.$manufacturer['name'].'</option>';
									$this->_html .= '</optgroup>';								

									// BEGIN Shops
									if (Shop::isFeatureActive()) {
										$this->_html .= '<optgroup label="'.$this->l('Shops').'">';
										$shops = Shop::getShopsCollection();
										foreach ($shops as $shop) {
											if (!$shop->setUrl() && !$shop->getBaseURL()) continue;
											$this->_html .= '<option value="SHOP'.(int)$shop->id.'">'.$spacer.$shop->name.'</option>';
										}
										$this->_html .= '</optgroup>';
									}								
									// BEGIN Menu Top Links
									$this->_html .= '<optgroup label="'.$this->l('Menu Top Links').'">';
									$links = FlexMenuItem::getLinks($id_lang, null, (int)Shop::getContextShopID());
									foreach ($links as $link) {
										if ($link['label'] == '') {
											$link = FlexMenuItem::getLink($link['id_pk_flexmenu_links'], $default_language, (int)Shop::getContextShopID());
											$this->_html .= '<option value="LNK'.(int)$link[0]['id_pk_flexmenu_links'].'">'.$spacer.$link[0]['label'].'</option>';
										} else
											$this->_html .= '<option value="LNK'.(int)$link['id_pk_flexmenu_links'].'">'.$spacer.$link['label'].'</option>';
									}
									$this->_html .= '</optgroup>';
									$this->_html .= '</select><br/>							
									<ul id="bottomitems" data-field="bottom_links">';
									$panel = "bottomlinks";
									if(isset($section_settings["bottom_links"])) {
										$this->makeMenuOption($id_menu, $panel);
									}
									$this->_html .= '
									</ul>
									<div class="clear">&nbsp;</div>				
								</div>
								<div id="bottomvideo" class="margin_field vid-container '.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "VID") ? '' : 'hide').'" data-type-content="bottom-vid">
									<input type="hidden" name="bottomvid" id="bottomvidInput" value="'.(isset($section_settings["bottom"]) && ($section_settings["bottom"] == "VID") ? $section_settings["bottom_video"] : '').'" size="70" />
									Video link:
									<input type="text" value="'.(isset($section_settings["bottom_video"]) ? $section_settings["bottom_video"] : '').'" data-dbname="bottom_video" id="bottomvid" />
									<p>Links example:<br/> <strong>//www.youtube.com/embed/pt2Wd_e-1nQ</strong><br/> or<br/> <strong>//player.vimeo.com/video/31160843</strong></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
		$this->_html .= '<div class="margin-form"><a class="backtomain" href="'.AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'">'.$this->l('Save').'</a>';
		   $this->_html .= '</div>
		</fieldset><br/>';
		return $this->_html;
	}	

	private function makeMenuOption($id_menu = NULL, $panel = NULL) {

		$menu_item = $this->getMenuItems($id_menu, $panel);		
		$id_lang = (int)$this->context->language->id;
		$id_shop = (int)Shop::getContextShopID();
		
		foreach ($menu_item as $item) {
			if (!$item)
				continue;

			preg_match($this->pattern, $item, $values);			
			$id = (int)substr($item, strlen($values[1]), strlen($item));

			switch (substr($item, 0, strlen($values[1]))) {
				case 'CAT' :
					$category = new Category((int)$id, (int)$id_lang);
					if (Validate::isLoadedObject($category))
						$this->_html .= '<li data-value="CAT' . $id . '">' . $category->name . '<span class="removeThis"></span></li>' . PHP_EOL;
					break;

				case 'PRD' :
					$product = new Product((int)$id, true, (int)$id_lang);
					if (Validate::isLoadedObject($product))
						$this->_html .= '<li data-value="PRD' . $id . '">' . $product->name . '<span class="removeThis"></span></li>' . PHP_EOL;
					break;

				case 'CMS' :
					$cms = new CMS((int)$id, (int)$id_lang);
					if (Validate::isLoadedObject($cms))
						$this->_html .= '<li data-value="CMS' . $id . '">' . $cms->meta_title . '<span class="removeThis"></span></li>' . PHP_EOL;
					break;

				case 'CMS_CAT' :
					$category = new CMSCategory((int)$id, (int)$id_lang);
					if (Validate::isLoadedObject($category))
						$this->_html .= '<li data-value="CMS_CAT' . $id . '">' . $category->name . '<span class="removeThis"></span></li>' . PHP_EOL;
					break;

				case 'MAN' :
					$manufacturer = new Manufacturer((int)$id, (int)$id_lang);
					if (Validate::isLoadedObject($manufacturer))
						$this->_html .= '<li data-value="MAN' . $id . '">' . htmlspecialchars($manufacturer->name).'<span class="removeThis"></span></li>' . PHP_EOL;
					break;

				case 'SUP' :
					$supplier = new Supplier((int)$id, (int)$id_lang);
					if (Validate::isLoadedObject($supplier))
						$this->_html .= '<li data-value="SUP' . $id . '">' . $supplier->name . '<span class="removeThis"></span></li>' . PHP_EOL;
					break;

				case 'BLG' :
					$links = FlexMenuItem::getBlogLink($id_lang, (int)$id);
					if ($links != false) {
						foreach ($links as $link) {
							$this->_html .= '<li data-value="BLG'.$id.'">'.$link['name'].'<span class="removeThis"></span></li>';
						}
					}
					break;

				case 'TST' :
					$this->_html .= '<li data-value="TST0">'.$this->l('Testimonials').'<span class="removeThis"></span></li>';		
					break;		
				case 'PSL' :	
					if ($id == 0)
						$this->_html .= '<li data-value="PSL0">'.$this->l('Home').'<span class="removeThis"></span></li>';
					if ($id == 1)
						$this->_html .= '<li data-value="PSL1">'.$this->l('Specials').'<span class="removeThis"></span></li>';
					if ($id == 2)
						$this->_html .= '<li data-value="PSL2">'.$this->l('New products').'<span class="removeThis"></span></li>';
					if ($id == 3)
						$this->_html .= '<li data-value="PSL3">'.$this->l('Best sellers').'<span class="removeThis"></span></li>';
					if ($id == 4)
						$this->_html .= '<li data-value="PSL4">'.$this->l('Our stores').'<span class="removeThis"></span></li>';
					if ($id == 5)
						$this->_html .= '<li data-value="PSL5">'.$this->l('Contact us').'<span class="removeThis"></span></li>';
					if ($id == 6)
						$this->_html .= '<li data-value="PSL6">'.$this->l('Sitemap').'<span class="removeThis"></span></li>';					
					break;

				case 'LNK' :
					$link = FlexMenuItem::getCustomLink((int)$id, (int)$id_lang, (int)$id_shop);
					if (count($link)) {
						if (!isset($link[0]['label']) || ($link[0]['label'] == '')) {
							$default_language = Configuration::get('PS_LANG_DEFAULT');
							$link = FlexMenuItem::getCustomLink($link[0]['id_pk_flexmenu_links'], (int)$default_language, (int)Shop::getContextShopID());							
						}
						$this->_html .= '<li data-value="LNK' . $link[0]['id_pk_flexmenu_links'] . '">' . $link[0]['label'] . '<span class="removeThis"></span></li>';
					}
					break;
				case 'SHOP' :
					$shop = new Shop((int)$id);
					if (Validate::isLoadedObject($shop))
						$this->_html .= '<li data-value="SHOP' . (int)$id . '">' . $shop->name . '<span class="removeThis"></span></li>' . PHP_EOL;
					break;
			}
		}
	}

	private function getMenuItems($id_menu = NULL, $panel = NULL) {
		
		$sections = FlexMenuItem::getinside($id_menu);
		switch($panel) {
			case 'leftimage' :
				$value = $sections['left_image'];
				break;
			case 'leftproducts' :
				$value = $sections['left_products'];
				break;
			case 'rightproducts' :
				$value = $sections['right_products'];
				break;
			case 'rightimage' :
				$value = $sections['right_image'];
				break;
			case 'mainlinks' :
				$value = $sections['main_links'];
				break;
			case 'mainproducts' :
				$value = $sections['main_products'];
				break;
			case 'bottomlinks' :
				$value = $sections['bottom_links'];
				break;
			case 'bottomimage' :
				$value = $sections['bottom_image'];
				break;
		}
		return explode(',', $value);
	}

	private function getCMSCategories($recursive = false, $parent = 1, $id_lang = false) {
		$id_lang = $id_lang ? (int)$id_lang : (int)Context::getContext()->language->id;

		if ($recursive === false) {
			$sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
				FROM `' . _DB_PREFIX_ . 'cms_category` bcp
				INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
				ON (bcp.`id_cms_category` = cl.`id_cms_category`)
				WHERE cl.`id_lang` = ' . (int)$id_lang . '
				AND bcp.`id_parent` = ' . (int)$parent;

			return Db::getInstance()->executeS($sql);
		} else {
			$sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
				FROM `' . _DB_PREFIX_ . 'cms_category` bcp
				INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
				ON (bcp.`id_cms_category` = cl.`id_cms_category`)
				WHERE cl.`id_lang` = ' . (int)$id_lang . '
				AND bcp.`id_parent` = ' . (int)$parent;

			$results = Db::getInstance()->executeS($sql);
			foreach ($results as $result) {
				$sub_categories = $this->getCMSCategories(true, $result['id_cms_category'], (int)$id_lang);
				if ($sub_categories && count($sub_categories) > 0)
					$result['sub_categories'] = $sub_categories;
				$categories[] = $result;
			}

			return isset($categories) ? $categories : false;
		}

	}

	private function getCategoryOption($curr, $id_category = 1, $id_lang = false, $id_shop = false, $recursive = true) {
		$id_lang = $id_lang ? (int)$id_lang : (int)Context::getContext()->language->id;
		$category = new Category((int)$id_category, (int)$id_lang, (int)$id_shop);
		$lnk = new Link();

		if (is_null($category->id))
			return;

		if ($recursive) {
			$children = Category::getChildren((int)$id_category, (int)$id_lang, true, (int)$id_shop);
			$spacer = str_repeat('&nbsp;', $this->spacer_size * (int)$category->level_depth);
		}

		$shop = (object)Shop::getShop((int)$category->getShopID());
		$this->_html .= '<option '.($curr == 'CAT'.(int)$category->id ? 'selected' : '').' value="CAT' . (int)$category->id . '">' . (isset($spacer) ? $spacer : '') . $category->name . ' (' . $shop->name . ')</option>';

		if (isset($children) && count($children))
			foreach ($children as $child) {
				$this->getCategoryOption($curr, (int)$child['id_category'], (int)$id_lang, (int)$child['id_shop']);
			}
	}

	private function getCategory($id_category, $depth = 0, $id_lang = false, $id_shop = false) {
		$id_lang = $id_lang ? (int)$id_lang : (int)Context::getContext()->language->id;
		$category = new Category((int)$id_category, (int)$id_lang);

		if ($category->level_depth > 1)
			$category_link = $category->getLink();
		else
			$category_link = $this->context->link->getPageLink('index');

		if (is_null($category->id))
			return;

		$children = Category::getChildren((int)$id_category, (int)$id_lang, true, (int)$id_shop);

		$is_intersected = array_intersect($category->getGroups(), $this->user_groups);
		// filter the categories that the user is allowed to see and browse
		if (!empty($is_intersected)) {
			$this->_menu .= '<li class="'.(count($children) && ($depth == 2) ? 'hasChildren ' : '').(($this->curPath() == $category_link) ? 'current-item' : '').'">';
			$this->_menu .= '<a class="menu-item-title" href="'.$category_link.'">'.$category->name.'</a>'.(count($children) ? '<a href="#" class="opener"></a>' : '');

			if (count($children)) {
				$this->_menu .= '<ul class="main-section-sublinks dd-section level_'.$depth.'">';
				$y=0;
				foreach ($children as $child) {
					$this->getCategory((int)$child['id_category'], $depth+1, (int)$id_lang, (int)$child['id_shop']);
					$y++;
				}
				$this->_menu .= '</ul>';
			}
			$this->_menu .= '</li>';
		}
	}

	private function getCMSPages($id_cms_category, $id_shop = false, $id_lang = false) {
		$id_shop = ($id_shop !== false) ? (int)$id_shop : (int)Context::getContext()->shop->id;
		$id_lang = $id_lang ? (int)$id_lang : (int)Context::getContext()->language->id;

		$sql = 'SELECT c.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms` c
			INNER JOIN `' . _DB_PREFIX_ . 'cms_shop` cs
			ON (c.`id_cms` = cs.`id_cms`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl
			ON (c.`id_cms` = cl.`id_cms`)
			WHERE c.`id_cms_category` = ' . (int)$id_cms_category . '
			AND cs.`id_shop` = ' . (int)$id_shop . '
			AND cl.`id_lang` = ' . (int)$id_lang . '
			AND c.`active` = 1
			ORDER BY `position`';

		return Db::getInstance()->executeS($sql);
	}

	private function getCMSOptions($parent = 0, $depth = 1, $id_lang = false, $curr = "") {
		$id_lang = $id_lang ? (int)$id_lang : (int)Context::getContext()->language->id;

		$categories = $this->getCMSCategories(false, (int)$parent, (int)$id_lang);
		$pages = $this->getCMSPages((int)$parent, false, (int)$id_lang);

		$spacer = str_repeat('&nbsp;', $this->spacer_size * (int)$depth);

		foreach ($categories as $category) {
			$this->_html .= '<option '.($curr == 'CMS_CAT'.$category['id_cms_category'] ? 'selected' : '').' value="CMS_CAT'.$category['id_cms_category'].'">'.$spacer.$category['name'].'</option>';
			$this->getCMSOptions($category['id_cms_category'], (int)$depth + 1, (int)$id_lang);
		}
		foreach ($pages as $page) {
			/*echo "!".$curr." = CMS".$page['id_cms']."\n";
			if ($curr == "CMS".$page['id_cms']) {
				$sel = " selected=\"selected\""; 
				echo $curr;
			} else $sel = "";*/
			$this->_html .= '<option value="CMS'.$page['id_cms'].'">'.$spacer.$page['meta_title'].'</option>';
		}
	}
	
	private function makeMenuLinks($id, $type)
	{
		
		$id_lang = (int)$this->context->language->id;
		$id_shop = (int)Shop::getContextShopID();

			switch ($type)
			{
				case 'CAT':
					$this->getCategory((int)$id);
					break;

				case 'PRD':
					$product = new Product((int)$id, true, (int)$id_lang);
					if (!is_null($product->id))
						$this->_menu .= '<li><a href="'.$product->getLink().'">'.$product->name.'</a></li>'.PHP_EOL;
					break;

				case 'CMS':
					$cms = CMS::getLinks((int)$id_lang, array($id));
					if (count($cms))
						$this->_menu .= '<li><a href="'.$cms[0]['link'].'">'.$cms[0]['meta_title'].'</a></li>'.PHP_EOL;
					break;

				case 'CMS_CAT':
					$category = new CMSCategory((int)$id, (int)$id_lang);
					if (count($category))
					{
						$this->_menu .= '<li><a href="'.$category->getLink().'">'.$category->name.'</a>';
						$this->getCMSCategories(false, $category->id, (int)$id_lang);
						$this->_menu .= '</li>'.PHP_EOL;
					}
					break;

				case 'MAN':
					$selected = ($this->page_name == 'manufacturer' && (Tools::getValue('id_manufacturer') == $id)) ? ' class="sfHover"' : '';
					$manufacturer = new Manufacturer((int)$id, (int)$id_lang);
					if (!is_null($manufacturer->id))
					{
						if (intval(Configuration::get('PS_REWRITING_SETTINGS')))
							$manufacturer->link_rewrite = Tools::link_rewrite($manufacturer->name);
						else
							$manufacturer->link_rewrite = 0;
						$link = new Link;
						$this->_menu .= '<li'.$selected.' class="menu-manufacturer"><a href="'.$link->getManufacturerLink((int)$id, $manufacturer->link_rewrite).'"><img src="'.Tools::getShopProtocol().Context::getContext()->shop->domain.Context::getContext()->shop->physical_uri.'img/m/'.$manufacturer->id.'-manu_alysum.jpg" alt="'.htmlspecialchars($manufacturer->name).'" title="'.htmlspecialchars($manufacturer->name).'" width="168" height="100" /></a></li>'.PHP_EOL;
					}
					break;

				case 'SUP':
					$selected = ($this->page_name == 'supplier' && (Tools::getValue('id_supplier') == $id)) ? ' class="sfHover"' : '';
					$supplier = new Supplier((int)$id, (int)$id_lang);
					if (!is_null($supplier->id))
					{
						$link = new Link;
						$this->_menu .= '<li'.$selected.'><a href="'.$link->getSupplierLink((int)$id, $supplier->link_rewrite).'">'.$supplier->name.'</a></li>'.PHP_EOL;
					}
					break;

				case 'SHOP':
					$selected = ($this->page_name == 'index' && ($this->context->shop->id == $id)) ? ' class="sfHover"' : '';
					$shop = new Shop((int)$id);
					if (Validate::isLoadedObject($shop))
					{
						$link = new Link;
						$this->_menu .= '<li'.$selected.'><a href="'.$shop->getBaseURL().'">'.$shop->name.'</a></li>'.PHP_EOL;
					}
					break;

				case 'BLG':
					$dispatcher = Dispatcher::getInstance();
					$params = array();
					$links = FlexMenuItem::getBlogLink($id_lang, (int)$id);						
					$url = $this->myRealUrl();
					if ($id == 0) {
						$this->_menu .= '<li><a href="'.$url.$dispatcher->createUrl('module-ph_simpleblog-list', $id_lang, $params).'">'.$link['name'].'</a></li>'.PHP_EOL;
					} else {
						if ($links != false) {
							foreach ($links as $link) {
								$params['sb_category'] = $link['link_rewrite'];
								$this->_menu .= '<li><a href="'.$url.$dispatcher->createUrl('module-ph_simpleblog-category', $id_lang, $params).'">'.$link['name'].'</a></li>'.PHP_EOL;
							}
						}						
					}					
					break;

				case 'TST':
					$url = Context::getContext()->link->getModuleLink('pk_testimonials', 'testimonials');
					$this->_menu .= '<li><a href="'.$url.'">'.$this->l('Testimonials').'</a></li>';		
					break;	

				case 'PSL':	
					if ($id == 0)
						$this->_menu .= '<li class="home-link"><a href="'.Context::getContext()->link->getPageLink('index').'">'.$this->l('Home').'</a></li>';
					if ($id == 1)
						$this->_menu .= '<li><a href="'.Context::getContext()->link->getPageLink('prices-drop').'">'.$this->l('Specials').'</a></li>';
					if ($id == 2)
						$this->_menu .= '<li><a href="'.Context::getContext()->link->getPageLink('new-products').'">'.$this->l('New products').'</a></li>';
					if ($id == 3)
						$this->_menu .= '<li><a href="'.Context::getContext()->link->getPageLink('best-sales').'">'.$this->l('Best sellers').'</a></li>';
					if ($id == 4)
						$this->_menu .= '<li><a href="'.Context::getContext()->link->getPageLink('stores').'">'.$this->l('Our stores').'</a></li>';
					if ($id == 5)
						$this->_menu .= '<li><a href="'.Context::getContext()->link->getPageLink('contact').'">'.$this->l('Contact us').'</a></li>';
					if ($id == 6)
						$this->_menu .= '<li><a href="'.Context::getContext()->link->getPageLink('sitemap').'">'.$this->l('Sitemap').'</a></li>';		
					break;

				case 'LNK':
					$link = FlexMenuItem::getCustomLink((int)$id, (int)$id_lang, (int)$id_shop);
					if (count($link))
					{
						if (!isset($link[0]['label']) || ($link[0]['label'] == ''))
						{
							$default_language = Configuration::get('PS_LANG_DEFAULT');
							$link = FlexMenuItem::getCustomLink($link[0]['id_pk_flexmenu_links'], $default_language, (int)Shop::getContextShopID());
						}
						$this->_menu .= '<li><a href="'.$link[0]['link'].'"'.(($link[0]['new_window']) ? ' target="_blank"': '').'>'.$link[0]['label'].'</a></li>'.PHP_EOL;
					}
					break;
			
		}
	}

	private function getItemParams($code)
	{
		preg_match($this->pattern, $code, $value);
		$id = (int)substr($code, strlen($value[1]), strlen($code));
		$type = substr($code, 0, strlen($value[1]));
		$id_lang = (int)$this->context->language->id;
		$id_shop = (int)Shop::getContextShopID();
		$blog_urlrewr = Configuration::get('pk_blockblogurlrewrite_on');
		switch ($type)
		{
			case 'CAT':
				$category = new Category($id, $id_lang);
				if (is_null($category->id)) 
					return;
				$data["name"] = $category->name;
				$data["link"] = $category->getLink();					
				return $data;
				break;

			case 'PRD':
				$product = new Product((int)$id, true, (int)$id_lang);
				if (is_null($product->id))
					return;
				$data["name"] = $product->name;
				$data["link"] = $product->getLink();					
				return $data;
				break;

			case 'CMS':
				$cms = CMS::getLinks((int)$id_lang, array($id));
				if (!count($cms))
					return;
				$data["name"] = $cms[0]['meta_title'];
				$data["link"] = $cms[0]['link'];					
				return $data;
				break;
			case 'CMS_CAT':
				$category = new CMSCategory((int)$id, (int)$id_lang);
				if (!count($category))
					return;
				$data["name"] = $category->name;
				$data["link"] = $category->getLink();					
				return $data;
				break;
			case 'MAN':
				$manufacturer = new Manufacturer((int)$id, (int)$id_lang);
				if (is_null($manufacturer->id))
					return;
				if (intval(Configuration::get('PS_REWRITING_SETTINGS')))
					$manufacturer->link_rewrite = Tools::link_rewrite($manufacturer->name, false);
				else
					$manufacturer->link_rewrite = 0;
				$link = new Link;
				$data["name"] = $manufacturer->name;
				$data["link"] = $link->getManufacturerLink((int)$id, $manufacturer->link_rewrite);					
				return $data;					
				break;
			case 'SUP':
				$supplier = new Supplier((int)$id, (int)$id_lang);
				if (is_null($supplier->id))
					return;
				$link = new Link;
				$data["name"] = $supplier->name;
				$data["link"] = $link->getSupplierLink((int)$id, $supplier->link_rewrite);					
				return $data;
				break;
			case 'SHOP':
				$shop = new Shop((int)$id);
				if (!Validate::isLoadedObject($shop))
					return;
				$data["name"] = $shop->name;
				$data["link"] = $shop->getBaseURL();					
				return $data;
				break;
			case 'BLG':
				$dispatcher = Dispatcher::getInstance();				
        		$url = $this->myRealUrl();        		
				if ($id == 0) {										
	        		$data["name"] = $this->l('Blog');
					$data["link"] = $url.$dispatcher->createUrl('module-ph_simpleblog-list', $id_lang);
				} else {
					$params = array();
        			$params['sb_category'] = $link[0]['link_rewrite'];
					$link = FlexMenuItem::getBlogLink($id_lang, $id);
					if ($link == false) 
						return false;
					$data["name"] = $link[0]['name'];					
					$data["link"] = $url.$dispatcher->createUrl('module-ph_simpleblog-category', $id_lang, $params);
				}
				return $data;
				break;
			case 'TST':				
				$data["link"] = Context::getContext()->link->getModuleLink('pk_testimonials', 'testimonials');
				$data["name"] = $this->l('Testimonials');
				return $data;
				break;
			case 'PSL':		
				switch ($id) {
					case 0:		
						$data["link"] = Context::getContext()->link->getPageLink('index');
						$data["name"] = $this->l('Home');
						$data["home"] = true;
						return $data;
						break;
					case 1:		
						$data["link"] = Context::getContext()->link->getPageLink('prices-drop');
						$data["name"] = $this->l('Specials');
						return $data;
						break;
					case 2:		
						$data["link"] = Context::getContext()->link->getPageLink('new-products');
						$data["name"] = $this->l('New products');
						return $data;
						break;
					case 3:		
						$data["link"] = Context::getContext()->link->getPageLink('best-sales');
						$data["name"] = $this->l('Best sellers');
						return $data;
						break;
					case 4:		
						$data["link"] = Context::getContext()->link->getPageLink('stores');
						$data["name"] = $this->l('Our stores');
						return $data;
						break;
					case 5:		
						$data["link"] = Context::getContext()->link->getPageLink('contact');
						$data["name"] = $this->l('Contact us');
						return $data;
						break;
					case 6:		
						$data["link"] = Context::getContext()->link->getPageLink('sitemap');
						$data["name"] = $this->l('Sitemap');
						return $data;
						break;
				}
				return $data;
				break;
			case 'LNK':
				$link = FlexMenuItem::getCustomLink((int)$id, (int)$id_lang, (int)$id_shop);
				if (!count($link))
					return;				
				foreach ($link as $key => $value) {
					if ($value["id_lang"] == $id_lang) {
						$data["name"] = $value['label'];
						$data["link"] = $value['link'];					
						$data["new_window"] = $value['new_window'];
					}
				}	
				return $data;
				break;
		}
	}	
	
	private function getProductImage($id)
	{
		$id_lang = (int)$this->context->language->id;
		$id_shop = (int)Shop::getContextShopID();
		
		$product = new Product((int)$id, true, (int)$id_lang);
		$cover = Product::getCover((int)$id);
		$reduction=0;

		if(isset($product->specificPrice['reduction']))
		$reduction=$product->specificPrice['reduction'];

		$nm = substr($product->name, 0, 14);
		if (strlen($nm) != strlen($product->name)) $nm = $nm."...";				

		$priceDisplay = Product::getTaxCalculationMethod();
		$pscatmode = (bool)Configuration::get('PS_CATALOG_MODE') || !(bool)Group::getCurrent()->show_prices;
		if (!is_null($product->id))
		$this->_menu .= '<li><div class="li-indent"><a class="product-image-link" href="'.htmlentities($product->getLink()).'"><img src="'.$this->context->link->getImageLink($product->link_rewrite, (int)$id.'-'.$cover['id_image'], 'home_'.$this->imageSuffix).'" alt="'.$nm.'" /></a>';
		if ($product->show_price && !(bool)$pscatmode){
		$this->_menu .= '<a href="'.htmlentities($product->getLink()).'" class="menu-product-name">'.$nm.'</a><span class="price-container'.(($reduction) ? " reduction" : "").'"><span class="price">'.( !$priceDisplay ? Tools::displayPrice(Tools::ps_round($product->price*(0.01*$product->tax_rate)+$product->price, 2)) : Tools::displayPrice(Tools::ps_round($product->price, 2))).'</span>'.(($reduction) ? ' <span class="old_price">'.Tools::displayPrice($product->getPriceWithoutReduct(false)).'</span>' : '').'</span>'.PHP_EOL;}
		$this->_menu .= '</div></li>'.PHP_EOL;
	}	

	public function getProductCover($id)	{

		$id_lang = (int)$this->context->language->id;
		$product = new Product((int)$id, true, (int)$id_lang);
		$cover = Product::getCover((int)$id);
		$link = $this->context->link->getImageLink($product->link_rewrite, (int)$id.'-'.$cover['id_image'], 'home_'.$this->imageSuffix);
		return $link;

	}		
	
	private function flexMenu()
	{	
	
		$id_lang = (int)$this->context->language->id;
		$id_shop = (int)Shop::getContextShopID();
		
		$data = FlexMenuItem::getsfm($id_lang, $id_shop);	

		foreach ($data as $link) {			

			$submenu = false;

			if (($link['state_left'] == 1) || 
				($link['state_right'] == 1) || 
				($link['state_bottom'] == 1) ||
				($link['state_main'] == 1)) $submenu = true;					
			
			$itemData = $this->getItemParams($link['label']);			

			$this->_menu .= '<li class="flexmenuitem'.((isset($itemData['home']) && $itemData['home'] == true)  ? ' home' : '').' id_menu'.$link['id_pk_flexmenu_menus'].(($submenu) ? ' hasChildren' : '').(($link['narrow'] == 1)  ? ' narrow' : '').(($this->curPath() == $itemData['link']) ? ' current-item' : '').'"><a href="'.$itemData['link'].'" '.(((!empty($itemData['new_window'])) && ($itemData['new_window'] == 1)) ? ' target="_blank"' : '').'><span>'.$itemData['name'].'</span></a>'.(($submenu) ? '<a href="#" class="opener"></a>' : '').'';		
						
			if ($submenu) {
				
			$this->_menu .='<div class="submenu dd-section fmsid'.$link['id_pk_flexmenu_menus'].' clearfix" '.(($link['main_image'] != "")  ? ' style="background-image:url('.$this->context->shop->getBaseURL().'modules/pk_flexmenu/uploads/'.$link['main_image'].')"' : '').'><div class="menu_width">';

				if ($link['state_left'] == 1) {

					$this->_menu .='<div class="left-section"><div class="section-indent">';
	
					switch ($link['left'])	{								
						case 'PRD':				
							$ids = explode(",", $link['left_products']);
							$this->_menu .='<div class="left-title menu-product-title"><span>'.$link['left_products_title'].'</span></div>';
							$this->_menu .='<ul class="left-section-products menu-product">';
							foreach ($ids as $id){	
								$id = str_replace("PRD", "", $id);
								$this->getProductImage($id);
							}
							$this->_menu .='</ul>';
						break;
						case 'IMAGE':
						if (isset($link['left_image']) && !empty($link['left_image']))
							$this->_menu .='<a href="'.$link['left_image_link'].'" class="imagelink"><img src="'.$this->_path.'uploads/'.$link['left_image'].'" class="leftimage menu-image" alt="" /></a>';
						break;				
						case 'VID':
						if (isset($link['left_video']) && !empty($link['left_video']))
							$this->_menu .='<div class="videoWrapper"><iframe width="184" height="120" src="'.$link['left_video'].'" allowfullscreen></iframe></div>';
						break;				
					}

					$this->_menu .='</div></div>';
				}

				$this->_menu .='<div class="sections-contaier'.(($link['state_left'] == 0) ? ' full-width' : '').'">';

				if ($link['state_main'] == 1) {

					$column = " full-width";
					if ( ($link['state_left'] == 1) AND ($link['state_right'] == 1) ) {
						$column = " two-columns";
					} elseif ($link['state_left'] == 1) {
						$column = " only-left";
					} elseif ($link['state_right'] == 1) {
						$column = " only-right";
					}

					$this->_menu .='<div class="main-section'.$column.'"><div class="section-indent">';
					switch ($link['main']) {
						case 'PRD':
							$this->_menu .='<ul class="main-section-products menu-product">';
							$ids = explode(",", $link['main_products']);
							$i=0;
							foreach ($ids as $id){	
								$id = str_replace("PRD", "", $id);
								$this->getProductImage($id);
								$i++;
							}
							$this->_menu .='</ul>';
							break;		
						case 'LINKS':
							$abbrs = explode(",", $link['main_links']);
							$this->_menu .='<ul class="main-section-links">';
							foreach ($abbrs as $abbr){										
								if (!$abbr) continue;				
								preg_match($this->pattern, $abbr, $value);
								$id = (int)substr($abbr, strlen($value[1]), strlen($abbr));
								$type = substr($abbr, 0, strlen($value[1]));
								$this->makeMenuLinks($id, $type);		
							}
							$this->_menu .='</ul>';
							break;
						case 'CMS_P':
							if ($link['main_cmsp']) {
								$this->_menu .='<div class="main-section-cmsp">';
								preg_match($this->pattern, $link['main_cmsp'], $value);
								$id = (int)substr($link['main_cmsp'], strlen($value[1]), strlen($link['main_cmsp']));
								$cms = new CMS($id, $id_lang);
								$this->_menu .= $cms->content;							
								$this->_menu .='</div>';
							}
							break;
						case 'VID':
							if (isset($link['main_video']) && !empty($link['main_video']))
							$this->_menu .='<div class="videoWrapper"><iframe width="652" height="398" src="'.$link['main_video'].'" allowfullscreen></iframe></div>';
							break;
					}

					$this->_menu .='</div></div>';
				}
			
				if ($link['state_right'] == 1) {

					$this->_menu .='<div class="right-section"><div class="section-indent">';

					switch ($link['right']) {				
						case 'PRD':
							$this->_menu .='<div class="right-title menu-product-title"><span>'.$link['right_products_title'].'</span></div>';
							$this->_menu .='<ul class="right-section-products menu-product">';
							$id = str_replace("PRD", "", $link['right_products']);
							$this->getProductImage($id);
							$this->_menu .='</ul>';
							break;				
						case 'IMAGE':
							if (isset($link['right_image']) && !empty($link['right_image']))
							$this->_menu .='<a href="'.$link['right_image_link'].'" class="imagelink"><img src="'.$this->_path.'uploads/'.$link['right_image'].'" class="rightimage menu-image" alt="" /></a>';
							break;
						case 'VID':
							if (isset($link['right_video']) && !empty($link['right_video']))
							$this->_menu .='<div class="videoWrapper"><iframe width="184" height="120" src="'.$link['right_video'].'" allowfullscreen></iframe></div>';
							break;			
					}

					$this->_menu .='</div></div>';

				}

				if ($link['state_bottom'] == 1) {

					$this->_menu .='<div class="bottom-section"><div class="section-indent">';

					switch ($link['bottom']) {
						case 'IMAGE':
						if (isset($link['bottom_image']) && !empty($link['bottom_image']))
							$this->_menu .='<a href="'.$link['bottom_image_link'].'" class="imagelink"><img src="'.$this->_path . 'uploads/'.$link['bottom_image'].'" class="bottomimage menu-image" alt="" /></a>';
						break;
						case 'LINKS':
							$abbrs = explode(",", $link['bottom_links']);
							$this->_menu .='<div class="bottom-title"><span>'.$link['bottom_title'].'</span></div>';
							$this->_menu .='<ul class="bottom-section-links clearfix">';
							foreach ($abbrs as $abbr){										
								if (!$abbr) continue;				
								preg_match($this->pattern, $abbr, $value);
								$id = (int)substr($abbr, strlen($value[1]), strlen($abbr));
								$type = substr($abbr, 0, strlen($value[1]));
								$this->makeMenuLinks($id, $type);		
							}
							$this->_menu .='</ul>';
						break;
						case 'VID':
							if (isset($link['bottom_video']) && !empty($link['bottom_video']))
							$this->_menu .='<div class="videoWrapper"><iframe width="886" height="500" src="'.$link['bottom_video'].'" allowfullscreen></iframe></div>';
							break;			
					}

					$this->_menu .='</div></div>';
				}

				$this->_menu .='</div></div></div>';				
			}
			$this->_menu .= '</li>'.PHP_EOL;
		}	
	}

	public function curPath() {
		return Tools::getShopProtocol().$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}

	public function hookDisplayHeader($params) {
		$this->context->controller->addCss($this->_path.'css/pk_flexmenu.css');
		$this->context->controller->addCss($this->_path.'css/pk_flexmenu_responsive.css');
		$this->context->controller->addJS($this->_path.'js/pk_flexmenu.js');
	}

	protected function getCacheId($name = null)
	{
		return parent::getCacheId($name.'|'.date('Ymd'));
	}

	public function hookMenu($params) {
				
		$this->user_groups = ($this->context->customer->isLogged() ? $this->context->customer->getGroups() : array(Configuration::get('PS_UNIDENTIFIED_GROUP')));
		if (!$this->isCached($this->name.'.tpl', $this->getCacheId($this->name))) {
			$this->flexMenu();
			$this->smarty->assign('flexmenu', $this->_menu);
			$this->smarty->assign('this_path', $this->_path);
		}
		//return $this->display(__FILE__, $this->name.'.tpl', parent::getCacheId($this->name.'|'.date('Ymd')));
		return $this->display(__FILE__, $this->name.'.tpl', $this->getCacheId($this->name));
		//return $this->display(__FILE__, 'pk_flexmenu.tpl');
	}
	public static function myRealURL()
    {
        $force_ssl = null;
        $allow = (int)Configuration::get('PS_REWRITING_SETTINGS');
        $ssl_enable = Configuration::get('PS_SSL_ENABLED');
        $context = Context::getContext();
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;

        if (!defined('_PS_BASE_URL_'))
            define('_PS_BASE_URL_', Tools::getShopDomain(true));
        if (!defined('_PS_BASE_URL_SSL_'))
            define('_PS_BASE_URL_SSL_', Tools::getShopDomainSsl(true));

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null)
            $shop = new Shop($id_shop);
        else
            $shop = Context::getContext()->shop;

        if (isset($ssl) && $ssl === null)
        {
            if ($force_ssl === null)
                $force_ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            $ssl = $force_ssl;
        }

        $base = ((isset($ssl) && $ssl && $this->ssl_enable) ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain);

        $langUrl = Language::getIsoById($id_lang).'/';

        if ((!$allow && in_array($id_shop, array($context->shop->id,  null))) || !Language::isMultiLanguageActivated($id_shop) || !(int)Configuration::get('PS_REWRITING_SETTINGS', null, null, $id_shop))
            $langUrl = '';

        return $base.$shop->getBaseURI().$langUrl;
    }
    public function installQuickAccess(){

      $qick_access = new QuickAccess();
      $qick_access->link = 'index.php?controller=AdminModules&configure='.$this->name.'&tab_module=front_office_features&module_name='.$this->name;
      $qick_access->new_window = false;

      $languages = Language::getLanguages(false);
      foreach ($languages as $language)
          $qick_access->name[$language['id_lang']]= 'Alysum Menu';

      $qick_access->add();  
      if(!$qick_access->id)
            return FALSE;
        
      return true;
    }

    public function getMenuToken() {
		
		$emp_id = isset($this->context->employee->id) ? $this->context->employee->id : 1;

    	$params = array(
    		'tabName' => 'AdminModules',
    		'id'	=> (int)Tab::getIdFromClassName('AdminModules'),
    		'employee' => $emp_id
    		);
    	$params['token'] = Tools::getAdminToken($params['tabName'].$params['id'].$params['employee']);
    	return $params;
    }
}