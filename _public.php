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
if (!defined('DC_RC_PATH')) {
    return;
}

$s = dcCore::app()->blog->settings->smilieseditor;

dcCore::app()->addBehavior('publicFooterContent', ['smiliesBehavior','publicFooterContent']);
dcCore::app()->addBehavior('publicCommentFormAfterContent', ['smiliesBehavior','publicFormAfterContent']);
dcCore::app()->addBehavior('publicAnswerFormAfterContent', ['smiliesBehavior','publicFormAfterContent']);
dcCore::app()->addBehavior('publicEditFormAfter', ['smiliesBehavior','publicFormAfterContent']);
dcCore::app()->addBehavior('publicEntryFormAfter', ['smiliesBehavior','publicFormAfterContent']);
dcCore::app()->addBehavior('publicEditEntryFormAfter', ['smiliesBehavior','publicFormAfterContent']);

if ($s->smilies_preview_flag) {
    dcCore::app()->addBehavior('publicBeforeCommentPreview', ['smiliesBehavior','publicBeforePreview']);
    dcCore::app()->addBehavior('publicBeforePostPreview', ['smiliesBehavior','publicBeforePostPreview']);
    dcCore::app()->addBehavior('publicBeforeMessagePreview', ['smiliesBehavior','publicBeforeMessagePreview']);
}

class smiliesBehavior
{
    public static function publicFooterContent()
    {
        $use_smilies      = (bool) dcCore::app()->blog->settings->system->use_smilies;
        $smilies_bar_flag = (bool) dcCore::app()->blog->settings->smilieseditor->smilies_bar_flag;

        if ($smilies_bar_flag && $use_smilies) {
            $js = html::stripHostURL(dcCore::app()->blog->getQmarkURL() . 'pf=smiliesEditor/js/smile.js');
            echo "\n" . '<script type="text/javascript" src="' . $js . '"></script>' . "\n";
        } else {
            return;
        }
    }

    public static function publicFormAfterContent()
    {
        $use_smilies      = (bool) dcCore::app()->blog->settings->system->use_smilies;
        $smilies_bar_flag = (bool) dcCore::app()->blog->settings->smilieseditor->smilies_bar_flag;
        $public_text      = dcCore::app()->blog->settings->smilieseditor->smilies_public_text;

        if (!$smilies_bar_flag || !$use_smilies) {
            return;
        }

        $sE      = new smiliesEditor();
        $smilies = $sE->getSmilies();
        $field   = '<p class="field smilies"><label>' . html::escapeHTML($public_text) . '&nbsp;:</label><span>%s</span></p>';

        $res = '';
        foreach ($smilies as $smiley) {
            if ($smiley['onSmilebar']) {
                $res .= ' <img class="smiley" src="' . $sE->smilies_base_url . $smiley['name'] . '" alt="' .
                html::escapeHTML($smiley['code']) . '" title="' . html::escapeHTML($smiley['code']) . '" onclick="javascript:InsertSmiley(\'c_content\', \'' .
                html::escapeHTML($smiley['code']) . ' \');" style="cursor:pointer;" />';
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

    public static function publicBeforePostPreview()
    {
        dcCore::app()->public->smilies              = context::getSmilies(dcCore::app()->blog);
        dcCore::app()->ctx->post_preview['content'] = context::addSmilies(dcCore::app()->ctx->post_preview['content']);
        dcCore::app()->ctx->post_preview['excerpt'] = context::addSmilies(dcCore::app()->ctx->post_preview['excerpt']);
    }

    public static function publicBeforeMessagePreview()
    {
        dcCore::app()->public->smilies                 = context::getSmilies(dcCore::app()->blog);
        dcCore::app()->ctx->message_preview['content'] = context::addSmilies(dcCore::app()->ctx->message_preview['content']);
    }
}
