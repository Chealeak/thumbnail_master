<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class DisableThumbnails extends Service
{
    private $dbOptionExistedImageSizes;
    private $existedThumbnailsInfo = [];

    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;
        $this->dbOptionExistedImageSizes = $prefix . 'existed_image_sizes';

        $this->enqueueScriptsAndStyles();
        $this->setAjaxDisableHandler();
        $this->keepEnabledImageSizes();
    }

    private function enqueueScriptsAndStyles()
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script($this->prefix . 'disable-ajax-handler', plugin_dir_url(__DIR__) . 'assets/js/disable-ajax-handler.js', ['jquery'], null, true);
            wp_localize_script($this->prefix . 'disable-ajax-handler', 'disable_ajax_handler',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'prefix' => $this->prefix
                ]
            );
        });
    }

    private function setAjaxDisableHandler()
    {
        add_action('wp_ajax_' . $this->prefix . 'disable_thumbnail', [$this, 'disableThumbnail']);
    }

    private function keepEnabledImageSizes()
    {
        add_action('init', function () {
            $this->existedThumbnailsInfo = $this->getExistedThumbnailsInfo();
        });
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

    public function getExistedImageSizesInfo()
    {
        return $this->existedThumbnailsInfo;
    }

    public function disableThumbnail()
    {
        if (isset($_POST['thumbnail_name'])) {

            if (DOING_AJAX) {
                wp_send_json([
                    'status' => $this->toggleThumbnailActivation(filter_var($_POST['thumbnail_name'], FILTER_SANITIZE_STRING))
                ]);

                wp_die();
            }
        }
    }

    public function toggleThumbnailActivation($thumbnailName)
    {
        if (in_array($thumbnailName, $this->existedThumbnailsInfo)) {
            $this->existedThumbnailsInfo = array_diff($this->existedThumbnailsInfo, [$thumbnailName]);
        } else {
            $this->existedThumbnailsInfo[] = $thumbnailName;
        }

        update_option($this->dbOptionExistedImageSizes, $this->existedThumbnailsInfo, false);
        $dbEnabledImageSizes = get_option($this->dbOptionExistedImageSizes);

        return in_array($thumbnailName, $dbEnabledImageSizes) ? 'enabled' : 'disabled';
    }
}