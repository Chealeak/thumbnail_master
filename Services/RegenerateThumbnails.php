<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RegenerateThumbnails implements Service
{
    public function register()
    {
        add_action('admin_menu', [$this, 'addAdminPage']);

        $this->regenerate(); // TODO: temporary!!!
    }

    public function addAdminPage()
    {
        add_menu_page('Regenerate Thumbnails', 'Regenerate Thumbnails', 'manage_options', 'th_m_regenerate_thumbnails', [$this, 'renderAdminPage']);
    }

    public function renderAdminPage()
    {
        echo '<h1>Generate Thumbnails</h1>';
    }

    public function regenerate()
    {
        global $wpdb;
        $imagesExisted = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'" );

        foreach ($imagesExisted as $image) {
            $imageFullSizePath = get_attached_file($image->ID);

            //if (!file_exists($imageFullSizePath)) {
                require_once( ABSPATH . 'wp-admin/includes/admin.php' );
                require_once( ABSPATH . 'wp-includes/pluggable.php' );
                add_image_size('test_size', 700, 700, true);

                if (wp_update_attachment_metadata($image->ID, wp_generate_attachment_metadata($image->ID, $imageFullSizePath))) {

                } else {

                }
            //}
        }
    }
}