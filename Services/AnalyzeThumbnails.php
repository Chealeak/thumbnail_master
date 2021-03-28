<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class AnalyzeThumbnails extends Service
{
    public function register()
    {
/*        add_action('admin_menu', [$this, 'addAdminPage']);*/
        add_action('admin_init', [$this, 'adminPageInit']);
        $this->enqueueScriptsAndStyles();
        $this->setAjaxDisableHandler();
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

    public function disableThumbnail()
    {
        $thumbnailMaster = \ThumbnailMaster::getInstance();

        if (isset($_POST['thumbnail_name'])) {

            if (DOING_AJAX) {
                wp_send_json([
                    'status' => $thumbnailMaster->toggleThumbnailActivation(filter_var($_POST['thumbnail_name'], FILTER_SANITIZE_STRING))
                ]);

                wp_die();
            }
        }
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

        $thumbnailMaster = \ThumbnailMaster::getInstance();

        foreach (get_intermediate_image_sizes() as $size) {
            $thumbnailEnabled = in_array($size, $thumbnailMaster->getEnabledImageSizes());

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