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

        App::behavior()->addBehavior('publicFooterContent', FrontendBehaviors::publicFooterContent(...));
        App::behavior()->addBehavior('publicCommentFormAfterContent', FrontendBehaviors::publicFormAfterContent(...));
        App::behavior()->addBehavior('publicAnswerFormAfterContent', FrontendBehaviors::publicFormAfterContent(...));
        App::behavior()->addBehavior('publicEditFormAfter', FrontendBehaviors::publicFormAfterContent(...));
        App::behavior()->addBehavior('publicEntryFormAfter', FrontendBehaviors::publicFormAfterContent(...));
        App::behavior()->addBehavior('publicEditEntryFormAfter', FrontendBehaviors::publicFormAfterContent(...));

        $settings = My::settings();
        if ($settings->smilies_preview_flag) {
            App::behavior()->addBehavior('publicBeforeCommentPreview', FrontendBehaviors::publicBeforeCommentPreview(...));
        }

        return true;
    }
}
