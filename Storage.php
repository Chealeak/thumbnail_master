<?php

namespace ThumbnailMaster;

class Storage
{
    private $dbOptionExistedImageSizes;
    private $storedThumbnailsInfo;

    public function __construct()
    {
        $this->dbOptionExistedImageSizes = 'th_m_existed_image_sizes';
        $this->keepExistedThumbnailsInfo();
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

    private function keepExistedThumbnailsInfo()
    {
        add_action('init', function () {
            $this->storedThumbnailsInfo = $this->getExistedThumbnailsInfo();
        });
    }

    public function getStoredThumbnailsInfo()
    {
        return $this->storedThumbnailsInfo;
    }
}