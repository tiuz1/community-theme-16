<?php

include(dirname(__FILE__).'/../../../config/config.inc.php');
include(dirname(__FILE__).'/../../../init.php');
include(dirname(__FILE__).'/../pk_flexmenu.php');

$menu = new pk_flexmenu();
$token = $menu->getMenuToken();

ini_set('post_max_size', '1M'); //or bigger by multiple files
ini_set('upload_max_filesize', '1M');
ini_set('max_file_uploads', 1);

if ($token['token'] == $_POST['token']) {

	$types = array('image/jpeg', 'image/gif', 'image/png', 'image/jpg');

	foreach ($_FILES["images"]["error"] as $key => $error) {
	    if ($error == UPLOAD_ERR_OK) {
	    	if (in_array($_FILES['images']['type'][0], $types)) {
				$name = $_FILES["images"]["name"][$key];
				$t_name = $_FILES["images"]["tmp_name"][$key];
				$path = "../uploads/" . $_FILES['images']['name'][$key];
				move_uploaded_file( $t_name, $path );
			}
	    }
	}
	
}