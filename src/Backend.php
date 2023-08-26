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

        dcCore::app()->addBehavior('adminPreferencesForm', BackendBehaviors::adminUserForm(...));
        dcCore::app()->addBehavior('adminUserForm', BackendBehaviors::adminUserForm(...));
        dcCore::app()->addBehavior('adminBeforeUserCreate', BackendBehaviors::setSmiliesDisplay(...));
        dcCore::app()->addBehavior('adminBeforeUserUpdate', BackendBehaviors::setSmiliesDisplay(...));

        if (dcCore::app()->auth->getOption('smilies_editor_admin')) {
            dcCore::app()->addBehavior('adminPostHeaders', BackendBehaviors::adminPostHeaders(...));
            dcCore::app()->addBehavior('adminPageHeaders', BackendBehaviors::adminPostHeaders(...));
            dcCore::app()->addBehavior('adminRelatedHeaders', BackendBehaviors::adminPostHeaders(...));
            dcCore::app()->addBehavior('adminDashboardHeaders', BackendBehaviors::adminPostHeaders(...));
        }

        return true;
    }
}
