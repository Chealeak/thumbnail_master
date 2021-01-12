<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class AnalyzeThumbnails implements Service
{
    private $prefix = '';
    private $adminPage = null;

    public function __construct()
    {

    }

    public function register()
    {
/*        add_action('admin_menu', [$this, 'addAdminPage']);*/
        add_action('admin_init', [$this, 'adminPageInit']);
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function setAdminPage($adminPage)
    {
        $this->adminPage = $adminPage;
    }

    public function adminPageInit()
    {
        /*        register_setting(
                    $this->prefix . 'option_group',
                    'option_name',
                    [$this, 'sanitizeOptionField']
                );*/

        add_settings_section(
            $this->prefix . 'setting_section_analysis',
            'Analysis',
            [$this, 'printAnalysisSectionInfo'],
            $this->prefix . $this->adminPage
        );

        /*        add_settings_field(
                    'title',
                    'Title',
                    [$this, 'title_callback'],
                    'my-setting-admin',
                    'setting_section_id'
                );*/
    }

    public function printAnalysisSectionInfo()
    {
        // add_image_size('test_size', 555, 555, true);

        $table = '<table>';

        $table .= '<tr>';
        $table .= '<th>Name</th>';
        $table .= '<th>Size</th>';
        $table .= '<th>Crop</th>';
        $table .= '</tr>';

        $existedThumbnailsInfo = $this->getExistedThumbnailsInfo();
        foreach ($existedThumbnailsInfo as $thumbnailName => $thumbnailInfo) {
            $table .= '<tr>';
            $table .= "<td>{$thumbnailName}</td>";
            $table .= "<td>{$thumbnailInfo['width']}x{$thumbnailInfo['height']}</td>";
            $table .= "<td>" . ($thumbnailInfo['crop'] ? 'Yes' : 'No') . "</td>";
            $table .= '</tr>';
        }

        $table .= '</table>';

        echo $table;
    }

    private function getExistedThumbnailsInfo()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        foreach (get_intermediate_image_sizes() as $_size) {
            if (in_array($_size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
                $sizes[$_size]['width'] = get_option("{$_size}_size_w");
                $sizes[$_size]['height'] = get_option("{$_size}_size_h");
                $sizes[$_size]['crop'] = (bool)get_option("{$_size}_crop");
            } elseif (isset($_wp_additional_image_sizes[$_size])) {
                $sizes[$_size] = array(
                    'width' => $_wp_additional_image_sizes[$_size]['width'],
                    'height' => $_wp_additional_image_sizes[$_size]['height'],
                    'crop' => $_wp_additional_image_sizes[$_size]['crop'],
                );
            }
        }

        return $sizes;
    }

/*    private function get_image_size($size)
    {
        $sizes = $this->getExistedThumbnailsInfo();

        if (isset($sizes[$size])) {
            return $sizes[$size];
        }

        return false;
    }

    private function get_image_width($size)
    {
        if (!$size = $this->get_image_size($size)) {
            return false;
        }

        if (isset($size['width'])) {
            return $size['width'];
        }

        return false;
    }

    private function get_image_height($size)
    {
        if (!$size = $this->get_image_size($size)) {
            return false;
        }

        if (isset($size['height'])) {
            return $size['height'];
        }

        return false;
    }*/
}