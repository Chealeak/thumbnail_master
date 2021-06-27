<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class AnalyzeThumbnails extends Service
{
    private $existedThumbnailsInfo;

    public function __construct(DisableThumbnails $disableThumbnails)
    {
        $this->existedThumbnailsInfo = $disableThumbnails->getExistedImageSizesInfo();
    }

    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;

        add_action('admin_init', [$this, 'adminPageInit']);
    }

    public function adminPageInit()
    {
        add_settings_section(
            $this->prefix . 'setting_section_analysis',
            'Analysis',
            [$this, 'printSectionInfo'],
            $this->adminPage
        );
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
                    <button class='button button-primary {$this->prefix}disable-button-js {$this->prefix}disable-button-{$thumbnailName}-js {$disableButtonExtraClass}' data-thumbnail-name='" . $thumbnailName . "'>{$disableButtonTitle}</button>
                    <button class='button button-primary {$this->prefix}regenerate-single-button-js' data-thumbnail-name='" . $thumbnailName . "'>Regenerate</button>
                    <button class='button button-primary {$this->prefix}remove-redundant-single-button-js' data-thumbnail-name='" . $thumbnailName . "'>Remove redundant</button>
                </td>
            ";
            $table .= '</tr>';
        }

        $table .= '</table>';

        $allImages = '<h2>All images</h2>';
        $allImages .= "
            <td>
                <button class='button button-primary {$this->prefix}regenerate-button-js'>Regenerate</button>
                <div id='{$this->prefix}progressbar' class='ldBar'></div>
                <button class='button button-primary {$this->prefix}remove-redundant-button-js'>Remove redundant</button>
                <div class='{$this->prefix}remove-redundant-result-js' data-page='1'></div>
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

        foreach (get_intermediate_image_sizes() as $size) {
            $enabled = true;
            if ($this->existedThumbnailsInfo) {
                if (isset($this->existedThumbnailsInfo[$size])) {
                    $enabled = $this->existedThumbnailsInfo[$size]['enabled'];
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
}