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
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->addBehavior('publicFooterContent', FrontendBehaviors::publicFooterContent(...));
        dcCore::app()->addBehavior('publicCommentFormAfterContent', FrontendBehaviors::publicFormAfterContent(...));
        dcCore::app()->addBehavior('publicAnswerFormAfterContent', FrontendBehaviors::publicFormAfterContent(...));
        dcCore::app()->addBehavior('publicEditFormAfter', FrontendBehaviors::publicFormAfterContent(...));
        dcCore::app()->addBehavior('publicEntryFormAfter', FrontendBehaviors::publicFormAfterContent(...));
        dcCore::app()->addBehavior('publicEditEntryFormAfter', FrontendBehaviors::publicFormAfterContent(...));

        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->smilies_preview_flag) {
            dcCore::app()->addBehavior('publicBeforeCommentPreview', FrontendBehaviors::publicBeforeCommentPreview(...));
        }

        return true;
    }
}
