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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class smiliesEditor
{
    protected $smilies_dir       = 'smilies';
    protected $smilies_file_name = 'smilies.txt';

    protected $smilies_desc_file ;

    public $smilies_base_url;
    public $smilies_path;

    public $smilies_config;
    public $smilies_list = [];

    public $filemanager;
    public $files_list = [];

    public $images_list = [];

    public function __construct()
    {
        $smi = & dcCore::app()->blog->settings->smilieseditor;
        $sys = & dcCore::app()->blog->settings->system;

        $this->smilies_desc_file = dcCore::app()->blog->themes_path . '/' . $sys->theme . '/' . $this->smilies_dir . '/' . $this->smilies_file_name;
        $this->smilies_base_url  = $sys->themes_url . '/' . $sys->theme . '/' . $this->smilies_dir . '/';
        $this->smilies_path      = dcCore::app()->blog->themes_path . '/' . $sys->theme . '/' . $this->smilies_dir;
        $this->smilies_config    = unserialize($smi->smilies_toolbar);
    }

    public function getSmilies()
    {
        if (file_exists($this->smilies_desc_file)) {
            $rule = file($this->smilies_desc_file);

            foreach ($rule as $v) {
                $v = trim($v);
                if (preg_match('|^([^\t]*)[\t]+(.*)$|', $v, $m)) {
                    $this->smilies_list[] = [
                        'code'       => $m[1],
                        'name'       => $m[2],
                        'onSmilebar' => !is_array($this->smilies_config) || in_array($m[1], $this->smilies_config)];
                }
            }
        }

        return $this->smilies_list;
    }

    public function setSmilies($smilies)
    {
        if (is_array($smilies)) {
            if (!is_writable($this->smilies_path)) {
                throw new Exception(__('Configuration file is not writable.'));
            }

            if (is_writable($this->smilies_desc_file) || !file_exists($this->smilies_desc_file)) {
                try {
                    $fp = @fopen($this->smilies_desc_file, 'wb');
                    if (!$fp) {
                        throw new Exception('tocatch');
                    }
                    $fcontent = '';

                    foreach ($smilies as $smiley) {
                        $fcontent .= $smiley['code'] . "\t\t" . $smiley['name'] . "\r\n";
                    }
                    fwrite($fp, $fcontent);
                    fclose($fp);
                } catch (Exception $e) {
                    throw new Exception(sprintf(__('Unable to write file %s. Please check your theme file and folders permissions.'), $this->smilies_desc_file));
                }
            }
        }

        return false;
    }

    public function setConfig($smilies)
    {
        if (is_array($smilies)) {
            $config = [];

            foreach ($smilies as $smiley) {
                if ($smiley['onSmilebar']) {
                    $config[] = $smiley['code'];
                }
            }
            $s = dcCore::app()->blog->settings->smilieseditor;
            $s->put('smilies_toolbar', serialize($config), 'string');

            dcCore::app()->blog->triggerBlog();

            return true;
        }

        return false;
    }

    public function uploadSmile($tmp, $name)
    {
        $name = files::tidyFileName($name);

        $file = $this->filemanager->uploadFile($tmp, $name);

        $type = files::getMimeType($name);

        if (($type == 'image/jpeg' || $type == 'image/png')) {
            $s = getimagesize($file);
            if ($s[0] > 24 || $s[1] > 24) {
                $this->filemanager->removeItem($name);

                throw new Exception(__('Uploaded image is too big (height or width > 24px).'));
            }

            return $name;
        } elseif ($type == 'application/zip') {
            try {
                $this->loadAllSmilies($file);
            } catch (Exception $e) {
                $this->filemanager->removeItem($name);

                throw $e;
            }

            return;
        }

        $this->filemanager->removeItem($name);

        throw new Exception(sprintf(__('This file %s is not an image. It would be difficult to use it for a smiley.'), $name));
    }

    public function getFiles()
    {
        try {
            $this->filemanager = new filemanager($this->smilies_path, $this->smilies_base_url);
            $this->filemanager->getDir();
            foreach ($this->filemanager->dir['files'] as $k => $v) {
                $this->files_list[$v->basename] = [$v->basename => 'name',  $v->file_url => 'url', $v->type => 'type'];

                if (preg_match('/^(image)(.+)$/', $v->type)) {
                    $this->images_list[$v->basename] = ['name' => $v->basename,  'url' => $v->file_url];
                }
            }
        } catch (Exception $e) {
            throw new Exception(sprintf(__('Active theme does not have required subfolder <code>%s</code>.'), $this->smilies_dir));
        }
    }

    public function createDir()
    {
        try {
            files::makeDir(dcCore::app()->blog->themes_path . '/' . dcCore::app()->blog->settings->system->theme . '/' . path::clean($this->smilies_dir));
        } catch (Exception $e) {
            throw new Exception(sprintf(__('Unable to create subfolder %s in your theme. Please check your folder permissions.'), $this->smilies_dir));
        }
    }

    public function loadAllSmilies($zip_file)
    {
        $zip = new fileUnzip($zip_file);
        $zip->getList(false, '#(^|/)(__MACOSX|\.directory|\.svn|\.DS_Store|Thumbs\.db)(/|$)#');

        $zip_root_dir = $zip->getRootDir();

        $define      = '';
        $target      = dirname($zip_file);
        $destination = $target;
        if ($zip_root_dir != false) {
            $define     = $zip_root_dir . '/' . $this->smilies_file_name;
            $has_define = $zip->hasFile($define);
        } else {
            $define     = $this->smilies_file_name;
            $has_define = $zip->hasFile($define);
        }
        if ($zip->isEmpty()) {
            $zip->close();
            unlink($zip_file);

            throw new Exception(__('Empty smilies zip file.'));
        }

        if (!$has_define) {
            $zip->close();
            unlink($zip_file);

            throw new Exception(__('The zip file does not appear to be a valid Dotclear smilies package.'));
        }

        $zip->unzipAll($target);
        $zip->close();
        unlink($zip_file);

        return true;
    }
}
