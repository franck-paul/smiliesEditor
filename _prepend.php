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

use Dotclear\Helper\Clearbricks;

Clearbricks::lib()->autoload(['smiliesEditor' => __DIR__ . '/inc/class.smilies.editor.php']);
