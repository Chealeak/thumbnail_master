<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RegenerateThumbnails extends Service
{
    private $enabledImageSizes;

    public function __construct(DisableThumbnails $disableThumbnails)
    {
        $this->enabledImageSizes = $disableThumbnails->getEnabledImageSizes();
    }

    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;

        $this->enqueueScriptsAndStyles();
        $this->setAjaxRegenerationHandler();
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
        $getExistedThumbnailsInfo = $this->getExistedThumbnailsInfo();

        foreach ($getExistedThumbnailsInfo as $imageInfoName => $imageInfo) {
            if (!$imageInfo['enabled']) {
                remove_image_size($imageInfoName);
            }
        }

        foreach ($imagesExisted as $image) {
            $imageFullSizePath = get_attached_file($image->ID);

            if (file_exists($imageFullSizePath)) {
                $attachmentMetadata = wp_generate_attachment_metadata($image->ID, $imageFullSizePath);

                if (!empty($this->enabledImageSizes) && isset($attachmentMetadata['sizes'])) {
                    foreach ($attachmentMetadata['sizes'] as $sizeKey => $sizeValue) {
                        if (!in_array($sizeKey, $this->enabledImageSizes)) {
                            unset($attachmentMetadata['sizes'][$sizeKey]);
                        }
                    }
                }

                if (wp_update_attachment_metadata($image->ID, $attachmentMetadata)) {

                } else {

                }
            }
        }
    }

    private function getExistedThumbnailsInfo()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];
        $enabledThumbnailSizes = $this->enabledImageSizes;

        foreach (get_intermediate_image_sizes() as $size) {
            $thumbnailEnabled = in_array($size, $enabledThumbnailSizes);

            if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
                $sizes[$size]['width'] = get_option("{$size}_size_w");
                $sizes[$size]['height'] = get_option("{$size}_size_h");
                $sizes[$size]['crop'] = (bool)get_option("{$size}_crop");
                $sizes[$size]['enabled'] = $thumbnailEnabled;
            } elseif (isset($_wp_additional_image_sizes[$size])) {
                $sizes[$size] = [
                    'width' => $_wp_additional_image_sizes[$size]['width'],
                    'height' => $_wp_additional_image_sizes[$size]['height'],
                    'crop' => $_wp_additional_image_sizes[$size]['crop'],
                    'enabled' => $thumbnailEnabled
                ];
            }
        }

        return $sizes;
    }
}