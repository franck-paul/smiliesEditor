<?php
/**
 * @brief smiliesEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Osku and contributors
 *
 * @copyright Osku and contributors
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$s     = dcCore::app()->blog->settings->smilieseditor;
$theme = dcCore::app()->blog->settings->system->theme;
$msg   = $warning = '';

// Init
$smg_writable = false;
if (dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') {
    $combo_action[__('Definition')] = [
        __('update') => 'update',
        __('delete') => 'clear',
    ];
}

$combo_action[__('Toolbar')] = [
    __('display') => 'display',
    __('hide')    => 'hide',
];

$smilies_bar_flag     = (bool) $s->smilies_bar_flag;
$smilies_preview_flag = (bool) $s->smilies_preview_flag;
$smilies_public_text  = $s->smilies_public_text;

// Get theme Infos
dcCore::app()->themes = new dcThemes();
dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
$T = dcCore::app()->themes->getModules($theme);

// Get smilies code
$o       = new smiliesEditor();
$smilies = $o->getSmilies();

// Try to create the subdirectory smilies
if (!empty($_POST['create_dir'])) {
    try {
        $o->createDir();
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }

    if (!dcCore::app()->error->flag()) {
        Http::redirect(dcCore::app()->admin->getPageURL() . '&creadir=1');
    }
}

// Init the filemanager
try {
    $smilies_files = $o->getFiles();
    $smg_writable  = $o->filemanager->writable();
} catch (Exception $e) {
    $warning = '<p class="form-note warn">' . $e->getMessage() . '</p>';
}

if (!empty($_POST['saveconfig'])) {
    try {
        $show     = (empty($_POST['smilies_bar_flag'])) ? false : true;
        $preview  = (empty($_POST['smilies_preview_flag'])) ? false : true;
        $formtext = (empty($_POST['smilies_public_text'])) ? __('Smilies') : $_POST['smilies_public_text'];

        $s->put('smilies_bar_flag', $show, 'boolean', 'Show smilies toolbar');
        $s->put('smilies_preview_flag', $preview, 'boolean', 'Show smilies on preview');
        $s->put('smilies_public_text', $formtext, 'string', 'Smilies displayed in toolbar');

        dcCore::app()->blog->triggerBlog();
        Http::redirect(dcCore::app()->admin->getPageURL() . '&config=1');
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

// Create array of used smilies filename
$smileys_list = [];
foreach ($smilies as $k => $v) {
    $smileys_list = array_merge($smileys_list, [$v['name'] => $v['name']]);
}

// Delete all unused images
if (!empty($_POST['rm_unused_img'])) {
    if (!empty($o->images_list)) {
        foreach ($o->images_list as $k => $v) {
            if (!array_key_exists($v['name'], $smileys_list)) {
                try {
                    $o->filemanager->removeItem($v['name']);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }
    }

    if (!dcCore::app()->error->flag()) {
        Http::redirect(dcCore::app()->admin->getPageURL() . '&dircleaned=1');
    }
}

if (!empty($_FILES['upfile'])) {
    $file = null;

    try {
        Files::uploadStatus($_FILES['upfile']);
        $file = $o->uploadSmile($_FILES['upfile']['tmp_name'], $_FILES['upfile']['name']);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }

    if (!dcCore::app()->error->flag()) {
        if ($file) {
            Http::redirect(dcCore::app()->admin->getPageURL() . '&upok=' . $file);
        } else {
            Http::redirect(dcCore::app()->admin->getPageURL() . '&upzipok=1');
        }
    }
}

// Create the combo of all images available in directory
$smileys_combo = [];
if (!empty($o->images_list)) {
    foreach ($o->images_list as $k => $v) {
        $smileys_combo = array_merge($smileys_combo, [$v['name'] => $v['name']]);
    }
}

$order = [];
if (empty($_POST['smilies_order']) && !empty($_POST['order'])) {
    $order = $_POST['order'];
    asort($order);
    $order = array_keys($order);
} elseif (!empty($_POST['smilies_order'])) {
    $order = explode(',', $_POST['smilies_order']);
}

if (!empty($_POST['actionsmilies']) && !empty($_POST['select'])) {
    $action = $_POST['actionsmilies'];

    switch ($action) {
        case 'clear':
            foreach ($_POST['select'] as $k => $v) {
                unset($smilies[$v]);

                try {
                    $o->setSmilies($smilies);
                    $o->setConfig($smilies);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());

                    break;
                }
            }
            if (!dcCore::app()->error->flag()) {
                Http::redirect(dcCore::app()->admin->getPageURL() . '&remove=1');
            }

            break;

        case 'update':
            foreach ($_POST['select'] as $k => $v) {
                $smilies[$v]['code'] = isset($_POST['code'][$v]) ? preg_replace('/[\s]+/', '', (string) $_POST['code'][$v]) : $smilies[$v]['code'] ;
                $smilies[$v]['name'] = $_POST['name'][$v] ?? $smilies[$v]['name'];
            }

            try {
                $o->setSmilies($smilies);
                $o->setConfig($smilies);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if (!dcCore::app()->error->flag()) {
                Http::redirect(dcCore::app()->admin->getPageURL() . '&update=1');
            }

            break;

        case 'display':
            foreach ($_POST['select'] as $k => $v) {
                $smilies[$v]['onSmilebar'] = true;
            }

            try {
                $o->setConfig($smilies);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if (!dcCore::app()->error->flag()) {
                Http::redirect(dcCore::app()->admin->getPageURL() . '&display=1');
            }

            break;

        case 'hide':
            foreach ($_POST['select'] as $k => $v) {
                $smilies[$v]['onSmilebar'] = false;
            }

            try {
                $o->setConfig($smilies);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if (!dcCore::app()->error->flag()) {
                Http::redirect(dcCore::app()->admin->getPageURL() . '&hide=1');
            }

            break;
    }
}

if (!empty($_POST['saveorder']) && !empty($order)) {
    foreach ($order as $k => $v) {
        $new_smilies[$v] = $smilies[$v];
    }

    try {
        $o->setSmilies($new_smilies);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }

    if (!dcCore::app()->error->flag()) {
        Http::redirect(dcCore::app()->admin->getPageURL() . '&neworder=1');
    }
}

if (!empty($_POST['smilecode']) && !empty($_POST['smilepic'])) {
    $count                   = is_countable($smilies) ? count($smilies) : 0;
    $smilies[$count]['code'] = preg_replace('/[\s]+/', '', (string) $_POST['smilecode']);
    $smilies[$count]['name'] = $_POST['smilepic'];

    try {
        $o->setSmilies($smilies);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }

    if (!dcCore::app()->error->flag()) {
        Http::redirect(dcCore::app()->admin->getPageURL() . '&addsmile=1');
    }
}

# Zip download
if (!empty($_GET['zipdl'])) {
    try {
        @set_time_limit(300);
        $fp  = fopen('php://output', 'wb');
        $zip = new fileZip($fp);
        $zip->addDirectory(dcCore::app()->themes->moduleInfo($theme, 'root') . '/smilies', '', true);
        header('Content-Disposition: attachment;filename=smilies-' . $theme . '.zip');
        header('Content-Type: application/x-zip');
        $zip->write();
        unset($zip);
        exit;
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

if (!empty($_GET['config'])) {
    $msg = '<p class="message">' . __('Configuration successfully updated.') . '</p>';
}

if (!empty($_GET['creadir'])) {
    $msg = '<p class="message">' . __('The subfolder has been successfully created') . '</p>';
}

if (!empty($_GET['dircleaned'])) {
    $msg = '<p class="message">' . __('Unused images have been successfully removed.') . '</p>';
}

if (!empty($_GET['upok'])) {
    $msg = '<p class="message">' . sprintf(__('The image <em>%s</em> has been successfully uploaded.'), $_GET['upok']) . '</p>';
}

if (!empty($_GET['upzipok'])) {
    $msg = '<p class="message">' . __('A smilies zip package has been successfully installed.') . '</p>';
}

if (!empty($_GET['remove'])) {
    $msg = '<p class="message">' . __('Smilies has been successfully removed.') . '</p>';
}

if (!empty($_GET['update'])) {
    $msg = '<p class="message">' . __('Smilies has been successfully updated.') . '</p>';
}

if (!empty($_GET['neworder'])) {
    $msg = '<p class="message">' . __('Order of smilies has been successfully changed.') . '</p>';
}

if (!empty($_GET['hide'])) {
    $msg = '<p class="message">' . __('These selected smilies are now hidden on toolbar.') . '</p>';
}

if (!empty($_GET['display'])) {
    $msg = '<p class="message">' . __('These selected smilies are now displayed on toolbar') . '</p>';
}

if (!empty($_GET['addsmile'])) {
    $msg = '<p class="message">' . __('A new smiley has been successfully created') . '</p>';
}
?>
<html>
<head>
	<title><?php echo __('Smilies Editor'); ?></title>
	<?php
echo
dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
dcPage::jsLoad('index.php?pf=smiliesEditor/js/_smilies.js');
?>
	
	  <script type="text/javascript">
	  <?php echo dcPage::jsVar('dotclear.smilies_base_url', dcCore::app()->blog->host . $o->smilies_base_url);?>
	  dotclear.msg.confirm_image_delete = '<?php echo Html::escapeJS(sprintf(__('Are you sure you want to remove these %s ?'), 'images')) ?>';
	  $(function() {
	    $('#del_form').on('submit', function() {
	      return window.confirm(dotclear.msg.confirm_image_delete);
	    });
	  });
	  </script>

	
	<style type="text/css">
		option[selected=selected] {color:#c00;}
		select {background-color:#FFF !important;}
		a.add {background:inherit url(images/plus.png) top left;}
		img.smiley {vertical-align : middle;}
		/*tr.offline {background-color : #f4f4ef;}*/
		tr td.smiley { text-align:center}
          #smilepic,#smilepic option,select.emote, select.emote option {background-color:transparent;
               background-repeat:no-repeat;background-position:4% 50%;
               padding:1px 1px 1px 30px;color:#444;height:26px;}
		option[selected=selected] {background-color:#E2DFCA !important;}
	</style>
</head>

<body>

<?php
if (!empty($o->images_list)) {
    $images_all = $o->images_list;
    foreach ($o->images_list as $k => $v) {
        if (array_key_exists($v['name'], $smileys_list)) {
            unset($o->images_list[$k]);
        }
    }
}
if ($msg) {
    echo $msg;
}

echo
'<h2>' . Html::escapeHTML(dcCore::app()->blog->name) . ' &rsaquo; <span class="page-title">' . __('Smilies Editor') . '</span></h2>';

echo
'<p>' . sprintf(__('Your <a href="blog_theme.php">current theme</a> on this blog is "%s".'), '<strong>' . Html::escapeHTML($T['name']) . '</strong>') . '</p>';

if ($warning) {
    echo $warning;
}

if (empty($smilies)) {
    if (!empty($o->filemanager)) {
        echo '<br /><p class="form-note info ">' . __('No defined smiley yet.') . '</p><br />';
    }
} else {
    echo
    '<div class="clear" id="smilies_options">' .
    '<form action="plugin.php" method="post" id="form_smilies_options">' .
            '<h3>' . __('Configuration') . '</h3>' .
                '<div class="two-cols">' .
                    '<p class="col">' .
                        form::checkbox('smilies_bar_flag', '1', $smilies_bar_flag) .
                        '<label class="classic" for="smilies_bar_flag">' . __('Show toolbar smilies in comments form') . '</label>' .
                    '</p>' .
                    '<p class="col">' .
                        form::checkbox('smilies_preview_flag', '1', $smilies_preview_flag) .
                        '<label class=" classic" for="smilies_preview_flag">' . __('Show images on preview') . '</label>' .
                    '</p>' .

                    '<p class="clear">' .
                        '<label class="required classic" for="smilies_preview_flag">' . __('Comments form label:') . '</label>&nbsp;&nbsp;' .
                        form::field('smilies_public_text', 50, 255, Html::escapeHTML($smilies_public_text)) .
                    '</p>' .
                    '<br /><p class="clear form-note">' .
                        sprintf(__('Don\'t forget to <a href="%s">display smilies</a> on your blog configuration.'), 'blog_pref.php#params.use_smilies') .
                    '</p>' .
                    '<p class="clear">' .
                        form::hidden(['p'], 'smiliesEditor') .
                        dcCore::app()->formNonce() .
                        '<input type="submit" name="saveconfig" value="' . __('Save') . '" />' .
                    '</p>' .
                '</div>' .
    '</form></div>';

    $colspan = (dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') ? 3 : 2;
    echo
        '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="smilies-form">' .
        '<h3>' . __('Smilies set') . '</h3>' .
        '<table class="maximal dragable">' .
        '<thead>' .
        '<tr>' .
        '<th colspan="' . $colspan . '">' . __('Code') . '</th>' .
        '<th>' . __('Image') . '</th>' .
        //'<noscript><th>'.__('Filename').'</th></noscript>'.
        '</tr>' .
        '</thead>' .

    '<tbody id="smilies-list">';
    foreach ($smilies as $k => $v) {
        if ($v['onSmilebar']) {
            $line   = '';
            $status = '<img alt="' . __('displayed') . '" title="' . __('displayed') . '" src="images/check-on.png" />';
        } else {
            $line   = 'offline';
            $status = '<img alt="' . __('undisplayed') . '" title="' . __('undisplayed') . '" src="images/check-wrn.png" />';
        }
        $disabled = (dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') ? false : true;
        echo
        '<tr class="line ' . $line . '" id="l_' . ($k) . '">';
        if (dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') {
            echo  '<td class="handle minimal">' . form::field(['order[' . $k . ']'], 2, 5, $k, 'position') . '</td>' ;
        }
        echo
        '<td class="minimal status">' . form::checkbox(['select[]'], $k) . '</td>' .
        '<td class="minimal">' . form::field(['code[]','c' . $k], 20, 255, Html::escapeHTML($v['code']), '', '', $disabled) . '</td>' .
        //'<noscript><td class="minimal smiley"><img src="'.dcCore::app()->blog->host.$o->smilies_base_url.$v['name'].'" alt="'.$v['code'].'" /></td></noscript>'.
        '<td class="nowrap status">' . form::combo(['name[]','n' . $k], $smileys_combo, $v['name'], 'emote', '', $disabled) . $status . '</td>' .
        '</tr>';
    }

    echo '</tbody></table>';

    echo '<div class="two-cols">
		<p class="col checkboxes-helpers"></p>';

    echo	'<p class="col right">' . __('Selected smilies action:') . ' ' .
        form::hidden('smilies_order', '') .
        form::hidden(['p'], 'smiliesEditor') .
        form::combo('actionsmilies', $combo_action) .
        dcCore::app()->formNonce() .
        '<input type="submit" value="' . __('Ok') . '" /></p>';

    if ((dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup')) {
        echo '<p><input type="submit" name="saveorder" id="saveorder"
		value="' . __('Save order') . '" 
		/></p>';
    }

    echo '</div></form>';
}

echo '<br /><br /><div class="three-cols">';

if (empty($images_all)) {
    if (empty($o->filemanager)) {
        echo '<div class="col"><form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="dir_form"><p>' . form::hidden(['p'], 'smiliesEditor') .
        dcCore::app()->formNonce() .
        '<input type="submit" name="create_dir" value="' . __('Initialize') . '" /></p></form></div>';
    }
} else {
    if (dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') {
        $val            = array_values($images_all);
        $preview_smiley = '<img class="smiley" src="' . dcCore::app()->blog->host . $val[0]['url'] . '" alt="' . $val[0]['name'] . '" title="' . $val[0]['name'] . '" id="smiley-preview" />';

        echo
            '<div class="col">' .
            '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="add-smiley-form">' .
            '<h3>' . __('New smiley') . '</h3>' .
            '<p><label for="smilepic" class="classic required">
			<abbr title="' . __('Required field') . '">*</abbr>
			' . __('Image:') . ' ' .
            form::combo('smilepic', $smileys_combo) . '</label></p>' .

            '<p><label for="smilecode" class="classic required">
			<abbr title="' . __('Required field') . '">*</abbr>
			' . __('Code:') . ' ' .
            form::field('smilecode', 20, 255) . '</label>' .

            form::hidden(['p'], 'smiliesEditor') .
            dcCore::app()->formNonce() .
            '&nbsp; <input type="submit" name="add_message" value="' . __('Create') . '" /></p>' .
            '</form></div>';
    }
}

if ($smg_writable && dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') {
    echo
    '<div class="col"><form id="upl-smile-form" action="' . Html::escapeURL(dcCore::app()->admin->getPageURL()) . '" method="post" enctype="multipart/form-data">' .
    '<h3>' . __('New image') . '</h3>' .
    '<p>' . form::hidden(['MAX_FILE_SIZE'], DC_MAX_UPLOAD_SIZE) .
    dcCore::app()->formNonce() .
    '<label>' . __('Choose a file:') .
    ' (' . sprintf(__('Maximum size %s'), Files::size(DC_MAX_UPLOAD_SIZE)) . ')' .
    '<input type="file" name="upfile" size="20" />' .
    '</label></p>' .
    '<p><input type="submit" value="' . __('Send') . '" />' .
    form::hidden(['d'], null) . '</p>' .
    //'<p class="form-note">'.__('Please take care to publish media that you own and that are not protected by copyright.').'</p>'.
    '</form></div>';
}

if (!empty($images_all) && dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') {
    if (!empty($o->images_list)) {
        echo '<div class="col"><form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="del_form">' .
        '<h3>' . __('Unused smilies') . '</h3>';

        echo '<p>';
        foreach ($o->images_list as $k => $v) {
            echo	'<img src="' . dcCore::app()->blog->host . $v['url'] . '" alt="' . $v['name'] . '" title="' . $v['name'] . '" />&nbsp;';
        }
        echo '</p>';

        echo
        '<p>' . form::hidden(['p'], 'smiliesEditor') .
        dcCore::app()->formNonce() .
        '<input type="submit" name="rm_unused_img" 
		value="' . __('Delete') . '" 
		/></p></form></div>';
    }
}

echo '</div>';

if (!empty($images_all)) {
    echo  '<p class="zip-dl clear"><a href="' . Html::escapeURL(dcCore::app()->admin->getPageURL()) . '&amp;zipdl=1">' .
        __('Download the smilies directory as a zip file') . '</a></p>';
}
dcPage::helpBlock('smilieseditor');
?>
</body>
</html>
