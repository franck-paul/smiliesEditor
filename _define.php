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
    '5.2',
    [
        'date'        => '2025-05-15T18:30:13+0200',
        'requires'    => [['core', '2.34']],
        'permissions' => 'My',
        'type'        => 'plugin',

        'details'    => 'https://plugins.dotaddict.org/dc2/details/smiliesEditor',
        'support'    => 'https://github.com/franck-paul/smiliesEditor',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/smiliesEditor/main/dcstore.xml',
        'license'    => 'gpl2',
    ]
);
