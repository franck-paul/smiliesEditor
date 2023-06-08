<?php
/**
 * @brief smiliesEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\smiliesEditor;

use dcCore;
use dcNamespace;
use dcNsProcess;
use dcPage;
use dcThemes;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

class Manage extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE);

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        $settings = dcCore::app()->blog->settings->get(My::id());

        // Get smilies code
        $smilies_editor = new CoreHelper();
        $smilies        = $smilies_editor->getSmilies();

        $theme = dcCore::app()->blog->settings->system->theme;

        if (!empty($_POST['create_dir'])) {
            try {
                $smilies_editor->createDir();
                Http::redirect(dcCore::app()->admin->getPageURL());
                dcPage::addSuccessNotice(__('The subfolder has been successfully created'));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                $show     = (empty($_POST['smilies_bar_flag'])) ? false : true;
                $preview  = (empty($_POST['smilies_preview_flag'])) ? false : true;
                $formtext = (empty($_POST['smilies_public_text'])) ? __('Smilies') : $_POST['smilies_public_text'];

                $settings->put('smilies_bar_flag', $show, dcNamespace::NS_BOOL, 'Show smilies toolbar');
                $settings->put('smilies_preview_flag', $preview, dcNamespace::NS_BOOL, 'Show smilies on preview');
                $settings->put('smilies_public_text', $formtext, dcNamespace::NS_STRING, 'Smilies displayed in toolbar');

                dcCore::app()->blog->triggerBlog();
                dcPage::addSuccessNotice(__('Configuration successfully updated.'));
                Http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Delete all unused images
        if (!empty($_POST['rm_unused_img'])) {
            try {
                // Create array of used smilies filename
                $smileys_list = [];
                foreach ($smilies as $k => $v) {
                    $smileys_list = array_merge($smileys_list, [$v['name'] => $v['name']]);
                }

                if (!empty($smilies_editor->images_list)) {
                    foreach ($smilies_editor->images_list as $k => $v) {
                        if (!array_key_exists($v['name'], $smileys_list)) {
                            try {
                                $smilies_editor->filemanager->removeItem($v['name']);
                            } catch (Exception $e) {
                                dcCore::app()->error->add($e->getMessage());
                            }
                        }
                    }
                }

                dcPage::addSuccessNotice(__('Unused images have been successfully removed.'));
                Http::redirect(dcCore::app()->admin->getPageURL() . '&dircleaned=1');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_FILES['upfile'])) {
            try {
                $file = null;
                Files::uploadStatus($_FILES['upfile']);
                $file = $smilies_editor->uploadSmile($_FILES['upfile']['tmp_name'], $_FILES['upfile']['name']);
                if ($file) {
                    dcPage::addSuccessNotice(sprintf(__('The image <em>%s</em> has been successfully uploaded.'), $_GET['upok']));
                    Http::redirect(dcCore::app()->admin->getPageURL());
                } else {
                    dcPage::addSuccessNotice(__('A smilies zip package has been successfully installed.'));
                    Http::redirect(dcCore::app()->admin->getPageURL());
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['saveorder'])) {
            $order = [];
            if (empty($_POST['smilies_order']) && !empty($_POST['order'])) {
                $order = $_POST['order'];
                asort($order);
                $order = array_keys($order);
            } elseif (!empty($_POST['smilies_order'])) {
                $order = explode(',', $_POST['smilies_order']);
            }

            if (!empty($order)) {
                try {
                    foreach ($order as $v) {
                        $new_smilies[$v] = $smilies[$v];
                    }
                    $smilies_editor->setSmilies($new_smilies);
                    dcPage::addSuccessNotice(__('Order of smilies has been successfully changed.'));
                    Http::redirect(dcCore::app()->admin->getPageURL());
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }

        if (!empty($_POST['actionsmilies']) && !empty($_POST['select'])) {
            $action = $_POST['actionsmilies'];

            switch ($action) {
                case 'clear':
                    try {
                        foreach ($_POST['select'] as $k => $v) {
                            unset($smilies[$v]);
                        }

                        $smilies_editor->setSmilies($smilies);
                        $smilies_editor->setConfig($smilies);
                        dcPage::addSuccessNotice(__('Smilies has been successfully removed.'));
                        Http::redirect(dcCore::app()->admin->getPageURL());
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
                    }

                    break;

                case 'update':
                    try {
                        foreach ($_POST['select'] as $k => $v) {
                            $smilies[$v]['code'] = isset($_POST['code'][$v]) ? preg_replace('/[\s]+/', '', (string) $_POST['code'][$v]) : $smilies[$v]['code'] ;
                            $smilies[$v]['name'] = $_POST['name'][$v] ?? $smilies[$v]['name'];
                        }

                        $smilies_editor->setSmilies($smilies);
                        $smilies_editor->setConfig($smilies);
                        dcPage::addSuccessNotice(__('Smilies has been successfully updated.'));
                        Http::redirect(dcCore::app()->admin->getPageURL());
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
                    }

                    break;

                case 'display':
                    try {
                        foreach ($_POST['select'] as $k => $v) {
                            $smilies[$v]['onSmilebar'] = true;
                        }

                        $smilies_editor->setConfig($smilies);
                        dcPage::addSuccessNotice(__('These selected smilies are now displayed on toolbar'));
                        Http::redirect(dcCore::app()->admin->getPageURL());
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
                    }

                    break;

                case 'hide':
                    try {
                        foreach ($_POST['select'] as $k => $v) {
                            $smilies[$v]['onSmilebar'] = false;
                        }

                        $smilies_editor->setConfig($smilies);
                        dcPage::addSuccessNotice(__('These selected smilies are now hidden on toolbar.'));
                        Http::redirect(dcCore::app()->admin->getPageURL());
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
                    }

                    break;
            }
        }

        if (!empty($_POST['smilecode']) && !empty($_POST['smilepic'])) {
            try {
                $count                   = is_countable($smilies) ? count($smilies) : 0;
                $smilies[$count]['code'] = preg_replace('/[\s]+/', '', (string) $_POST['smilecode']);
                $smilies[$count]['name'] = $_POST['smilepic'];

                $smilies_editor->setSmilies($smilies);
                dcPage::addSuccessNotice(__('A new smiley has been successfully created'));
                Http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # Zip download
        if (!empty($_GET['zipdl'])) {
            try {
                @set_time_limit(300);
                $fp  = fopen('php://output', 'wb');
                $zip = new Zip($fp);
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

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $settings = dcCore::app()->blog->settings->get(My::id());
        $theme    = dcCore::app()->blog->settings->system->theme;

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

        $smilies_bar_flag     = (bool) $settings->smilies_bar_flag;
        $smilies_preview_flag = (bool) $settings->smilies_preview_flag;
        $smilies_public_text  = $settings->smilies_public_text;

        // Get theme Infos
        dcCore::app()->themes = new dcThemes();
        dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        $theme_define = dcCore::app()->themes->getDefine($theme);

        // Get smilies code
        $smilies_editor = new CoreHelper();
        $smilies        = $smilies_editor->getSmilies();

        // Init the filemanager
        try {
            $smilies_editor->getFiles();
            $smg_writable = $smilies_editor->filemanager->writable();
        } catch (Exception $e) {
            dcPage::addWarningNotice($e->getMessage());
        }

        // Create array of used smilies filename
        $smileys_list = [];
        foreach ($smilies as $k => $v) {
            $smileys_list = array_merge($smileys_list, [$v['name'] => $v['name']]);
        }

        // Create the combo of all images available in directory
        $smileys_combo = [];
        if (!empty($smilies_editor->images_list)) {
            foreach ($smilies_editor->images_list as $k => $v) {
                $smileys_combo = array_merge($smileys_combo, [$v['name'] => $v['name']]);
            }
        }

        if (!empty($smilies_editor->images_list)) {
            $images_all = $smilies_editor->images_list;
            foreach ($smilies_editor->images_list as $k => $v) {
                if (array_key_exists($v['name'], $smileys_list)) {
                    unset($smilies_editor->images_list[$k]);
                }
            }
        }

        $head = dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
        dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
        dcPage::jsJson('smilies', [
            'smilies_base_url'     => dcCore::app()->blog->host . $smilies_editor->smilies_base_url,
            'confirm_image_delete' => sprintf(__('Are you sure you want to remove these %s ?'), 'images'),
        ]) .
        dcPage::jsModuleLoad(My::id() . '/js/_smilies.js') .
        dcPage::cssModuleLoad(My::id() . '/css/admin.css');

        dcPage::openModule(__('Smilies Editor'), $head);

        echo dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                __('Smilies Editor')                        => '',
            ]
        );
        echo dcPage::notices();

        // Form
        echo
        '<p>' . sprintf(__('Your <a href="blog_theme.php">current theme</a> on this blog is "%s".'), '<strong>' . Html::escapeHTML($theme_define->get('name')) . '</strong>') . '</p>';

        if (empty($smilies)) {
            if (!empty($smilies_editor->filemanager)) {
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

            echo    '<p class="col right">' . __('Selected smilies action:') . ' ' .
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
            if (empty($smilies_editor->filemanager)) {
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
            ' (' . sprintf(__('Maximum size %s'), Files::size((int) DC_MAX_UPLOAD_SIZE)) . ')' .
            '<input type="file" name="upfile" size="20" />' .
            '</label></p>' .
            '<p><input type="submit" value="' . __('Send') . '" />' .
            form::hidden(['d'], null) . '</p>' .
            //'<p class="form-note">'.__('Please take care to publish media that you own and that are not protected by copyright.').'</p>'.
            '</form></div>';
        }

        if (!empty($images_all) && dcCore::app()->auth->isSuperAdmin() && $theme != 'blowup') {
            if (!empty($smilies_editor->images_list)) {
                echo '<div class="col"><form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="del_form">' .
                '<h3>' . __('Unused smilies') . '</h3>';

                echo '<p>';
                foreach ($smilies_editor->images_list as $k => $v) {
                    echo    '<img src="' . dcCore::app()->blog->host . $v['url'] . '" alt="' . $v['name'] . '" title="' . $v['name'] . '" />&nbsp;';
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

        dcPage::closeModule();
    }
}
