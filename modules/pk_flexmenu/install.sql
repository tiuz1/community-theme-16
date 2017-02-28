CREATE TABLE IF NOT EXISTS `PREFIX_pk_flexmenu_menus` (
`id_pk_flexmenu_menus` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
`id_shop` INT UNSIGNED NOT NULL,
`position` INT(10) UNSIGNED NOT NULL,
`active` TINYINT( 1 ) NOT NULL,
`narrow` TINYINT( 1 ) NOT NULL,
`main_image` VARCHAR(255) NOT NULL,
INDEX (`id_shop`)
) ENGINE = ENGINE_TYPE CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE IF NOT EXISTS `PREFIX_pk_flexmenu_submenus` (
`id_menu` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`left` VARCHAR(255) NOT NULL DEFAULT '',
`left_products` VARCHAR(255) NOT NULL DEFAULT '',
`left_products_title` VARCHAR(255) NOT NULL DEFAULT '',
`left_image` VARCHAR(255) NOT NULL DEFAULT '',
`left_video` VARCHAR(255) NOT NULL DEFAULT '',
`left_image_link` VARCHAR(255) NOT NULL DEFAULT '',
`left_image_text` VARCHAR(255) NOT NULL DEFAULT '',
`state_left` INT(2) NOT NULL DEFAULT 0,
`main` VARCHAR(255) NOT NULL DEFAULT '',
`main_products` VARCHAR(255) NOT NULL DEFAULT '',
`main_links` VARCHAR(255) NOT NULL DEFAULT '',
`main_video` VARCHAR(255) NOT NULL DEFAULT '',
`main_cmsp` VARCHAR(255) NOT NULL DEFAULT '',
`state_main` INT(2) NOT NULL DEFAULT 0,
`right` VARCHAR(255) NOT NULL DEFAULT '',
`right_products` VARCHAR(255) NOT NULL DEFAULT '',
`right_products_title` VARCHAR(255) NOT NULL DEFAULT '',
`right_image` VARCHAR(255) NOT NULL DEFAULT '',
`right_video` VARCHAR(255) NOT NULL DEFAULT '',
`right_image_link` VARCHAR(255) NOT NULL DEFAULT '',
`right_image_text` VARCHAR(255) NOT NULL DEFAULT '',
`state_right` INT(2) NOT NULL DEFAULT 0,
`bottom_title` VARCHAR(255) NOT NULL DEFAULT '',
`bottom` VARCHAR(255) NOT NULL DEFAULT '',
`bottom_image` VARCHAR(255) NOT NULL DEFAULT '',
`bottom_video` VARCHAR(255) NOT NULL DEFAULT '',
`bottom_image_link` VARCHAR(255) NOT NULL DEFAULT '',
`bottom_image_text` VARCHAR(255) NOT NULL DEFAULT '',
`bottom_links` VARCHAR(255) NOT NULL DEFAULT '',
`state_bottom` INT(2) NOT NULL DEFAULT 0,
INDEX ( `id_menu`)
) ENGINE = ENGINE_TYPE CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE IF NOT EXISTS `PREFIX_pk_flexmenu_menus_lang` (
`id_pk_flexmenu_menus` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
`id_lang` INT NOT NULL,
`id_shop` INT NOT NULL,
`label` VARCHAR( 128 ) NOT NULL,
INDEX ( `id_pk_flexmenu_menus`, `id_shop`)
) ENGINE = ENGINE_TYPE CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE IF NOT EXISTS `PREFIX_pk_flexmenu_links` (
`id_pk_flexmenu_links` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
`id_shop` INT UNSIGNED NOT NULL,
`new_window` TINYINT( 1 ) NOT NULL,
INDEX (`id_shop`)
) ENGINE = ENGINE_TYPE CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE IF NOT EXISTS `PREFIX_pk_flexmenu_links_lang` (
`id_pk_flexmenu_links` INT NOT NULL,
`id_lang` INT NOT NULL,
`id_shop` INT NOT NULL,
`label` VARCHAR( 128 ) NOT NULL ,
`link` VARCHAR( 128 ) NOT NULL ,
INDEX ( `id_pk_flexmenu_links` , `id_lang`, `id_shop`)
) ENGINE = ENGINE_TYPE CHARACTER SET utf8 COLLATE utf8_general_ci;
INSERT INTO `PREFIX_pk_flexmenu_menus` VALUES(1, 1, 1, 1, 0, '');
INSERT INTO `PREFIX_pk_flexmenu_menus_lang` VALUES(1, 1, 1, 'CAT2');
INSERT INTO `PREFIX_pk_flexmenu_submenus` VALUES(1, 'IMAGE', '', '', '', '', '', '', 0, 'LINKS', '', 'CMS1,CMS2,CMS4,CMS5', '', '', 1, 'IMAGE', '', '', '', '', '', '', 0, '', 'LINKS', '', '', '', '', '', 0);