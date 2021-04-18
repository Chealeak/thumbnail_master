<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class AnalyzeThumbnails extends Service
{
    private $disableThumbnails;

    public function __construct(DisableThumbnails $disableThumbnails)
    {
        $this->disableThumbnails = $disableThumbnails;
    }

    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;

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
            $disableButtonTitle = ($thumbnailInfo['enabled'] ? 'Disable' : 'Enable');
            $disableButtonExtraClass = ($thumbnailInfo['enabled'] ? $this->prefix . 'enabled' : $this->prefix . 'disabled');
            $table .= "
                <td>
                    <button class='button button-primary' data-thumbnail-name='" . $thumbnailName . "'>Analyze</button>
                    <button class='button button-primary {$this->prefix}disable-button-js {$this->prefix}disable-button-{$thumbnailName}-js {$disableButtonExtraClass}' data-thumbnail-name='" . $thumbnailName . "'>{$disableButtonTitle}</button>
                    <button class='button button-primary' data-thumbnail-name='" . $thumbnailName . "'>Regenerate</button>
                    <button class='button button-primary' data-thumbnail-name='" . $thumbnailName . "'>Remove redundant</button>
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
        $enabledThumbnailSizes = $this->disableThumbnails->getEnabledImageSizes();

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