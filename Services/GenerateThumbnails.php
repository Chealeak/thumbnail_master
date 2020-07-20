<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class GenerateThumbnails implements Service
{
    public function register()
    {
        add_action('admin_menu', [$this, 'addAdminPage']);
    }

    public function addAdminPage()
    {
        add_menu_page('Generate Thumbnails', 'Generate Thumbnails', 'manage_options', 'th_m_generate_thumbnails', [$this, 'renderAdminPage']);
    }

    public function renderAdminPage()
    {
        echo '<h1>Generate Thumbnails</h1>';
    }
}