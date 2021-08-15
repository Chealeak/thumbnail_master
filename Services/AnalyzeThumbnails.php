<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class AnalyzeThumbnails extends Service
{
    public function register(string $prefix, string $textDomain, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->textDomain = $textDomain;
        $this->adminPage = $adminPage;
        $this->dbOptionExistedImageSizes = $prefix . 'existed_image_sizes';

        $this->enqueueScriptsAndStyles();

        add_action('admin_init', [$this, 'adminPageInit']);
    }

    private function enqueueScriptsAndStyles()
    {
        if (isset($_GET['page']) && $_GET['page'] == $this->adminPage) {
            add_action('admin_enqueue_scripts', function () {
                wp_enqueue_style($this->prefix . 'bulma-modified', plugin_dir_url(__DIR__) . 'assets/css/bulma-modified.min.css');
            });
        }
    }

    public function adminPageInit()
    {
        add_settings_section(
            $this->prefix . 'setting_section_analysis',
            null,
            [$this, 'printSectionInfo'],
            $this->adminPage
        );
    }

    public function printSectionInfo()
    {
        $sectionTitle = '<h1 class="title is-4">' . __('Thumbnail master', $this->textDomain) . '</h1>';

        $allImages = '<h2 class="title is-6">' . __('All images', $this->textDomain) . '</h2>';
        $allImages .= "
            <div class='block'>
                <div class='block'>
                    <p><strong>" . __('Regenerate all active thumbnails', $this->textDomain) . "</strong></p>
                    <button class='button button-primary {$this->prefix}regenerate-button-js'>Regenerate All</button>
                </div>
                
                <div class='block'>
                    <p><strong>" . __('Remove all redundant thumbnails that are not used in the system', $this->textDomain) . "</strong></p>
                    <button class='button button-primary {$this->prefix}remove-redundant-button-js'>Remove All</button>
                    <div class='{$this->prefix}remove-redundant-result-js' data-page='1'></div>
                </div>
                <!--<button class='button button-primary'>Backup uploads</button>-->
            </div>
        ";

        $checkboxes = "<form method='post' action='options.php'>";
        $checkboxes .= "
            <div class='block'>
                <div class='block'>
                    <p><strong>" . __('Thumbnails are regenerated if they exist on a current page during the page rendering', $this->textDomain) . "</strong></p>
                    <label class='checkbox'>" . __('Enable regeneration on fly', $this->textDomain) . "
                        <input type='checkbox'>
                    </label>
                </div>
                <div class='block'>
                    <p><strong>" . __('After enabling this option you will be allowed to use func function which will generate a picture tag for you', $this->textDomain) . "</strong></p>
                    <label class='checkbox'>" . __('Enable responsive images', $this->textDomain) . "
                        <input type='checkbox'>
                    </label> 
                </div>
                <!--<div><input type='checkbox'>Enable WebP</div>-->
            </div>
        ";
        $checkboxes .= "<p class='submit'><input type='submit' name='submit' id='submit' class='button button-primary' value='Save Changes'></p>";
        $checkboxes .= "</form>";

        $table = '<h2 class="title is-6">' . __('All image sizes', $this->textDomain) . '</h2>';
        $table .= '<table class="table is-bordered is-striped is-hoverable">';

        $table .= '<tr>';
        $table .= '<th>Name</th>';
        $table .= '<th>Size</th>';
        $table .= '<th>Crop</th>';
        $table .= '<th colspan="3">Actions</th>';
        $table .= '</tr>';

        foreach ($this->storedThumbnailsInfo as $thumbnailName => $thumbnailInfo) {
            $table .= '<tr>';
            $table .= "<td>{$thumbnailName}</td>";
            $table .= "<td>{$thumbnailInfo['width']}x{$thumbnailInfo['height']}</td>";
            $table .= "<td>" . ($thumbnailInfo['crop'] ? 'Yes' : 'No') . "</td>";
            $disableButtonTitle = ($thumbnailInfo['enabled'] ? 'Disable' : 'Enable');
            $disableButtonExtraClass = ($thumbnailInfo['enabled'] ? '' : 'is-info');
            $table .= "
                <td>
                    <button class='bulma-button $disableButtonExtraClass {$this->prefix}disable-button-js {$this->prefix}disable-button-{$thumbnailName}-js' data-thumbnail-name='" . $thumbnailName . "'>{$disableButtonTitle}</button>
                </td>
                <td>
                    <button class='bulma-button is-link is-outlined {$this->prefix}regenerate-single-button-js' data-thumbnail-name='" . $thumbnailName . "'>Regenerate</button>
                </td>
                <td>
                    <button class='bulma-button is-link is-outlined {$this->prefix}remove-redundant-single-button-js' data-thumbnail-name='" . $thumbnailName . "'>Remove redundant</button>
                </td>
            ";
            $table .= '</tr>';
        }

        $table .= '</table>';

        echo
            $sectionTitle .
            "<div class='box'>" . $allImages . $checkboxes . "</div>" .
            "<div class='box'>" . $table . "</div>";
    }
}