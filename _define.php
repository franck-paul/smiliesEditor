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
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Smilies Editor',
        'Smilies Editor',
        'Osku and contributors',
        '8.0',
        [
            'date'        => '2026-08-03T10:10:40+0200',
            'requires'    => [['core', '2.39']],
            'permissions' => 'My',
            'type'        => 'plugin',

            'details'    => 'https://dotclear.org/plugin/detail/smiliesEditor',
            'support'    => 'https://github.com/franck-paul/smiliesEditor',
            'repository' => 'https://raw.githubusercontent.com/franck-paul/smiliesEditor/main/dcstore.xml',
            'license'    => 'gpl2',
        ]
    );
}
