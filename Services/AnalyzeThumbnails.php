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
        $noticesWrapper = "
            <div class='
                " . $this->prefix . "notices'
                data-regenerate-success-text='" . __('Regeneration is done!', $this->textDomain) . "'
                data-remove-redundant-success-text='" . __('Removing is done!', $this->textDomain) . "'
                data-dismiss-notice-text='" . __('Dismiss this notice.', $this->textDomain) . "'>
            </div>
        ";

        $sectionWrapperStart = '<div class="section">';

        $sectionTitle = '<h1 class="title is-4">' . __('Thumbnail Master', $this->textDomain) . '</h1>';

        $settings = '<h2 class="title is-6">' . __('Settings', $this->textDomain) . '</h2>';

        $settings .= "<form method='post' action='options.php'>";
        $settings .= "
            <div class='block'>
                <div class='block'>
                    <p><strong>" . __('Thumbnails are regenerated if they exist on a current page during the page rendering', $this->textDomain) . "</strong></p>
                    <label class='checkbox'>" . __('Enable regeneration on fly', $this->textDomain) . "
                        <input type='checkbox'>
                    </label>
                </div>
            </div>
        ";
        $settings .= "<input type='submit' name='submit' id='submit' class='bulma-button is-link' value='Save Changes'></p>";
        $settings .= "</form>";

        $actions = '<h2 class="title is-6">' . __('Actions', $this->textDomain) . '</h2>';
        $actions .= "
            <div class='block'>
                <div class='block'>
                    <p><strong>" . __('Regenerate all active thumbnails', $this->textDomain) . "</strong></p>
                    <button class='bulma-button is-info {$this->prefix}regenerate-button-js' data-in-process-text='" . __('Regenerating...', $this->textDomain) . "'>" . __('Regenerate', $this->textDomain) . "</button>
                </div>
                
                <div class='block'>
                    <p><strong>" . __('Remove all disabled redundant thumbnails that are not used in the system', $this->textDomain) . "</strong></p>
                    <button class='bulma-button is-info {$this->prefix}remove-redundant-button-js' data-page='1' data-in-process-text='" . __('Removing...', $this->textDomain) . "'>" . __('Remove Redundant', $this->textDomain) . "</button>
                </div>
            </div>
        ";

        $table = '<h2 class="title is-6">' . __('Enable/disable image sizes', $this->textDomain) . '</h2>';
        $table .= '<table class="table is-bordered is-striped is-hoverable">';

        $table .= '<tr class="has-text-centered">';
        $table .= '<th>' . __('Name', $this->textDomain) . '</th>';
        $table .= '<th>' . __('Size', $this->textDomain) . '</th>';
        $table .= '<th>' . __('Crop', $this->textDomain) . '</th>';
        $table .= '<th>' . __('Action', $this->textDomain) . '</th>';
        $table .= '</tr>';

        foreach ($this->storedThumbnailsInfo as $thumbnailName => $thumbnailInfo) {
            $table .= '<tr>';
            $table .= "<td>{$thumbnailName}</td>";
            $table .= "<td>{$thumbnailInfo['width']}x{$thumbnailInfo['height']}</td>";
            $table .= "<td>" . ($thumbnailInfo['crop'] ? 'Yes' : 'No') . "</td>";
            $disableButtonTitle = ($thumbnailInfo['enabled'] ? __('Disable', $this->textDomain) : __('Enable', $this->textDomain));
            $disableButtonExtraClass = ($thumbnailInfo['enabled'] ? '' : 'is-info');
            $table .= "
                <td>
                    <button class='bulma-button $disableButtonExtraClass {$this->prefix}disable-button-js {$this->prefix}disable-button-{$thumbnailName}-js' data-thumbnail-name='" . $thumbnailName . "'>{$disableButtonTitle}</button>
                </td>
            ";
            $table .= '</tr>';
        }

        $table .= '</table>';

        $sectionWrapperEnd = '</div>';

        echo
            $noticesWrapper .
            $sectionWrapperStart .
            $sectionTitle .
            "<section class='box'>" . $settings . "</section>" .
            "<section class='box'>" . $actions . "</section>" .
            "<section class='box'>" . $table . "</section>" .
            $sectionWrapperEnd;
    }
}