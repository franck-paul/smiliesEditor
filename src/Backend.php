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
use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('smiliesEditor') . __('Smilies Editor');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(Menus::MENU_BLOG);

        App::behavior()->addBehavior('adminPreferencesFormV2', BackendBehaviors::adminUserForm(...));
        App::behavior()->addBehavior('adminUserForm', BackendBehaviors::adminUserForm(...));
        App::behavior()->addBehavior('adminBeforeUserCreate', BackendBehaviors::setSmiliesDisplay(...));
        App::behavior()->addBehavior('adminBeforeUserUpdate', BackendBehaviors::setSmiliesDisplay(...));

        if (App::auth()->getOption('smilies_editor_admin')) {
            App::behavior()->addBehavior('adminPostHeaders', BackendBehaviors::adminPostHeaders(...));
            App::behavior()->addBehavior('adminPageHeaders', BackendBehaviors::adminPostHeaders(...));
            App::behavior()->addBehavior('adminRelatedHeaders', BackendBehaviors::adminPostHeaders(...));
            App::behavior()->addBehavior('adminDashboardHeaders', BackendBehaviors::adminPostHeaders(...));
        }

        return true;
    }
}
