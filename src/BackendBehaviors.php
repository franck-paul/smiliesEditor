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
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use form;

class BackendBehaviors
{
    /**
     * @param      dcCore|Metarecord   $args   The arguments
     *
     * @return     string
     */
    public static function adminUserForm($args): string
    {
        /**
         * @var        array<string, mixed>
         */
        $opts = [];

        if ($args instanceof dcCore) {
            $opts = $args->auth->getOptions();
        } elseif ($args instanceof MetaRecord) {
            $opts = $args->options();
        }

        $value = array_key_exists('smilies_editor_admin', $opts) ? $opts['smilies_editor_admin'] : false;

        echo
        '<fieldset><legend>' . __('Toolbar') . '</legend>' .
        '<p><label class="classic">' .
        form::checkbox('smilies_editor_admin', '1', $value) . __('Display smilies on toolbar') .
        '</label></p></fieldset>';

        return '';
    }

    public static function setSmiliesDisplay(Cursor $cur, ?string $user_id = null): string
    {
        if (!is_null($user_id) && isset($_POST['smilies_editor_admin'])) {
            $cur->user_options['smilies_editor_admin'] = $_POST['smilies_editor_admin'];
        }

        return '';
    }

    public static function adminPostHeaders(): string
    {
        $res = '<script type="text/javascript">' . "\n";

        $sE      = new CoreHelper();
        $smilies = $sE->getSmilies();
        foreach ($smilies as $id => $smiley) {
            if ($smiley['onSmilebar']) {
                $res .= 'jsToolBar.prototype.elements.smilieseditor_s' . $id . " = {type: 'button', title: '" . Html::escapeJS($smiley['code']) . "', fn:{} }; " .
                    //"jsToolBar.prototype.elements.smilieseditor_s".$id.".context = 'post'; ".
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".icon = '" . Html::escapeJS(App::blog()->host() . $sE->smilies_base_url . $smiley['name']) . "'; " .
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".fn.wiki = function() { this.encloseSelection('" . Html::escapeJS($smiley['code']) . "  ',''); }; " .
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".fn.xhtml = function() { this.encloseSelection('" . Html::escapeJS($smiley['code']) . "  ',''); }; " .
                    'jsToolBar.prototype.elements.smilieseditor_s' . $id . ".fn.wysiwyg = function() {
                        smiley = document.createTextNode('" . Html::escapeJS($smiley['code']) . " ');
                        this.insertNode(smiley);
                    };\n";
            }
        }
        $res .= "</script>\n";

        return $res;
    }
}
