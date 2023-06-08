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

use dcAdmin;
use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::BACKEND);

        // dead but useful code, in order to have translations
        __('smiliesEditor') . __('Smilies Editor');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
            __('Smilies Editor'),
            My::makeUrl(),
            My::icons(),
            preg_match(My::urlScheme(), $_SERVER['REQUEST_URI']),
            My::checkContext(My::MENU)
        );

        dcCore::app()->addBehavior('adminPreferencesForm', [BackendBehaviors::class,'adminUserForm']);
        dcCore::app()->addBehavior('adminUserForm', [BackendBehaviors::class,'adminUserForm']);
        dcCore::app()->addBehavior('adminBeforeUserCreate', [BackendBehaviors::class,'setSmiliesDisplay']);
        dcCore::app()->addBehavior('adminBeforeUserUpdate', [BackendBehaviors::class,'setSmiliesDisplay']);

        if (dcCore::app()->auth->getOption('smilies_editor_admin')) {
            dcCore::app()->addBehavior('adminPostHeaders', [BackendBehaviors::class,'adminPostHeaders']);
            dcCore::app()->addBehavior('adminPageHeaders', [BackendBehaviors::class,'adminPostHeaders']);
            dcCore::app()->addBehavior('adminRelatedHeaders', [BackendBehaviors::class,'adminPostHeaders']);
            dcCore::app()->addBehavior('adminDashboardHeaders', [BackendBehaviors::class,'adminPostHeaders']);
        }

        return true;
    }
}
