<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RemoveRedundantThumbnails extends Service
{
    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;

        add_action('admin_init', [$this, 'adminPageInit']);
        $this->enqueueScriptsAndStyles();
        $this->setAjaxRemoveRedundantHandler();
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
        ?>
            <div class="wrap">
                <h1>Remove Redundant Thumbnails</h1>
                <button class="<?= $this->prefix ?>remove-redundant-button-js">Remove</button>
                <div class="<?= $this->prefix ?>remove-redundant-result-js" data-page="1"></div>
            </div>
        <?php
    }

    private function enqueueScriptsAndStyles()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script($this->prefix . 'remove-redundant-ajax-handler', plugin_dir_url(__DIR__) . 'assets/js/remove-redundant-ajax-handler.js', ['jquery'], null, true);
            wp_localize_script($this->prefix . 'remove-redundant-ajax-handler', 'remove_redundant_ajax_handler',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'prefix' => $this->prefix
                ]
            );
        });
    }

    private function setAjaxRemoveRedundantHandler()
    {
        add_action('wp_ajax_' . $this->prefix . 'remove_redundant_thumbnails', [$this, 'removeThumbnails']);
    }

    public function removeThumbnails()
    {
        $attachmentArgs = [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'any',
            'fields'         => 'ids',
        ];

        if (isset($_POST['page'])) {
            $attachmentArgs['paged'] = intval($_POST['page']);
            $attachmentArgs['posts_per_page'] = 10;
        }

        $attachmentQuery = new \WP_Query($attachmentArgs);

        $existedImageSizeNames = [];
        global $_wp_additional_image_sizes;

        if (!empty($_wp_additional_image_sizes)) {
            $existedImageSizeNames = array_keys($_wp_additional_image_sizes);
        }

        foreach ($attachmentQuery->posts as $attachmentId) {
            $attachmentMeta = wp_get_attachment_metadata($attachmentId);
            $uploadDirectory = wp_upload_dir();
            $pathToFileDirectory = str_replace(basename($attachmentMeta['file']), '', trailingslashit($uploadDirectory['basedir']) . $attachmentMeta['file']);
            foreach ($attachmentMeta['sizes'] as $size => $info) {
                if (!in_array($size, $existedImageSizeNames)) {
                    $filePath = realpath($pathToFileDirectory . $info['file']);
                    unlink($filePath);
                    unset($attachmentMeta['sizes'][$size]);
                }
            }

            if (!empty($attachmentMeta['sizes'])) {
                wp_update_attachment_metadata($attachmentId, $attachmentMeta);
            }
        }

        if (DOING_AJAX) {
            wp_send_json([
                'page' => isset($_POST['page']) ? $_POST['page'] + 1 : 1,
                'completed' => empty($attachmentQuery->found_posts)
            ]);

            wp_die();
        }
    }
}