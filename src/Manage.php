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
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class Manage
{
    use TraitProcess;

    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $settings = My::settings();

        // Get smilies code
        $smilies_editor = new CoreHelper();

        /**
         * @var array<int, array<string, mixed>>
         */
        $smilies = $smilies_editor->getSmilies();

        $theme = App::blog()->settings()->system->theme;

        if (!empty($_POST['create_dir'])) {
            try {
                $smilies_editor->createDir();
                My::redirect();
                App::backend()->notices()->addSuccessNotice(__('The subfolder has been successfully created'));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                $show     = !empty($_POST['smilies_bar_flag']);
                $preview  = !empty($_POST['smilies_preview_flag']);
                $formtext = (empty($_POST['smilies_public_text'])) ? __('Smilies') : $_POST['smilies_public_text'];

                $settings->put('smilies_bar_flag', $show, App::blogWorkspace()::NS_BOOL, 'Show smilies toolbar');
                $settings->put('smilies_preview_flag', $preview, App::blogWorkspace()::NS_BOOL, 'Show smilies on preview');
                $settings->put('smilies_public_text', $formtext, App::blogWorkspace()::NS_STRING, 'Smilies displayed in toolbar');

                App::blog()->triggerBlog();
                App::backend()->notices()->addSuccessNotice(__('Configuration successfully updated.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Delete all unused images
        if (!empty($_POST['rm_unused_img'])) {
            try {
                // Create array of used smilies filename
                $smileys_list = [];
                foreach ($smilies as $v) {
                    $smileys_list = array_merge($smileys_list, [$v['name'] => $v['name']]);
                }

                $smilies_editor->getFiles();
                foreach ($smilies_editor->images_list as $v) {
                    if (!array_key_exists($v['name'], $smileys_list)) {
                        try {
                            $smilies_editor->filemanager->removeItem($v['name']);
                        } catch (Exception $e) {
                            App::error()->add($e->getMessage());
                        }
                    }
                }

                App::backend()->notices()->addSuccessNotice(__('Unused images have been successfully removed.'));
                My::redirect([
                    'dircleaned' => 1,
                ]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_FILES['upfile'])) {
            try {
                $file = null;
                Files::uploadStatus($_FILES['upfile']);
                $file = $smilies_editor->uploadSmile($_FILES['upfile']['tmp_name'], $_FILES['upfile']['name']);
                if ($file) {
                    App::backend()->notices()->addSuccessNotice(sprintf(__('The image <em>%s</em> has been successfully uploaded.'), Html::escapeHTML($_FILES['upfile']['name'])));
                    My::redirect();
                } else {
                    App::backend()->notices()->addSuccessNotice(__('A smilies zip package has been successfully installed.'));
                    My::redirect();
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['saveorder'])) {
            $order = [];
            if (empty($_POST['smilies_order']) && !empty($_POST['order'])) {
                $order = $_POST['order'];
                asort($order);
                $order = array_keys($order);
            } elseif (!empty($_POST['smilies_order'])) {
                $order = explode(',', (string) $_POST['smilies_order']);
            }

            if ($order !== []) {
                try {
                    /**
                     * @var array<int, array<string, mixed>>
                     */
                    $new_smilies = [];
                    foreach ($order as $v) {
                        $new_smilies[(int) $v] = $smilies[(int) $v];
                    }

                    $smilies_editor->setSmilies($new_smilies);
                    App::backend()->notices()->addSuccessNotice(__('Order of smilies has been successfully changed.'));
                    My::redirect();
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        if (!empty($_POST['actionsmilies']) && !empty($_POST['select'])) {
            $action = $_POST['actionsmilies'];

            switch ($action) {
                case 'clear':
                    try {
                        foreach ($_POST['select'] as $v) {
                            unset($smilies[$v]);
                        }

                        $smilies_editor->setSmilies($smilies);
                        $smilies_editor->setConfig($smilies);
                        App::backend()->notices()->addSuccessNotice(__('Smilies has been successfully removed.'));
                        My::redirect();
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }

                    break;

                case 'update':
                    try {
                        foreach ($_POST['select'] as $v) {
                            $smilies[(int) $v]['code'] = isset($_POST['code'][$v]) ? preg_replace('/[\s]+/', '', (string) $_POST['code'][$v]) : $smilies[(int) $v]['code'] ;
                            $smilies[(int) $v]['name'] = $_POST['name'][$v] ?? $smilies[$v]['name'];
                        }

                        $smilies_editor->setSmilies($smilies);
                        $smilies_editor->setConfig($smilies);
                        App::backend()->notices()->addSuccessNotice(__('Smilies has been successfully updated.'));
                        My::redirect();
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }

                    break;

                case 'display':
                    try {
                        foreach ($_POST['select'] as $v) {
                            $smilies[(int) $v]['onSmilebar'] = true;
                        }

                        $smilies_editor->setConfig($smilies);
                        App::backend()->notices()->addSuccessNotice(__('These selected smilies are now displayed on toolbar'));
                        My::redirect();
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }

                    break;

                case 'hide':
                    try {
                        foreach ($_POST['select'] as $v) {
                            $smilies[(int) $v]['onSmilebar'] = false;
                        }

                        $smilies_editor->setConfig($smilies);
                        App::backend()->notices()->addSuccessNotice(__('These selected smilies are now hidden on toolbar.'));
                        My::redirect();
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }

                    break;
            }
        }

        if (!empty($_POST['smilecode']) && !empty($_POST['smilepic'])) {
            try {
                $count = count($smilies);

                $smilies[$count]['code'] = preg_replace('/[\s]+/', '', (string) $_POST['smilecode']);
                $smilies[$count]['name'] = $_POST['smilepic'];

                $smilies_editor->setSmilies($smilies);
                App::backend()->notices()->addSuccessNotice(__('A new smiley has been successfully created'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Zip download
        if (!empty($_GET['zipdl'])) {
            try {
                @set_time_limit(300);
                $fp  = fopen('php://output', 'wb');
                $zip = new Zip($fp);
                $zip->addDirectory(App::themes()->moduleInfo($theme, 'root') . '/smilies', '', true);
                header('Content-Disposition: attachment;filename=smilies-' . $theme . '.zip');
                header('Content-Type: application/x-zip');

                $zip->write();
                unset($zip);
                exit;
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $settings = My::settings();
        $theme    = App::blog()->settings()->system->theme;
        $ordering = App::auth()->isSuperAdmin() && !in_array($theme, explode(',', (string) App::config()->distributedThemes()));

        // Init
        $smg_writable = false;

        $actions   = [];
        $actions[] = (new Optgroup(__('Toolbar')))
            ->items([
                (new Option(__('display'), 'display')),
                (new Option(__('hide'), 'hide')),
            ]);
        if ($ordering) {
            $actions[] = (new Optgroup(__('Definition')))
                ->items([
                    (new Option(__('update'), 'update')),
                    (new Option(__('delete'), 'clear')),
                ]);
        }

        $smilies_bar_flag     = (bool) $settings->smilies_bar_flag;
        $smilies_preview_flag = (bool) $settings->smilies_preview_flag;
        $smilies_public_text  = $settings->smilies_public_text;

        // Get theme Infos
        App::themes()->loadModules(App::blog()->themesPath(), null);
        $theme_define = App::themes()->getDefine($theme);
        $theme_name   = $theme_define->get('name');

        // Get smilies code
        $smilies_editor = new CoreHelper();
        $smilies        = $smilies_editor->getSmilies();

        // Init the filemanager
        try {
            $smilies_editor->getFiles();
            $smg_writable = $smilies_editor->filemanager->writable();
        } catch (Exception $exception) {
            App::backend()->notices()->addWarningNotice($exception->getMessage());
        }

        // Create array of used smilies filename
        $smileys_list = [];
        foreach ($smilies as $k => $v) {
            $smileys_list = array_merge($smileys_list, [$v['name'] => $v['name']]);
        }

        // Create the combo of all images available in directory
        $smileys_combo = [];
        foreach ($smilies_editor->images_list as $k => $v) {
            $smileys_combo = array_merge($smileys_combo, [$v['name'] => $v['name']]);
        }

        $images_all = $smilies_editor->images_list;
        foreach ($smilies_editor->images_list as $k => $v) {
            if (array_key_exists($v['name'], $smileys_list)) {
                unset($smilies_editor->images_list[$k]);
            }
        }

        $head = App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js') .
        App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js') .
        App::backend()->page()->jsJson('smilies', [
            'smilies_base_url'     => App::blog()->host() . $smilies_editor->smilies_base_url,
            'confirm_image_delete' => sprintf(__('Are you sure you want to remove these %s ?'), 'images'),
        ]) .
        My::jsLoad('_smilies.js') .
        My::cssLoad('admin.css');

        App::backend()->page()->openModule(My::name(), $head);

        echo App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Smilies Editor')                  => '',
            ]
        );
        echo App::backend()->notices()->getNotices();

        // Form
        $items = [];

        // Mandatory fields note
        $items[] = (new Note())
            ->class('form-note')
            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render()));

        // Current theme information
        $items[] = (new Note())
            ->text(sprintf(
                __('Your <a href="%s">current theme</a> on this blog is "%s".'),
                App::backend()->url()->get('admin.blog.theme'),
                (new Strong(Html::escapeHTML($theme_name)))->render()
            ));

        if ($smilies === []) {
            if (!empty($smilies_editor->filemanager)) {
                $items[] = (new Note())
                    ->class(['form-note', 'info'])
                    ->text(__('No defined smiley yet.'));
            }
        } else {
            // Configuration (may go in secondary tab in future)
            $items[] = (new Div('smilies_options'))
                ->class('clear')
                ->items([
                    (new Form('form_smilies_options'))
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('Configuration')))
                                ->fields([
                                    (new Para())
                                        ->items([
                                            (new Checkbox('smilies_bar_flag', $smilies_bar_flag))
                                                ->value(1)
                                                ->label(new Label(__('Show toolbar smilies in comments form'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('smilies_preview_flag', $smilies_preview_flag))
                                                ->value(1)
                                                ->label(new Label(__('Show images on preview'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Input('smilies_public_text'))
                                                ->size(50)
                                                ->maxlength(255)
                                                ->default(Html::escapeHTML($smilies_public_text))
                                                ->label((new Label((new Span('*'))->render() . __('Comments form label:'), Label::IL_TF))
                                                    ->class('required')),
                                        ]),
                                    (new Note())
                                        ->class('form-note')
                                        ->text(sprintf(
                                            __('Don\'t forget to <a href="%s">display smilies</a> on your blog configuration.'),
                                            App::backend()->url()->get('admin.blog.pref') . '#params.use_smilies'
                                        )),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ... My::hiddenFields(),
                                            (new Submit('saveconfig', __('Save'))),
                                        ]),

                                ]),
                        ]),
                ]);

            $rows = function () use ($smilies, $smileys_combo, $ordering) {
                foreach ($smilies as $key => $value) {
                    if ($value['onSmilebar']) {
                        $class  = '';
                        $status = (new Img('images/published.svg'))
                            ->alt(__('displayed'))
                            ->title(__('displayed'))
                            ->class(['mark', 'mark-published']);
                    } else {
                        $class  = 'offline';
                        $status = (new Img('images/unpublished.svg'))
                            ->alt(__('undisplayed'))
                            ->title(__('undisplayed'))
                            ->class(['mark', 'mark-unpublished']);
                    }

                    yield (new Tr('l_' . $key))
                        ->class(['line', $class])
                        ->cols([
                            $ordering ?
                            (new Td())
                                ->class(['handle', 'minimal'])
                                ->items([
                                    (new Input(['order[' . $key . ']']))
                                        ->size(2)
                                        ->maxlength(5)
                                        ->default($key)
                                        ->class('position'),
                                ]) :
                            (new None()),
                            (new Td())
                                ->class(['minimal', 'status'])
                                ->items([
                                    (new Checkbox(['select[]']))
                                        ->value($key),
                                ]),
                            (new Td())
                                ->class('minimal')
                                ->items([
                                    (new Input(['code[]','c' . $key]))
                                        ->size(20)
                                        ->maxlength(255)
                                        ->default(Html::escapeHTML($value['code']))
                                        ->disabled(!$ordering),
                                ]),
                            (new Td())
                                ->class(['nowrap', 'status'])
                                ->items([
                                    (new Select(['name[]','n' . $key]))
                                        ->class('emote')
                                        ->items($smileys_combo)
                                        ->default($value['name'])
                                        ->disabled(!$ordering),
                                    $status,
                                ]),
                        ]);
                }
            };

            $items[] = (new Form('smilies-form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields([
                    (new Text('h3', __('Smilies set'))),
                    (new Table())
                        ->class(['maximal', 'dragable'])
                        ->thead((new Thead())
                            ->rows([
                                (new Tr())
                                    ->cols([
                                        (new Th())
                                            ->colspan($ordering ? 3 : 2)
                                            ->text(__('Code')),
                                        (new Th())
                                            ->text(__('Image')),
                                    ]),
                            ]))
                        ->tbody((new Tbody('smilies-list'))
                            ->rows([
                                ... $rows(),
                            ])),
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Para())
                                ->class(['col', 'checkboxes-helpers']),
                            (new para())
                                ->class(['col', 'right', 'form-buttons'])
                                ->items([
                                    (new Select('actionsmilies'))
                                        ->items($actions)
                                        ->label(new Label(__('Selected smilies action:'), Label::IL_TF)),
                                    (new Submit('actionsmilies_submit', __('Ok'))),
                                    ... My::hiddenFields(),
                                ]),
                            (new Para())
                                ->items([
                                    $ordering ?
                                    (new Submit('saveorder', __('Save order'))) :
                                    (new None()),
                                ]),
                        ]),
                ]);
        }

        $forms = [];
        if ($images_all === []) {
            if (empty($smilies_editor->filemanager)) {
                $forms[] = (new Div())
                    ->class('col')
                    ->items([
                        (new Form('dir_form'))
                            ->method('post')
                            ->action(App::backend()->getPageURL())
                            ->fields([
                                (new Para())
                                    ->items([
                                        ... My::hiddenFields(),
                                        (new Submit('create_dir', __('Initialize'))),
                                    ]),
                            ]),
                    ]);
            }
        } elseif ($ordering) {
            $forms[] = (new Div())
                ->class('col')
                ->items([
                    (new Form('add-smiley-form'))
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('New smiley')))
                                ->fields([
                                    (new Para())
                                        ->items([
                                            (new Select('smilepic'))
                                                ->items($smileys_combo)
                                                ->label((new Label((new Span('*'))->render() . __('Image:'), Label::IL_TF))
                                                    ->class('required')),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Input('smilecode'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->label((new Label((new Span('*'))->render() . __('Code:'), Label::IL_TF))
                                                    ->class('required')),
                                        ]),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ... My::hiddenFields(),
                                            (new Submit('add_message', __('Create'))),
                                        ]),
                                ]),
                        ]),
                ]);
        }

        if ($smg_writable && $ordering) {
            $forms[] = (new Div())
                ->class('col')
                ->items([
                    (new Form('upl-smile-form'))
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->enctype('multipart/form-data')
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('New image')))
                                ->fields([
                                    (new Para())
                                        ->items([
                                            (new File('upfile'))
                                                ->size(20)
                                                ->label(new Label(__('Choose a file:') . ' (' . sprintf(__('Maximum size %s'), Files::size((int) App::config()->maxUploadSize())) . ')', Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ... My::hiddenFields([
                                                'MAX_FILE_SIZE' => (string) App::config()->maxUploadSize(),
                                            ]),
                                            (new Hidden(['d'], null)),
                                            (new Submit('upfile-submit', __('Send'))),
                                        ]),
                                ]),
                        ]),
                ]);
        }

        if ($images_all !== [] && $ordering && $smilies_editor->images_list !== []) {
            $list = function () use ($smilies_editor) {
                foreach ($smilies_editor->images_list as $value) {
                    yield (new Img(App::blog()->host() . $value['url']))
                        ->alt($value['name'])
                        ->class('emote')
                        ->title($value['name']);
                }
            };

            $forms[] = (new Div())
                ->class('col')
                ->items([
                    (new Form('del_form'))
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('Unused smilies')))
                                ->fields([
                                    (new Para())
                                        ->separator(' ')
                                        ->items([
                                            ... $list(),
                                        ]),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ... My::hiddenFields(),
                                            (new Submit('rm_unused_img', __('Delete'))),
                                        ]),
                                ]),
                        ]),
                ]);
        }

        $items[] = (new Div())
            ->class('three-cols')
            ->items($forms);

        if ($images_all !== []) {
            $items[] = (new Para())
                ->class(['zip-dl', 'clear'])
                ->items([
                    (new Link())
                        ->href(App::backend()->getPageURL() . '&zipdl=1')
                        ->text(__('Download the smilies directory as a zip file')),
                ]);
        }

        echo (new Set())
            ->items($items)
        ->render();

        App::backend()->page()->helpBlock('smilieseditor');

        App::backend()->page()->closeModule();
    }
}
