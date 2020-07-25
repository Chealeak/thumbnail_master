<?php
/**
 * @package ThumbnailMaster
 */
/*
Plugin Name: Thumbnail Master
Plugin URI:
Description: Regenerate thumbnails, clear from redundant thumbnails, generate only necessary thumbnails.
Version: 1.0.0
Author: Andrew Tolpeko
License: GPLv2 or later
Text Domain: thumbnail-master
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2015 Automattic, Inc.
*/

if (!function_exists('add_action')) {
    echo 'You can\'t access this file!';
    exit;
}

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

class ThumbnailMaster
{
    private $services = [];

    public function __construct()
    {
        $this->createServiceContainer();
        $this->registerServices();
    }

    private function activate()
    {
        // activate plugin logic
    }

    private function deactivate()
    {
        // deactivate plugin logic
    }

    private function uninstall()
    {
        // uninstall plugin logic
    }

    private function createServiceContainer()
    {
        $serviceNamespace = "ThumbnailMaster\\Services\\";

        $serviceFileNames = array_diff(scandir(__DIR__ . '/Services'), ['.', '..']);
        foreach ($serviceFileNames as $serviceFileName) {
            $serviceClassName = basename($serviceFileName, '.php');
            $serviceNameWithNamespace = $serviceNamespace . $serviceClassName;

            if (class_exists($serviceNameWithNamespace)) {
                $service = new $serviceNameWithNamespace();
                $this->services[] = $service;
            }
        }
    }

    private function registerServices()
    {
        foreach ($this->services as $service) {
            $service->register();
        }
    }
}

if (class_exists('ThumbnailMaster')) {
    $thumbnailMaster = new ThumbnailMaster();
}

register_activation_hook(__FILE__, [$thumbnailMaster, 'activate']);
register_deactivation_hook(__FILE__, [$thumbnailMaster, 'deactivate']);