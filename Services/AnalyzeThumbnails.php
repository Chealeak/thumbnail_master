<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class AnalyzeThumbnails extends Service
{
    public function register()
    {
/*        add_action('admin_menu', [$this, 'addAdminPage']);*/
        add_action('admin_init', [$this, 'adminPageInit']);
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
            [$this, 'printSectionInfo'],
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

    public function printSectionInfo()
    {
        $table = '<table>';

        $table .= '<tr>';
        $table .= '<th>Name</th>';
        $table .= '<th>Size</th>';
        $table .= '<th>Crop</th>';
        $table .= '<th>Actions</th>';
        $table .= '</tr>';

        $existedThumbnailsInfo = $this->getExistedThumbnailsInfo();
        foreach ($existedThumbnailsInfo as $thumbnailName => $thumbnailInfo) {
            $table .= '<tr>';
            $table .= "<td>{$thumbnailName}</td>";
            $table .= "<td>{$thumbnailInfo['width']}x{$thumbnailInfo['height']}</td>";
            $table .= "<td>" . ($thumbnailInfo['crop'] ? 'Yes' : 'No') . "</td>";
            $table .= "
                <td>
                    <button class='button button-primary'>Analyze</button>
                    <button class='button button-primary'>Disable</button>
                    <button class='button button-primary'>Regenerate</button>
                    <button class='button button-primary'>Remove redundant</button>
                </td>
            ";
            $table .= '</tr>';
        }

        $table .= '</table>';

        $allImages = '<h2>All images</h2>';
        $allImages .= "
            <td>
                <button class='button button-primary'>Regenerate</button>
                <button class='button button-primary'>Remove redundant</button>
                <button class='button button-primary'>Backup uploads</button>
            </td>
        ";

        $checkboxes = "<form method='post' action='options.php'>";
        $checkboxes .= "
            <div>
                <div><input type='checkbox'>Enable regeneration on fly</div>
                <div><input type='checkbox'>Enable responsive images</div>
                <div><input type='checkbox'>Enable WebP</div>
            </div>
        ";
        $checkboxes .= "<p class='submit'><input type='submit' name='submit' id='submit' class='button button-primary' value='Save Changes'></p>";
        $checkboxes .= "</form>";

        echo $table . $allImages . $checkboxes;
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