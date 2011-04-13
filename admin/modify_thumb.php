<?php

require('../../../config.php');
require(WB_PATH . '/modules/admin.php');

// check if module language file exists for the language set by the user (e.g. DE, EN)
if (!file_exists(WB_PATH . '/modules/foldergallery/languages/' . LANGUAGE . '.php')) {
// no module language file exists for the language set by the user, include default module language file DE.php
    require_once(WB_PATH . '/modules/foldergallery/languages/DE.php');
} else {
// a module language file exists for the language defined by the user, load it
    require_once(WB_PATH . '/modules/foldergallery/languages/' . LANGUAGE . '.php');
}

// Files includen
require_once (WB_PATH . '/modules/foldergallery/info.php');
require_once (WB_PATH . '/modules/foldergallery/admin/scripts/backend.functions.php');



$cat_id = $_GET['cat_id'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $settings = getSettings($section_id);
    $root_dir = $settings['root_dir']; //Chio

    $sql = 'SELECT * FROM ' . TABLE_PREFIX . 'mod_foldergallery_files WHERE id=' . $_GET['id'] . ';';
    if ($query = $database->query($sql)) {
        $result = $query->fetchRow();
        $bildfilename = $result['file_name'];
        $parent_id = $result['parent_id'];


        $query2 = $database->query('SELECT * FROM ' . TABLE_PREFIX . 'mod_foldergallery_categories WHERE id=' . $parent_id . ' LIMIT 1;');
        $categorie = $query2->fetchRow();
        if ($categorie['parent'] != "-1") {
            $parent = $categorie['parent'] . '/' . $categorie['categorie'];
        }
        else
            $parent = '';

        $full_file_link = $url . $root_dir . $parent . '/' . $bildfilename;
        $full_file = $path . $root_dir . $parent . '/' . $bildfilename;
        $thumb_file = $path . $root_dir . $parent . $thumbdir . '/' . $bildfilename;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //Löscht das bisherige Thumbnail
            deleteFile($thumb_file);

            //Neues Thumb erstellen
            if (generateThumb($full_file, $thumb_file, $settings['thumb_size'], 1, $settings['ratio'], 100, '999999', $_POST['x'], $_POST['y'], $_POST['w'], $_POST['h'])) {
                $admin->print_success('Thumb erfolgreich geändert', WB_URL . '/modules/foldergallery/admin/modify_cat.php?page_id=' . $page_id . '&section_id=' . $section_id . '&cat_id=' . $cat_id);
            }
        } else {
            list($width, $height, $type, $attr) = getimagesize($full_file); //str_replace um auch Datein oder Ordner mit leerzeichen bearbeiten zu können.
            //erstellt ein passendes Vorschaufenster zum eingestellten Verhältniss
            if ($settings['ratio'] > 1) {
                $previewWidth = $settings['thumb_size'];
                $previewHeight = $settings['thumb_size'] / $settings['ratio'];
            } else {
                $previewWidth = $settings['thumb_size'] * $settings['ratio'];
                $previewHeight = $settings['thumb_size'];
            }

            $t = new Template(dirname(__FILE__) . '/templates', 'remove');
            $t->set_file('modify_thumb', 'modify_thumb.htt');
            // clear the comment-block, if present
            $t->set_block('modify_thumb', 'CommentDoc');
            $t->clear_var('CommentDoc');

            $t->set_var(array(
                // Infos for JCrop
                'REL_WIDTH'         => $width,
                'REL_HEIGHT'        => $height,
                'THUMB_SIZE'        => $settings['thumb_size'],
                'RATIO'             => $settings['ratio'],
                // Language Strings
                'EDIT_THUMB'        => $MOD_FOLDERGALLERY['EDIT_THUMB'],
                'EDIT_THUMB_DESCR'  => $MOD_FOLDERGALLERY['EDIT_THUMB_DESCRIPTION'],
                'SAVE_NEW_THUMB'    => $MOD_FOLDERGALLERY['EDIT_THUMB_BUTTON'],
                'CANCEL'            => $TEXT['CANCEL'],
                // Data about the Image and Preview
                'FULL_FILE_LINK'    => $full_file_link,
                'PREVIEW_HEIGHT'    => $previewHeight,
                'PREVIEW_WIDTH'     => $previewWidth,
                'WB_URL'            => WB_URL,
                'PAGE_ID'           => $page_id,
                'SECTION_ID'        => $section_id,
                'CAT_ID'            => $cat_id,
                'IMG_ID'            => $_GET['id']
            ));

            $t->pparse('output', 'modify_thumb');
        }
    }
} else {
    $admin->print_error($MOD_FOLDERGALLERY['ERROR_MESSAGE'], WB_URL . '/modules/foldergallery/admin/modify_cat.php?page_id=' . $page_id . '&section_id=' . $section_id . '&cat_id=' . $cat_id);
}
$admin->print_footer();
?>
