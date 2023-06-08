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

use context;
use dcCore;
use Dotclear\Helper\Html\Html;

class FrontendBehaviors
{
    public static function publicFooterContent()
    {
        $settings = dcCore::app()->blog->settings->get(My::id());

        $use_smilies      = (bool) dcCore::app()->blog->settings->system->use_smilies;
        $smilies_bar_flag = (bool) $settings->smilies_bar_flag;

        if ($smilies_bar_flag && $use_smilies) {
            $js = Html::stripHostURL(dcCore::app()->blog->getQmarkURL() . 'pf=smiliesEditor/js/smile.js');
            echo "\n" . '<script type="text/javascript" src="' . $js . '"></script>' . "\n";
        } else {
            return;
        }
    }

    public static function publicFormAfterContent()
    {
        $settings = dcCore::app()->blog->settings->get(My::id());

        $use_smilies      = (bool) dcCore::app()->blog->settings->system->use_smilies;
        $smilies_bar_flag = (bool) $settings->smilies_bar_flag;
        $public_text      = $settings->smilies_public_text;

        if (!$smilies_bar_flag || !$use_smilies) {
            return;
        }

        $sE      = new CoreHelper();
        $smilies = $sE->getSmilies();
        $field   = '<p class="field smilies"><label>' . Html::escapeHTML($public_text) . '&nbsp;:</label><span>%s</span></p>';

        $res = '';
        foreach ($smilies as $smiley) {
            if ($smiley['onSmilebar']) {
                $res .= ' <img class="smiley" src="' . $sE->smilies_base_url . $smiley['name'] . '" alt="' .
                Html::escapeHTML($smiley['code']) . '" title="' . Html::escapeHTML($smiley['code']) . '" onclick="javascript:InsertSmiley(\'c_content\', \'' .
                Html::escapeHTML($smiley['code']) . ' \');" style="cursor:pointer;" />';
            }
        }

        if ($res != '') {
            echo sprintf($field, $res);
        }
    }

    public static function publicBeforeCommentPreview()
    {
        dcCore::app()->public->smilies                 = context::getSmilies(dcCore::app()->blog);
        dcCore::app()->ctx->comment_preview['content'] = context::addSmilies(dcCore::app()->ctx->comment_preview['content']);
    }
}
