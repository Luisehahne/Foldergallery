<?php

require('../../../config.php');
if (defined('WB_PATH') == false) {
    exit("Cannot access this file directly");
}
require(WB_PATH . '/modules/admin.php');

// check if backend.css file needs to be included into <body></body>
if (!method_exists($admin, 'register_backend_modfiles') && file_exists(WB_PATH . "/modules/foldergallery/backend.css")) {
    echo '<style type="text/css">';
    include(WB_PATH . '/modules/foldergallery/backend.css');
    echo "\n</style>\n";
}
// check if backend.js file needs to be included into <body></body>
if (!method_exists($admin, 'register_backend_modfiles') && file_exists(WB_PATH . "/modules/foldergaller/backend.js")) {
    echo '<script type="text/javascript">';
    include(WB_PATH . '/modules/foldergallery/backend.js');
    echo "</script>";
}

// include the default language
require_once(WB_PATH . '/modules/foldergallery/languages/EN.php');
// check if module language file exists for the language set by the user (e.g. DE, EN)
if (file_exists(WB_PATH . '/modules/foldergallery/languages/' . LANGUAGE . '.php')) {
    require_once(WB_PATH . '/modules/foldergallery/languages/' . LANGUAGE . '.php');
}

// Files includen
require_once (WB_PATH . '/modules/foldergallery/info.php');
require_once (WB_PATH . '/modules/foldergallery/admin/scripts/backend.functions.php');
require_once (WB_PATH . '/modules/foldergallery/class/class.upload.php');
require_once (WB_PATH . '/modules/foldergallery/class/validator.php');
require_once (WB_PATH . '/modules/foldergallery/class/DirectoryHandler.Class.php');

//  Set the mySQL encoding to utf8
$oldMysqlEncoding = mysql_client_encoding();
mysql_set_charset('utf8', $database->db_handle);

$settings = getSettings($section_id);
$root_dir = $settings['root_dir']; //Chio


if (isset($_GET['cat_id']) && is_numeric($_GET['cat_id'])) {
    $cat_id = $_GET['cat_id'];
} else {
    $error['no_cat_id'] = 1;
    $admin->print_error('lost cat', ADMIN_URL . '/pages/modify.php?page_id=' . $page_id . '&section_id=' . $section_id);
    die();
}

// Kategorie Infos aus der DB holen
$sql = 'SELECT * FROM ' . TABLE_PREFIX . 'mod_foldergallery_categories WHERE id=' . $cat_id . ' LIMIT 1;';
$query = $database->query($sql);
$categorie = $query->fetchRow();

if (is_array($categorie)) {
    if ($categorie['parent'] != -1) {
        $cat_path = $path . $settings['root_dir'] . $categorie['parent'] . '/' . $categorie['categorie'];
        $cat_path = str_replace(WB_PATH, '', $cat_path);
        $parent = $categorie['parent'] . '/' . $categorie['categorie'];
        $uploadPath = $settings['root_dir'] . $categorie['parent'] . '/' . $categorie['categorie'];
    } else {
        // Root
        $cat_path = $path . $settings['root_dir'];
        $parent = '';
        $uploadPath = $settings['root_dir'];
    }
}
$parent_id = $categorie['id'];
if ($categorie['active'] == 1) {
    $cat_active_checked = 'checked="checked"';
} else {
    $cat_active_checked = '';
}


$folder = $root_dir . $parent;
$pathToFolder = $path . $folder . '/';
$pathToThumb = $path . $folder . $thumbdir . '/';
$urlToFolder = $url . $folder . '/';
$urlToThumb = $url . $folder . $thumbdir . '/';

$bilder = array();
$sql = 'SELECT * FROM ' . TABLE_PREFIX . 'mod_foldergallery_files WHERE parent_id="' . $parent_id . '" ORDER BY position ASC;';
$query = $database->query($sql);
if ($query->numRows()) {
    while ($result = $query->fetchRow()) {
        // Falls es das Vorschaubild noch nicht gibt:
        //Chio Start
        $bildfilename = $result['file_name'];
        $file = $pathToFolder . $bildfilename;
        if (!is_file(DirectoryHandler::DecodePath($file))) {
            $deletesql = 'DELETE FROM ' . TABLE_PREFIX . 'mod_foldergallery_files WHERE id=' . $result['id'];
            $database->query($deletesql);
            continue;
        }


        $file = $pathToFolder . $bildfilename;
        $thumb = $pathToThumb . $bildfilename;
        if (!is_file(DirectoryHandler::DecodePath($thumb))) {
            FG_createThumb($file, $bildfilename, $pathToThumb, $settings['tbSettings']);
        }

        //Chio Ende
        $bilder[] = array(
            'id' => $result['id'],
            'file_name' => $bildfilename, //Chio
            'caption' => $result['caption'], //Chio
            'thumb_link' => $urlToThumb . $bildfilename
        );
    }
} else {
    // Diese Kategorie enthält noch keine Bilder
    $error['noimages'] = 1;
}


