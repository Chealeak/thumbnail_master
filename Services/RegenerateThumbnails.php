<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RegenerateThumbnails extends Service
{
    private $dbOptionExistedImageSizes;
    private $existedThumbnailsInfo;

    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;
        $this->dbOptionExistedImageSizes = $prefix . 'existed_image_sizes';

        $this->enqueueScriptsAndStyles();
        $this->setAjaxRegenerationHandler();
        $this->keepEnabledImageSizes();
    }

    public function sanitizeOptionField($input)
    {
        $newInput = [];

        if (isset($input['title'])) {
            $newInput['title'] = sanitize_text_field($input['title']);
        }

        return $newInput;
    }

    private function enqueueScriptsAndStyles()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script($this->prefix . 'regenerate-ajax-handler', plugin_dir_url(__DIR__) . 'assets/js/regenerate-ajax-handler.js', ['jquery'], null, true);
            wp_localize_script($this->prefix . 'regenerate-ajax-handler', 'regenerate_ajax_handler',
                [
                    'ajaxurl' => admin_url('admin-ajax.php')
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
        $thumbnailName = null;

        if (isset($_POST['thumbnailName'])) {
            $thumbnailName = filter_var($_POST['thumbnailName'], FILTER_SANITIZE_STRING);
        }

        $this->regenerate($thumbnailName);

        wp_die();
    }

    public function regenerate($thumbnailName = null)
    {
        require_once(ABSPATH . 'wp-admin/includes/admin.php');
        require_once(ABSPATH . 'wp-includes/pluggable.php');

        global $wpdb;
        $imagesExisted = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'");
        $existedThumbnailsInfo = $this->getExistedThumbnailsInfo();

        $defaultImageSizesToRemove = [];

        foreach ($existedThumbnailsInfo as $imageInfoName => $imageInfo) {
            $isDefaultImageSize = in_array($imageInfoName, ['thumbnail', 'medium', 'medium_large', 'large']);
            if ($isDefaultImageSize && !$imageInfo['enabled']) {
                $defaultImageSizesToRemove[] = $imageInfoName;
            }
        }

        if (!is_null($thumbnailName) ) {
            foreach ($existedThumbnailsInfo as $imageInfoName => $imageInfo)
                if ($thumbnailName !== $imageInfoName) {
                    remove_image_size($imageInfoName);
                }
        } else {
            foreach ($existedThumbnailsInfo as $imageInfoName => $imageInfo) {
                if (!$imageInfo['enabled']) {
                    remove_image_size($imageInfoName);
                }
            }
        }

        add_filter('intermediate_image_sizes_advanced', function ($sizes) use ($defaultImageSizesToRemove) {
            foreach ($defaultImageSizesToRemove as $defaultImageSizeName) {
                unset($sizes[$defaultImageSizeName]);
            }

            return $sizes;
        });

        foreach ($imagesExisted as $image) {
            $imageFullSizePath = get_attached_file($image->ID);

            if (file_exists($imageFullSizePath)) {
                $attachmentMetadata = wp_generate_attachment_metadata($image->ID, $imageFullSizePath);

                if (isset($attachmentMetadata['sizes'])) {
                    foreach ($attachmentMetadata['sizes'] as $sizeName => $sizeInfo) {
                        if (isset($this->existedThumbnailsInfo[$sizeName])) {
                            if (!$this->existedThumbnailsInfo[$sizeName]['enabled']) {
                                unset($attachmentMetadata['sizes'][$sizeName]);
                            }
                        }
                    }
                }

                if (wp_update_attachment_metadata($image->ID, $attachmentMetadata)) {

                } else {

                }
            }
        }

        if (!is_null($thumbnailName) ) {
            foreach ($existedThumbnailsInfo as $imageInfoName => $imageInfo)
                if ($thumbnailName !== $imageInfoName) {
                    add_image_size($imageInfoName, $imageInfo['width'], $imageInfo['height'], $imageInfo['crop']);
                }
        } else {
            foreach ($existedThumbnailsInfo as $imageInfoName => $imageInfo) {
                if (!$imageInfo['enabled']) {
                    add_image_size($imageInfoName, $imageInfo['width'], $imageInfo['height'], $imageInfo['crop']);
                }
            }
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