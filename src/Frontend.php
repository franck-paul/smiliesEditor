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
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::FRONTEND);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehavior('publicFooterContent', [FrontendBehaviors::class,'publicFooterContent']);
        dcCore::app()->addBehavior('publicCommentFormAfterContent', [FrontendBehaviors::class,'publicFormAfterContent']);
        dcCore::app()->addBehavior('publicAnswerFormAfterContent', [FrontendBehaviors::class,'publicFormAfterContent']);
        dcCore::app()->addBehavior('publicEditFormAfter', [FrontendBehaviors::class,'publicFormAfterContent']);
        dcCore::app()->addBehavior('publicEntryFormAfter', [FrontendBehaviors::class,'publicFormAfterContent']);
        dcCore::app()->addBehavior('publicEditEntryFormAfter', [FrontendBehaviors::class,'publicFormAfterContent']);

        $settings = dcCore::app()->blog->settings->get(My::id());
        if ($settings->smilies_preview_flag) {
            dcCore::app()->addBehavior('publicBeforeCommentPreview', [FrontendBehaviors::class,'publicBeforePreview']);
        }

        return true;
    }
}
