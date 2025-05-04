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

use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;

class BackendBehaviors
{
    /**
     * @param      null|Metarecord   $rs   The arguments
     */
    public static function adminUserForm(?MetaRecord $rs = null): string
    {
        /**
         * @var        array<string, mixed>
         */
        $opts = [];

        if (is_null($rs)) {
            $opts = App::auth()->getOptions();
        } elseif ($rs instanceof MetaRecord) {
            $opts = $rs->options();
        }

        $value = $opts['smilies_editor_admin'] ?? false;

        echo (new Fieldset('smilies_editor'))
            ->legend(new Legend(__('Toolbar')))
            ->items([
                (new Para())->items([
                    (new Checkbox('smilies_editor_admin', (bool) $value))
                        ->label(new Label(__('Display smilies on toolbar'), Label::INSIDE_TEXT_AFTER)),
                ]),
            ])
        ->render();

        return '';
    }

    public static function setSmiliesDisplay(Cursor $cur): string
    {
        $cur->user_options['smilies_editor_admin'] = !empty($_POST['smilies_editor_admin']);

        return '';
    }

    public static function adminPostHeaders(): string
    {
        $smiliesEditor = new CoreHelper();
        $smilies       = $smiliesEditor->getSmilies();
        $buttons       = [];
        foreach ($smilies as $id => $smiley) {
            if ($smiley['onSmilebar']) {
                $buttons[] = [
                    'id'   => $id,
                    'code' => Html::escapeJS($smiley['code']),
                    'icon' => Html::escapeJS(App::blog()->host() . $smiliesEditor->smilies_base_url . $smiley['name']),
                ];
            }
        }

        return
        Page::jsJson('smilieseditor', $buttons) .
        My::jsLoad('legacy_smilies.js');
    }
}
