<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RegenerateThumbnails implements Service
{
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
        add_menu_page('Thumbnail Master', 'Thumbnail Master', 'manage_options', 'th_m_regenerate_thumbnails', function (){});
        add_submenu_page('th_m_regenerate_thumbnails', 'Regenerate Thumbnails', 'Regenerate Thumbnails', 'manage_options', 'th_m_regenerate_thumbnails', function (){});

        add_options_page(
            'Settings Admin',
            'My Settings',
            'manage_options',
            'th_m_regenerate_thumbnails',
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
                    settings_fields( 'my_option_group' );
                    do_settings_sections( 'my-setting-admin' );
                    submit_button();
                ?>
            </form>
            <button class="th_m_regenerate-button-js">Regenerate</button>
            <div id="th_m_progressbar" class="ldBar"></div>
        </div>
        <?php
    }

    public function adminPageInit()
    {
        register_setting(
            'my_option_group',
            'my_option_name',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'setting_section_id',
            'My Custom Settings',
            array( $this, 'print_section_info' ),
            'my-setting-admin'
        );

        add_settings_field(
            'title',
            'Title',
            array( $this, 'title_callback' ),
            'my-setting-admin',
            'setting_section_id'
        );
    }

    public function sanitize( $input )
    {
        $new_input = array();

        if( isset( $input['title'] ) ) {
            $new_input['title'] = sanitize_text_field($input['title']);
        }

        return $new_input;
    }

    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    public function title_callback()
    {
        printf(
            '<input type="text" id="title" name="my_option_name[title]" value="%s"/>',
            isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
        );
    }

    private function enqueueScriptsAndStyles()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script('th_m_regenerate-ajax-handler', plugin_dir_url(__DIR__) . 'assets/js/regenerate-ajax-handler.js', ['jquery'], null, true);
            wp_localize_script('th_m_regenerate-ajax-handler', 'regenerate_ajax_handler',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'testParamName' => 'testParamValue'
                ]
            );
            wp_enqueue_script('th_m_loading-bar', plugin_dir_url(__DIR__) . 'assets/js/loading-bar.min.js', ['jquery'], null, true);
            wp_enqueue_style('th_m_loading-bar', plugin_dir_url(__DIR__) . 'assets/css/loading-bar.min.css');
            wp_enqueue_script('th_m_common', plugin_dir_url(__DIR__) . 'assets/js/common.js', ['jquery'], null, true);
        });
    }

    private function setAjaxRegenerationHandler()
    {
        add_action('wp_ajax_th_m_regenerate_thumbnails', [$this, 'regenerateWithAjax']);
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
        $imagesExisted = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'" );

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