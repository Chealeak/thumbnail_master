<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RemoveRedundantThumbnails extends Service
{
    private $dbOptionExistedImageSizes;
    private $existedThumbnailsInfo;

    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;
        $this->dbOptionExistedImageSizes = $prefix . 'existed_image_sizes';

        $this->enqueueScriptsAndStyles();
        $this->setAjaxRemoveRedundantHandler();
        $this->keepEnabledImageSizes();
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

    public function removeThumbnails($thumbnailNameToRemove = null)
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
                if (isset($this->existedThumbnailsInfo[$existedThumbnailName])) {
                    if (!$this->existedThumbnailsInfo[$existedThumbnailName]['enabled']) {
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

    private function getExistedThumbnailsInfo()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        $existedImageSizesFromDb = get_option($this->dbOptionExistedImageSizes);

        foreach (get_intermediate_image_sizes() as $size) {
            $enabled = true;
            if ($existedImageSizesFromDb) {
                if (isset($existedImageSizesFromDb[$size])) {
                    $enabled = $existedImageSizesFromDb[$size]['enabled'];
                }
            }

            if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
                $sizes[$size]['width'] = get_option("{$size}_size_w");
                $sizes[$size]['height'] = get_option("{$size}_size_h");
                $sizes[$size]['crop'] = (bool)get_option("{$size}_crop");
                $sizes[$size]['enabled'] = $enabled;
            } elseif (isset($_wp_additional_image_sizes[$size])) {
                $sizes[$size] = [
                    'width' => $_wp_additional_image_sizes[$size]['width'],
                    'height' => $_wp_additional_image_sizes[$size]['height'],
                    'crop' => $_wp_additional_image_sizes[$size]['crop'],
                    'enabled' => $enabled
                ];
            }
        }

        return $sizes;
    }

    private function keepEnabledImageSizes()
    {
        add_action('init', function () {
            $this->existedThumbnailsInfo = $this->getExistedThumbnailsInfo();
        });
    }
}