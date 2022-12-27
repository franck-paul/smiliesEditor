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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Smilies Editor'),
    'plugin.php?p=smiliesEditor',
    'index.php?pf=smiliesEditor/icon.png',
    preg_match('/plugin.php\?p=smiliesEditor(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_ADMIN,
    ]), dcCore::app()->blog->id)
);

dcCore::app()->addBehavior('adminPreferencesForm', ['smiliesEditorAdminBehaviors','adminUserForm']);
dcCore::app()->addBehavior('adminUserForm', ['smiliesEditorAdminBehaviors','adminUserForm']);
dcCore::app()->addBehavior('adminBeforeUserCreate', ['smiliesEditorAdminBehaviors','setSmiliesDisplay']);
dcCore::app()->addBehavior('adminBeforeUserUpdate', ['smiliesEditorAdminBehaviors','setSmiliesDisplay']);

if (dcCore::app()->auth->getOption('smilies_editor_admin')) {
    dcCore::app()->addBehavior('adminPostHeaders', ['smiliesEditorAdminBehaviors','adminPostHeaders']);
    dcCore::app()->addBehavior('adminPageHeaders', ['smiliesEditorAdminBehaviors','adminPostHeaders']);
    dcCore::app()->addBehavior('adminRelatedHeaders', ['smiliesEditorAdminBehaviors','adminPostHeaders']);
    dcCore::app()->addBehavior('adminDashboardHeaders', ['smiliesEditorAdminBehaviors','adminPostHeaders']);
}

class smiliesEditorAdminBehaviors
{
    public static function adminUserForm($args)
    {
        if ($args instanceof dcCore) {
            $opts = $args->auth->getOptions();
        } elseif ($args instanceof record) {
            $opts = $args->options();
        } else {
            $opts = [];
        }

        $value = array_key_exists('smilies_editor_admin', $opts) ? $opts['smilies_editor_admin'] : false;

        echo
        '<fieldset><legend>' . __('Toolbar') . '</legend>' .
        '<p><label class="classic">' .
        form::checkbox('smilies_editor_admin', '1', $value) . __('Display smilies on toolbar') .
        '</label></p></fieldset>';
    }

    public static function setSmiliesDisplay($cur, $user_id = null)
    {
        if (!is_null($user_id)) {
            $cur->user_options['smilies_editor_admin'] = $_POST['smilies_editor_admin'];
        }
    }

    public static function adminPostHeaders()
    {
        $res = '<script type="text/javascript">' . "\n";

        $sE      = new smiliesEditor();
        $smilies = $sE->getSmilies();
        foreach ($smilies as $id => $smiley) {
            if ($smiley['onSmilebar']) {
                $res .= 'jsToolBar.prototype.elements.smilieseditor_s' . $id . " = {type: 'button', title: '" . html::escapeJS($smiley['code']) . "', fn:{} }; " .
                    //"jsToolBar.prototype.elements.smilieseditor_s".$id.".context = 'post'; ".
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".icon = '" . html::escapeJS(dcCore::app()->blog->host . $sE->smilies_base_url . $smiley['name']) . "'; " .
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".fn.wiki = function() { this.encloseSelection('" . html::escapeJS($smiley['code']) . "  ',''); }; " .
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".fn.xhtml = function() { this.encloseSelection('" . html::escapeJS($smiley['code']) . "  ',''); }; " .
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".fn.wysiwyg = function() {
						smiley = document.createTextNode('" . html::escapeJS($smiley['code']) . " ');
						this.insertNode(smiley);
					};\n";
            }
        }
        $res .= "</script>\n";

        return $res;
    }
}
