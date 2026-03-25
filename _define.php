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
    'Smilies Editor',
    'Smilies Editor',
    'Osku and contributors',
    '7.6',
    [
        'date'        => '2026-03-25T16:31:41+0100',
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'type'        => 'plugin',

        'details'    => 'https://dotclear.org/plugin/detail/smiliesEditor',
        'support'    => 'https://github.com/franck-paul/smiliesEditor',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/smiliesEditor/main/dcstore.xml',
        'license'    => 'gpl2',
    ]
);
