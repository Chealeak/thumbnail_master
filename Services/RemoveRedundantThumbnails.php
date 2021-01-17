<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class RemoveRedundantThumbnails extends Service
{
    public function register()
    {
        add_action('admin_init', [$this, 'adminPageInit']);
    }

    public function adminPageInit()
    {
        add_settings_section(
            $this->prefix . 'setting_section_remove_redundant_thumbnails',
            'Remove Redundant Thumbnails',
            [$this, 'printSectionInfo'],
            $this->prefix . $this->adminPage
        );
    }

    public function printSectionInfo()
    {
        ?>
            <div class="wrap">
                <h1>Remove Redundant Thumbnails</h1>
                <button>Remove</button>
            </div>
        <?php
    }
}