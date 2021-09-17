<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RegenerateThumbnails extends Service
{
    public function register(string $prefix, string $textDomain, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->textDomain = $textDomain;
        $this->adminPage = $adminPage;
        $this->dbOptionExistedImageSizes = $prefix . 'existed_image_sizes';

        $this->enqueueScriptsAndStyles();
        $this->setAjaxRegenerationHandler();
        add_action('init', function () {
            $this->prepareForRegeneration();
        });
        $this->regenerateOnFly();
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
/*            wp_enqueue_script($this->prefix . 'loading-bar', plugin_dir_url(__DIR__) . 'assets/js/loading-bar.min.js', ['jquery'], null, true);
            wp_enqueue_style($this->prefix . 'loading-bar', plugin_dir_url(__DIR__) . 'assets/css/loading-bar.min.css');*/
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
        global $wpdb;
        $imagesExisted = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'");

        $this->prepareForRegeneration();

        foreach ($imagesExisted as $image) {
            $this->regenerateSingleImage($image->ID);
        }

        foreach ($this->storedThumbnailsInfo as $imageInfoName => $imageInfo) {
            if (!$imageInfo['enabled']) {
                add_image_size($imageInfoName, $imageInfo['width'], $imageInfo['height'], $imageInfo['crop']);
            }
        }
    }

    private function prepareForRegeneration()
    {
        $defaultImageSizesToRemove = [];

        foreach ($this->storedThumbnailsInfo as $imageInfoName => $imageInfo) {
            $isDefaultImageSize = in_array($imageInfoName, ['thumbnail', 'medium', 'medium_large', 'large']);
            if ($isDefaultImageSize && !$imageInfo['enabled']) {
                $defaultImageSizesToRemove[] = $imageInfoName;
            }
        }

        foreach ($this->storedThumbnailsInfo as $imageInfoName => $imageInfo) {
            if (!$imageInfo['enabled']) {
                remove_image_size($imageInfoName);
            }
        }

        add_filter('intermediate_image_sizes_advanced', function ($sizes) use ($defaultImageSizesToRemove) {
            foreach ($defaultImageSizesToRemove as $defaultImageSizeName) {
                unset($sizes[$defaultImageSizeName]);
            }

            return $sizes;
        });
    }

    public function regenerateSingleImage($imageId)
    {
        require_once(ABSPATH . 'wp-admin/includes/admin.php');
        require_once(ABSPATH . 'wp-includes/pluggable.php');

        $imageFullSizePath = get_attached_file($imageId);

        if (file_exists($imageFullSizePath)) {
            $attachmentMetadata = wp_generate_attachment_metadata($imageId, $imageFullSizePath);

            if (isset($attachmentMetadata['sizes'])) {
                foreach ($attachmentMetadata['sizes'] as $sizeName => $sizeInfo) {
                    if (isset($this->storedThumbnailsInfo[$sizeName])) {
                        if (!$this->storedThumbnailsInfo[$sizeName]['enabled']) {
                            unset($attachmentMetadata['sizes'][$sizeName]);
                        }
                    }
                }
            }

            if (wp_update_attachment_metadata($imageId, $attachmentMetadata)) {

            } else {

            }
        }
    }

    public function regenerateOnFly()
    {
        add_filter('wp_get_attachment_image_src', function ($image, $attachment_id, $size, $icon) {
            $this->regenerateSingleImage($attachment_id);

            return $image;
        }, 10, 5);
    }
}