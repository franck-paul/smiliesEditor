<?php

/**
 * @brief smiliesEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\smiliesEditor;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\UserWorkspaceInterface;

class BackendBehaviors
{
    public static function adminUserForm(): string
    {
        $value = is_bool($value = App::auth()->prefs()->get('interface')->get('smilies_editor_admin')) && $value;

        echo (new Fieldset('smilies_editor'))
            ->legend(new Legend(__('Toolbar')))
            ->items([
                (new Para())->items([
                    (new Checkbox('smilies_editor_admin', $value))
                        ->label(new Label(__('Display smilies on toolbar'), Label::INSIDE_TEXT_AFTER)),
                ]),
            ])
        ->render();

        return '';
    }

    public static function setSmiliesDisplay(): string
    {
        App::auth()->prefs()->get('interface')->put(
            'smilies_editor_admin',
            !empty($_POST['smilies_editor_admin']),
            UserWorkspaceInterface::WS_BOOL
        );

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
        App::backend()->page()->jsJson('smilieseditor', $buttons) .
        My::jsLoad('legacy_smilies.js');
    }
}
