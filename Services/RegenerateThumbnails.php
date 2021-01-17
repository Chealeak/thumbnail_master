<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RegenerateThumbnails extends Service
{
    public function register()
    {
        add_action('admin_menu', [$this, 'addAdminPage']);
        add_action('admin_init', [$this, 'adminPageInit']);

        //$this->regenerate();
    }

    public function addAdminPage()
    {
        add_menu_page('Thumbnail Master',
            'Thumbnail Master',
            'manage_options',
            $this->prefix . 'regenerate_thumbnails',
            function () {}
        );
        add_submenu_page($this->prefix . 'regenerate_thumbnails',
            'Regenerate Thumbnails',
            'Regenerate Thumbnails',
            'manage_options',
            $this->prefix . 'regenerate_thumbnails',
            function () {}
        );

        add_options_page(
            'Settings Admin',
            'My Settings',
            'manage_options',
            $this->prefix . 'regenerate_thumbnails',
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
                settings_fields($this->prefix . 'option_group');
                do_settings_sections($this->prefix . 'setting-admin');
                submit_button();
                ?>
            </form>
            <button class="<?= $this->prefix ?>regenerate-button-js">Regenerate</button>
            <div id="<?= $this->prefix ?>progressbar" class="ldBar"></div>
        </div>
        <?php
    }

    public function adminPageInit()
    {
        /*        register_setting(
                    $this->prefix . 'option_group',
                    'option_name',
                    [$this, 'sanitizeOptionField']
                );*/

/*        add_settings_section(
            $this->prefix . 'setting_section_analysis',
            'Analysis',
            [$this, 'printAnalysisSectionInfo'],
            $this->prefix . 'setting-admin'
        );*/

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

    /*    public function title_callback()
        {
            printf(
                '<input type="text" id="title" name="my_option_name[title]" value="%s"/>',
                isset($this->options['title']) ? esc_attr($this->options['title']) : ''
            );
        }*/

    private function enqueueScriptsAndStyles()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script($this->prefix . 'regenerate-ajax-handler', plugin_dir_url(__DIR__) . 'assets/js/regenerate-ajax-handler.js', ['jquery'], null, true);
            wp_localize_script($this->prefix . 'regenerate-ajax-handler', 'regenerate_ajax_handler',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'testParamName' => 'testParamValue'
                ]
            );
            wp_enqueue_script($this->prefix . 'loading-bar', plugin_dir_url(__DIR__) . 'assets/js/loading-bar.min.js', ['jquery'], null, true);
            wp_enqueue_style($this->prefix . 'loading-bar', plugin_dir_url(__DIR__) . 'assets/css/loading-bar.min.css');
            wp_enqueue_script($this->prefix . 'common', plugin_dir_url(__DIR__) . 'assets/js/common.js', ['jquery'], null, true);
        });
    }

    private function setAjaxRegenerationHandler()
    {
        add_action('wp_ajax_' . $this->prefix . 'regenerate_thumbnails', [$this, 'regenerateWithAjax']);
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