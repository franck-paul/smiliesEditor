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
$this->registerModule(
    'smiliesEditor',
    'Smilies Editor',
    'Osku and contributors',
    '4.1',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',

        'details'    => 'https://plugins.dotaddict.org/dc2/details/smiliesEditor',
        'support'    => 'https://github.com/franck-paul/smiliesEditor',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/smiliesEditor/master/dcstore.xml',
    ]
);
