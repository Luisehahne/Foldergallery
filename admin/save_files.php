<?php
require('../../../config.php');
if(defined('WB_PATH') == false) { exit("Cannot access this file directly");  }
require(WB_PATH.'/modules/admin.php');
	
// check if backend.css file needs to be included into <body></body>
if(!method_exists($admin, 'register_backend_modfiles') && file_exists(WB_PATH ."/modules/foldergallery/backend.css")) {
echo '<style type="text/css">';
include(WB_PATH .'/modules/foldergallery/backend.css');
echo "\n</style>\n";
}
// check if backend.js file needs to be included into <body></body>
if(!method_exists($admin, 'register_backend_modfiles') && file_exists(WB_PATH ."/modules/foldergaller/backend.js")) {
echo '<script type="text/javascript">';
include(WB_PATH .'/modules/foldergallery/backend.js');
echo "</script>";
}

// include the default language
require_once(WB_PATH .'/modules/foldergallery/languages/EN.php');
// check if module language file exists for the language set by the user (e.g. DE, EN)
if(file_exists(WB_PATH .'/modules/foldergallery/languages/'.LANGUAGE .'.php')) {
    require_once(WB_PATH .'/modules/foldergallery/languages/'.LANGUAGE .'.php');
}

// Files includen
require_once (WB_PATH.'/modules/foldergallery/info.php');
require_once (WB_PATH.'/modules/foldergallery/admin/scripts/backend.functions.php');



if(!isset($_POST['save']) && !is_string($_POST['save'])) {
	echo "Falsche Formulardaten!";
} else {


	// Vorhandene POST Daten auswerten
	if(isset($_POST['cat_id']) && is_numeric($_POST['cat_id'])) {
		$cat_id = $_POST['cat_id'];
	} else {
		$error['no_cat_id'] = 1;
		$admin->print_error('lost cat', ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&section_id='.$section_id);
		die();
	}

        
	$bilderNeu = array();
        foreach($_POST['caption'] as $key => $value) {
            if(!is_numeric($key)) {
                continue;
            }
            if(is_string($value) && $value != '') {
                $caption = htmlentities($value, ENT_QUOTES, "UTF-8");
            } else {
                $caption = '';
            }
            $bilderNeu[] = array(
                'id'        => $key,
                'caption'   => $caption,
                'delete'    => false
            );
        }
		
	// Jetzt machen wir alle Datenbank Änderungen
	$deleteSQL = 'SELECT * FROM '.TABLE_PREFIX.'mod_foldergallery_files WHERE ';
	$selectSQL = 'SELECT id, caption FROM '.TABLE_PREFIX.'mod_foldergallery_files WHERE ';
	$updateSQL = 'UPDATE '.TABLE_PREFIX.'mod_foldergallery_files SET ';
	foreach($bilderNeu as $bild){
		$selectArray[] = $bild['id'];
	}
	if(isset($selectArray)){
            $selectSQL .= '(id IN('.implode(',',$selectArray).'));';
            $query = $database->query($selectSQL);
            while($singleResult = $query->fetchRow(MYSQL_ASSOC)){
                foreach($bilderNeu as $bild) {
                    if($bild['id'] == $singleResult['id']) {
                        if($bild['caption'] != $singleResult['caption']){
                            $updateArray[] = array(
                                'id' => $bild['id'],
                                'caption' => $bild['caption']
                            );
			}
                    }
		}
            }
	}
	if(isset($updateArray)) {
		$anzahlUpdates = count($updateArray);
		for($i = 0; $i < $anzahlUpdates; $i++) {
			$updateSQLNew = $updateSQL." caption='".$updateArray[$i]['caption']."' WHERE id=".$updateArray[$i]['id'].";";
			$database->query($updateSQLNew);
		}
	}
	
}
$admin->print_success($TEXT['SUCCESS'], WB_URL.'/modules/foldergallery/admin/modify_cat.php?page_id='.$page_id.'&section_id='.$section_id.'&cat_id='.$cat_id);

$admin->print_footer();
?>