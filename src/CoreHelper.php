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

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Manager;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Exception;

class CoreHelper
{
    protected string $smilies_dir = 'smilies';

    protected string $smilies_file_name = 'smilies.txt';

    protected string $smilies_desc_file ;

    public string $smilies_base_url;

    public string $smilies_path;

    /**
     * @var array<string>
     */
    public array $smilies_config = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $smilies_list = [];

    public Manager $filemanager;

    /**
     * @var array<string, array<string, string>>
     */
    public array $files_list = [];

    /**
     * @var array<string, array<string, string>>
     */
    public array $images_list = [];

    public function __construct()
    {
        $smi = My::settings();
        $sys = App::blog()->settings()->system;

        $this->smilies_desc_file = App::blog()->themesPath() . '/' . $sys->theme . '/' . $this->smilies_dir . '/' . $this->smilies_file_name;
        $this->smilies_base_url  = $sys->themes_url . '/' . $sys->theme . '/' . $this->smilies_dir . '/';
        $this->smilies_path      = App::blog()->themesPath() . '/' . $sys->theme . '/' . $this->smilies_dir;
        if (($config = unserialize((string) $smi->smilies_toolbar)) !== false && is_array($config)) {
            $this->smilies_config = $config;
        }
    }

    /**
     * Gets the smilies.
     *
     * @return     array<int, array<string, mixed>>  The smilies.
     */
    public function getSmilies(): array
    {
        if (file_exists($this->smilies_desc_file)) {
            $rule = file($this->smilies_desc_file);
            if ($rule !== false) {
                foreach ($rule as $v) {
                    $v = trim($v);
                    if (preg_match('|^([^\t]*)[\t]+(.*)$|', $v, $m)) {
                        $this->smilies_list[] = [
                            'code'       => $m[1],
                            'name'       => $m[2],
                            'onSmilebar' => in_array($m[1], $this->smilies_config)];
                    }
                }
            }
        }

        return $this->smilies_list;
    }

    /**
     * Sets the smilies.
     *
     * @param      array<int, array<string, mixed>>      $smilies  The smilies
     *
     * @throws     Exception
     *
     * @return     bool
     */
    public function setSmilies(array $smilies): bool
    {
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

                return true;
            } catch (Exception) {
                throw new Exception(sprintf(__('Unable to write file %s. Please check your theme file and folders permissions.'), $this->smilies_desc_file));
            }
        }

        return false;
    }

    /**
     * Sets the configuration.
     *
     * @param      array<int, array<string, mixed>>      $smilies  The smilies
     *
     * @return     bool
     */
    public function setConfig(array $smilies): bool
    {
        $config = [];
        foreach ($smilies as $smiley) {
            if ($smiley['onSmilebar']) {
                $config[] = $smiley['code'];
            }
        }

        My::settings()->put('smilies_toolbar', serialize($config), 'string');

        App::blog()->triggerBlog();

        return true;
    }

    /**
     * Uploads a smile.
     *
     * @param      string     $tmp    The temporary uploaded file
     * @param      string     $name   The filename
     *
     * @throws     Exception
     *
     * @return     string|void
     */
    public function uploadSmile(string $tmp, string $name)
    {
        $name = Files::tidyFileName($name);

        $file = $this->filemanager->uploadFile($tmp, $name);

        $type = Files::getMimeType($name);

        if (($type == 'image/jpeg' || $type == 'image/png')) {
            $s = getimagesize($file);
            if ($s === false) {
                $this->filemanager->removeItem($name);

                throw new Exception(__('Unable to get image size.'));
            } elseif ($s[0] > 24 || $s[1] > 24) {
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

    public function getFiles(): void
    {
        try {
            $this->filemanager = new Manager($this->smilies_path, $this->smilies_base_url);
            $this->filemanager->getDir();
            foreach ($this->filemanager->getFiles() as $v) {
                if ($v->basename !== $this->smilies_file_name) {
                    $this->files_list[$v->basename] = [$v->basename => 'name',  $v->file_url => 'url', $v->type => 'type'];

                    if (preg_match('/^(image)(.+)$/', (string) $v->type) !== false) {
                        $this->images_list[$v->basename] = ['name' => $v->basename,  'url' => $v->file_url];
                    }
                }
            }
        } catch (Exception) {
            throw new Exception(sprintf(__('Active theme does not have required subfolder <code>%s</code>.'), $this->smilies_dir));
        }
    }

    public function createDir(): void
    {
        try {
            Files::makeDir(App::blog()->themesPath() . '/' . App::blog()->settings()->system->theme . '/' . Path::clean($this->smilies_dir));
        } catch (Exception) {
            throw new Exception(sprintf(__('Unable to create subfolder %s in your theme. Please check your folder permissions.'), $this->smilies_dir));
        }
    }

    public function loadAllSmilies(string $zip_file): bool
    {
        $zip = new Unzip($zip_file);
        $zip->getList(false, '#(^|/)(__MACOSX|\.directory|\.svn|\.DS_Store|Thumbs\.db)(/|$)#');

        $zip_root_dir = $zip->getRootDir();

        $define = '';
        $target = dirname($zip_file);
        if ($zip_root_dir !== false) {
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
