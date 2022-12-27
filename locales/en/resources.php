<?php

# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of smiliesEditor, a plugin for Dotclear 2.
#
# Copyright (c) 2009, 2010 Osku and contributors
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------
if (!isset(dcCore::app()->resources['help']['smilieseditor'])) {
    dcCore::app()->resources['help']['smilieseditor'] = dirname(__FILE__) . '/help.html';
}
