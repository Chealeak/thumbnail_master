<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RemoveRedundantThumbnails extends Service
{
    public function register()
    {
        add_action('admin_init', [$this, 'adminPageInit']);
    }

    public function adminPageInit()
    {
        add_settings_section(
            $this->prefix . 'setting_section_remove_redundant_thumbnails',
            'Remove Redundant Thumbnails',
            [$this, 'printSectionInfo'],
            $this->prefix . $this->adminPage
        );
    }

    public function printSectionInfo()
    {
        $attachmentArgs = [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'any',
            'fields'         => 'ids',
        ];

        $attachmentQuery = new \WP_Query($attachmentArgs);

        foreach ($attachmentQuery->posts as $attachmentId) {
            $attachmentMeta = wp_get_attachment_metadata($attachmentId);
            $uploadDirectory = wp_upload_dir();
            $pathToFileDirectory = str_replace(basename($attachmentMeta['file']), '', trailingslashit($uploadDirectory['basedir']) . $attachmentMeta['file']);

            foreach ($attachmentMeta['sizes'] as $size => $info) {
                $filePath = realpath( $pathToFileDirectory . $info['file'] );
                //unlink($filePath); // TODO: uncomment
                //unset($attachmentMeta['sizes'][ $size ]); // TODO: uncomment
            }

            wp_update_attachment_metadata($attachmentId, $attachmentMeta);
        }

        ?>
            <div class="wrap">
                <h1>Remove Redundant Thumbnails</h1>
                <button>Remove</button>
            </div>
        <?php
    }
}