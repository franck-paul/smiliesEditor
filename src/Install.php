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
use Exception;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::INSTALL);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            $old_version = dcCore::app()->getVersion(My::id());
            if (version_compare((string) $old_version, '2.0', '<')) {
                // Rename settings namespace
                if (dcCore::app()->blog->settings->exists('smilieseditor')) {
                    dcCore::app()->blog->settings->delNamespace(My::id());
                    dcCore::app()->blog->settings->renNamespace('smilieseditor', My::id());
                }
            }

            // Init
            $settings = dcCore::app()->blog->settings->get(My::id());

            $settings->put('smilies_bar_flag', false, dcNamespace::NS_BOOL, 'Show smilies toolbar', false, true);
            $settings->put('smilies_preview_flag', false, dcNamespace::NS_BOOL, 'Show smilies on preview', false, true);
            $settings->put('smilies_toolbar', '', dcNamespace::NS_STRING, 'Smilies displayed in toolbar', false, true);
            $settings->put('smilies_public_text', __('Smilies'), dcNamespace::NS_STRING, 'Smilies displayed in toolbar', false, true);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }
}
