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

if (!dcCore::app()->newVersion(basename(__DIR__), dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version'))) {
    return;
}

try {
    // New settings
    $s = dcCore::app()->blog->settings->smilieseditor;
    $s->put('smilies_bar_flag', false, 'boolean', 'Show smilies toolbar', true, true);
    $s->put('smilies_preview_flag', false, 'boolean', 'Show smilies on preview', true, true);
    $s->put('smilies_toolbar', '', 'string', 'Smilies displayed in toolbar', true, true);
    $s->put('smilies_public_text', __('Smilies'), 'string', 'Smilies displayed in toolbar', true, true);

    $old_version = dcCore::app()->getVersion(basename(__DIR__));
    if (version_compare((string) $old_version, '1.0', '<')) {
        ; // Update since 1.0 to be completed
    }

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
