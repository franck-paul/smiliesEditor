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
use Dotclear\Core\Frontend\Ctx;
use Dotclear\Helper\Html\Html;

class FrontendBehaviors
{
    public static function publicFooterContent(): string
    {
        $settings = My::settings();

        $use_smilies      = App::blog()->settings()->get('system')->getBool('use_smilies', false);
        $smilies_bar_flag = $settings->getBool('smilies_bar_flag', false);

        if ($smilies_bar_flag && $use_smilies) {
            $js = Html::stripHostURL(App::blog()->getQmarkURL() . 'pf=smiliesEditor/js/smile.js');
            echo "\n" . '<script src="' . $js . '"></script>' . "\n";
        }

        return '';
    }

    public static function publicFormAfterContent(): string
    {
        $settings = My::settings();

        $use_smilies      = App::blog()->settings()->get('system')->getBool('use_smilies', false);
        $smilies_bar_flag = $settings->getBool('smilies_bar_flag', false);
        $public_text      = $settings->getStr('smilies_public_text', false);

        if (!$smilies_bar_flag || !$use_smilies) {
            return '';
        }

        $coreHelper = new CoreHelper();
        $smilies    = $coreHelper->getSmilies();
        $field      = '<p class="field smilies"><label>' . Html::escapeHTML($public_text) . '&nbsp;:</label><span>%s</span></p>';

        $res = '';
        foreach ($smilies as $smily) {
            if ($smily['onSmilebar']) {
                $res .= ' <img class="smiley" src="' . $coreHelper->smilies_base_url . $smily['name'] . '" alt="' .
                Html::escapeHTML($smily['code']) . '" title="' . Html::escapeHTML($smily['code']) . '" onclick="javascript:InsertSmiley(\'c_content\', \'' .
                Html::escapeHTML($smily['code']) . ' \');" style="cursor:pointer;">';
            }
        }

        if ($res !== '') {
            echo sprintf($field, $res);
        }

        return '';
    }

    public static function publicBeforeCommentPreview(): string
    {
        if (!isset(App::frontend()->smilies)) {
            $smilies = Ctx::getSmilies(App::blog());
            if ($smilies !== false) {
                App::frontend()->smilies = $smilies;
            }
        }

        if (is_array(App::frontend()->context()->comment_preview)) {
            $content = isset(App::frontend()->context()->comment_preview['content']) && is_string($content = App::frontend()->context()->comment_preview['content']) ? $content : '';

            App::frontend()->context()->comment_preview['content'] = Ctx::addSmilies($content);
        }

        return '';
    }
}
