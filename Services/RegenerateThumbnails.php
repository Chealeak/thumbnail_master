<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RegenerateThumbnails implements Service
{
    const PLUGIN_PREFIX = 'th_m_';

    public function __construct()
    {
        $this->enqueueScriptsAndStyles();
        $this->setAjaxRegenerationHandler();
    }

    public function register()
    {
        add_action('admin_menu', [$this, 'addAdminPage']);
        add_action('admin_init', [$this, 'adminPageInit']);

        //$this->regenerate();
    }

    public function addAdminPage()
    {
        add_menu_page('Thumbnail Master', 'Thumbnail Master', 'manage_options', self::PLUGIN_PREFIX . 'regenerate_thumbnails', function () {
        });
        add_submenu_page(self::PLUGIN_PREFIX . 'regenerate_thumbnails', 'Regenerate Thumbnails', 'Regenerate Thumbnails', 'manage_options', self::PLUGIN_PREFIX . 'regenerate_thumbnails', function () {
        });

        add_options_page(
            'Settings Admin',
            'My Settings',
            'manage_options',
            self::PLUGIN_PREFIX . 'regenerate_thumbnails',
            [$this, 'renderAdminPage']
        );
    }

    public function renderAdminPage()
    {
        $this->options = get_option('my_option_name');
        ?>
        <div class="wrap">
            <h1>Regenerate Thumbnails Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::PLUGIN_PREFIX . 'option_group');
                do_settings_sections(self::PLUGIN_PREFIX . 'setting-admin');
                submit_button();
                ?>
            </form>
            <button class="<?= self::PLUGIN_PREFIX ?>regenerate-button-js">Regenerate</button>
            <div id="<?= self::PLUGIN_PREFIX ?>progressbar" class="ldBar"></div>
        </div>
        <?php
    }

    public function adminPageInit()
    {
        /*        register_setting(
                    self::PLUGIN_PREFIX . 'option_group',
                    'option_name',
                    [$this, 'sanitizeOptionField']
                );*/

        add_settings_section(
            self::PLUGIN_PREFIX . 'setting_section_analysis',
            'Analysis',
            [$this, 'printAnalysisSectionInfo'],
            self::PLUGIN_PREFIX . 'setting-admin'
        );

        /*        add_settings_field(
                    'title',
                    'Title',
                    [$this, 'title_callback'],
                    'my-setting-admin',
                    'setting_section_id'
                );*/
    }

    public function sanitizeOptionField($input)
    {
        $newInput = [];

        if (isset($input['title'])) {
            $newInput['title'] = sanitize_text_field($input['title']);
        }

        return $newInput;
    }

    public function printAnalysisSectionInfo()
    {
        // add_image_size('test_size', 555, 555, true);

        $table = '<table>';

        $table .= '<tr>';
        $table .= '<th>Name</th>';
        $table .= '<th>Size</th>';
        $table .= '<th>Crop</th>';
        $table .= '</tr>';

        $existedThumbnailsInfo = $this->getExistedThumbnailsInfo();
        foreach ($existedThumbnailsInfo as $thumbnailName => $thumbnailInfo) {
            $table .= '<tr>';
            $table .= "<td>{$thumbnailName}</td>";
            $table .= "<td>{$thumbnailInfo['width']}x{$thumbnailInfo['height']}</td>";
            $table .= "<td>" . ($thumbnailInfo['crop'] ? 'Yes' : 'No') . "</td>";
            $table .= '</tr>';
        }

        $table .= '</table>';

        echo $table;
    }

    /*    public function title_callback()
        {
            printf(
                '<input type="text" id="title" name="my_option_name[title]" value="%s"/>',
                isset($this->options['title']) ? esc_attr($this->options['title']) : ''
            );
        }*/

    private function getExistedThumbnailsInfo()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        foreach (get_intermediate_image_sizes() as $_size) {
            if (in_array($_size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
                $sizes[$_size]['width'] = get_option("{$_size}_size_w");
                $sizes[$_size]['height'] = get_option("{$_size}_size_h");
                $sizes[$_size]['crop'] = (bool)get_option("{$_size}_crop");
            } elseif (isset($_wp_additional_image_sizes[$_size])) {
                $sizes[$_size] = array(
                    'width' => $_wp_additional_image_sizes[$_size]['width'],
                    'height' => $_wp_additional_image_sizes[$_size]['height'],
                    'crop' => $_wp_additional_image_sizes[$_size]['crop'],
                );
            }
        }

        return $sizes;
    }

    private function get_image_size($size)
    {
        $sizes = $this->getExistedThumbnailsInfo();

        if (isset($sizes[$size])) {
            return $sizes[$size];
        }

        return false;
    }

    private function get_image_width($size)
    {
        if (!$size = $this->get_image_size($size)) {
            return false;
        }

        if (isset($size['width'])) {
            return $size['width'];
        }

        return false;
    }

    private function get_image_height($size)
    {
        if (!$size = $this->get_image_size($size)) {
            return false;
        }

        if (isset($size['height'])) {
            return $size['height'];
        }

        return false;
    }

    private function enqueueScriptsAndStyles()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script(self::PLUGIN_PREFIX . 'regenerate-ajax-handler', plugin_dir_url(__DIR__) . 'assets/js/regenerate-ajax-handler.js', ['jquery'], null, true);
            wp_localize_script(self::PLUGIN_PREFIX . 'regenerate-ajax-handler', 'regenerate_ajax_handler',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'testParamName' => 'testParamValue'
                ]
            );
            wp_enqueue_script(self::PLUGIN_PREFIX . 'loading-bar', plugin_dir_url(__DIR__) . 'assets/js/loading-bar.min.js', ['jquery'], null, true);
            wp_enqueue_style(self::PLUGIN_PREFIX . 'loading-bar', plugin_dir_url(__DIR__) . 'assets/css/loading-bar.min.css');
            wp_enqueue_script(self::PLUGIN_PREFIX . 'common', plugin_dir_url(__DIR__) . 'assets/js/common.js', ['jquery'], null, true);
        });
    }

    private function setAjaxRegenerationHandler()
    {
        add_action('wp_ajax_' . self::PLUGIN_PREFIX . 'regenerate_thumbnails', [$this, 'regenerateWithAjax']);
    }

    public function regenerateWithAjax()
    {
        $this->regenerate();

        wp_die();
    }

    public function regenerate()
    {
        require_once(ABSPATH . 'wp-admin/includes/admin.php');
        require_once(ABSPATH . 'wp-includes/pluggable.php');

        global $wpdb;
        $imagesExisted = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'");

        foreach ($imagesExisted as $image) {
            $imageFullSizePath = get_attached_file($image->ID);

            if (file_exists($imageFullSizePath)) {
                if (wp_update_attachment_metadata($image->ID, wp_generate_attachment_metadata($image->ID, $imageFullSizePath))) {

                } else {

                }
            }
        }
    }
}