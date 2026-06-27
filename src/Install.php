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
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Exception;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            $old_version = App::version()->getVersion(My::id());
            // Rename settings namespace
            if (version_compare((string) $old_version, '2.0', '<') && App::blog()->settings()->exists('smilieseditor')) {
                App::blog()->settings()->delWorkspace(My::id());
                App::blog()->settings()->renWorkspace('smilieseditor', My::id());
            }

            // Init
            $settings = My::settings();

            $settings->put('smilies_bar_flag', false, BlogWorkspaceInterface::NS_BOOL, 'Show smilies toolbar', false, true);
            $settings->put('smilies_preview_flag', false, BlogWorkspaceInterface::NS_BOOL, 'Show smilies on preview', false, true);
            $settings->put('smilies_toolbar', '', BlogWorkspaceInterface::NS_STRING, 'Smilies displayed in toolbar', false, true);
            $settings->put('smilies_public_text', __('Smilies'), BlogWorkspaceInterface::NS_STRING, 'Smilies displayed in toolbar', false, true);

            if (!App::auth()->prefs()->get('interface')->prefExists('smilies_editor_admin', true)) {
                // Migrate old option if possible
                $format = App::auth()->getOption('smilies_editor_admin') ?? false;
                App::auth()->prefs()->get('interface')->put(
                    'smilies_editor_admin',
                    $format,
                    UserWorkspaceInterface::WS_BOOL,
                    'Display smilies on toolbar',
                    true,
                    true
                );
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }
}