//Template
$t = new Template(dirname(__FILE__) . '/templates', 'remove');
$t->set_file('modify_cat', 'modify_cat.htt');
// clear the comment-block, if present
$t->set_block('modify_cat', 'CommentDoc');
$t->clear_var('CommentDoc');
// set other blocks
$t->set_block('modify_cat', 'file_loop', 'FILE_LOOP');

// Textvariablen parsen
$t->set_var(array(
    'MODIFY_CAT_TITLE' => $MOD_FOLDERGALLERY['MODIFY_CAT_TITLE'],
    'MODIFY_CAT_STRING' => $MOD_FOLDERGALLERY['MODIFY_CAT'],
    'FOLDER_IN_FS_STRING' => $MOD_FOLDERGALLERY['FOLDER_IN_FS'],
    'FOLDER_IN_FS_VALUE' => $cat_path,
    'CAT_ACTIVE_CHECKED' => $cat_active_checked,
    'CAT_ACTIVE_STRING' => $MOD_FOLDERGALLERY['ACTIVE'],
    'CAT_NAME_STRING' => $MOD_FOLDERGALLERY['CAT_NAME'],
    'CAT_NAME_VALUE' => $categorie['cat_name'],
    'CAT_DESCRIPTION_STRING' => $MOD_FOLDERGALLERY['CAT_DESCRIPTION'],
    'CAT_DESCRIPTION_VALUE' => $categorie['description'],
    'MODIFY_IMG_STRING' => $MOD_FOLDERGALLERY['MODIFY_IMG'],
    'IMAGE_STRING' => $MOD_FOLDERGALLERY['IMAGE'],
    'IMAGE_NAME_STRING' => $MOD_FOLDERGALLERY['IMAGE_NAME'],
    'IMAGE_CAPTION_STRING' => $MOD_FOLDERGALLERY['IMG_CAPTION'],
    'IMAGE_ACTION_STRING' => $MOD_FOLDERGALLERY['ACTION'],
    'SAVE_STRING' => $TEXT['SAVE'],
    'CANCEL_STRING' => $TEXT['CANCEL'],
    'SORT_IMAGES_STRING' => $MOD_FOLDERGALLERY['SORT_IMAGE'],
    // Section und Page ID
    'SECTION_ID_VALUE' => $section_id,
    'PAGE_ID_VALUE' => $page_id,
    'CAT_ID_VALUE' => $cat_id,
    'IMAGE_DELETE_ALT' => $MOD_FOLDERGALLERY['IMAGE_DELETE_ALT'],
    'THUMB_EDIT_ALT' => $MOD_FOLDERGALLERY['THUMB_EDIT_ALT'],
    'EDIT_THUMB_SOURCE' => THEME_URL . '/images/resize_16.png',
    'DELETE_IMG_SOURCE' => THEME_URL . '/images/delete_16.png',
    'UPLOAD_FOLDER' => $uploadPath,
    'UPLOAD_SEC_NUM' => $page_id . '/' . $section_id . '/' . $cat_id,
    'ADD_MORE_PICS_TITLE' => $MOD_FOLDERGALLERY['ADD_MORE_PICS']
));

// Links parsen
$t->set_var(array(
    'SAVE_CAT_LINK' => WB_URL . '/modules/foldergallery/admin/save_cat.php?page_id=' . $page_id . '&section_id=' . $section_id . '&cat_id=' . $cat_id,
    'SAVE_FILES_LINK' => WB_URL . '/modules/foldergallery/admin/save_files.php?page_id=' . $page_id . '&section_id=' . $section_id . '&cat_id=' . $cat_id,
    'CANCEL_ONCLICK' => 'javascript: window.location = \'' . ADMIN_URL . '/pages/modify.php?page_id=' . $page_id . '\';'
));


// parse Images
foreach ($bilder as $bild) {
    $t->set_var(array(
        'ID_VALUE' => $bild['id'],
        'IMAGE_VALUE' => $bild['thumb_link'] . '?t=' . time(),
        'IMAGE_NAME_VALUE' => $bild['file_name'],
        'CAPTION_VALUE' => $bild['caption'],
        'THUMB_EDIT_LINK' => WB_URL . "/modules/foldergallery/admin/modify_thumb.php?page_id=" . $page_id . "&section_id=" . $section_id . "&cat_id=" . $cat_id . "&id=" . $bild['id'],
        'IMAGE_DELETE_LINK' => "javascript: confirm_link(\"" . $MOD_FOLDERGALLERY['DELETE_ARE_YOU_SURE'] . "\", \"" . WB_URL . "/modules/foldergallery/admin/scripts/delete_img.php?page_id=" . $page_id . "&section_id=" . $section_id . "&cat_id=" . $cat_id . "&id=" . $bild['id'] . "\");",
        'COUNTER' => $bild['id'],
        'EDIT_THUMB_SOURCE' => THEME_URL . '/images/resize_16.png',
        'DELETE_IMG_SOURCE' => THEME_URL . '/images/delete_16.png'
    ));
    $t->parse('FILE_LOOP', 'file_loop', true);
}


$t->pparse('output', 'modify_cat');

// reset the mySQL encoding
mysql_set_charset($oldMysqlEncoding, $database->db_handle);

$admin->print_footer();
?>
