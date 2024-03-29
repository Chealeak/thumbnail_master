<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RemoveRedundantThumbnails extends Service
{
    public function register(string $prefix, string $textDomain, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->textDomain = $textDomain;
        $this->adminPage = $adminPage;
        $this->dbOptionExistedImageSizes = $prefix . 'existed_image_sizes';

        $this->enqueueScriptsAndStyles();
        $this->setAjaxRemoveRedundantHandler();
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

        if (isset($_POST['thumbnailName'])) {
            $thumbnailNameToRemove = filter_var($_POST['thumbnailName'], FILTER_SANITIZE_STRING);
        }

        $attachmentQuery = new \WP_Query($attachmentArgs);
        foreach ($attachmentQuery->posts as $attachmentId) {
            $attachmentMeta = wp_get_attachment_metadata($attachmentId);
            $uploadDirectory = wp_upload_dir();
            $pathToFileDirectory = str_replace(basename($attachmentMeta['file']), '', trailingslashit($uploadDirectory['basedir']) . $attachmentMeta['file']);
            foreach ($attachmentMeta['sizes'] as $existedThumbnailName => $existedThumbnailInfo) {
                if (isset($this->storedThumbnailsInfo[$existedThumbnailName])) {
                    if (!$this->storedThumbnailsInfo[$existedThumbnailName]['enabled']) {
                        $isSpecifiedThumbnailRemoving = (!empty($thumbnailNameToRemove) && ($thumbnailNameToRemove === $existedThumbnailName));
                        $isDisabledThumbnailsRemoving = (empty($thumbnailNameToRemove));

                        if ($isSpecifiedThumbnailRemoving || $isDisabledThumbnailsRemoving) {
                            $filePath = realpath($pathToFileDirectory . $existedThumbnailInfo['file']);
                            if ($filePath) {
                                unlink($filePath);
                            }
                            unset($attachmentMeta['sizes'][$existedThumbnailName]);
                        }
                    }
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