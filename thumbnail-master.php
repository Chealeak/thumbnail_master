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

final class ThumbnailMaster
{
    const PLUGIN_PREFIX = 'th_m_';
    const ADMIN_PAGE = 'setting-admin';
    const DB_OPTION_ENABLED_IMAGE_SIZES = self::PLUGIN_PREFIX . 'enabled_image_sizes';

    private static $instance;
    private $container;
    private $enabledImageSizes = [];

    public function __construct()
    {
        if (!class_exists('DI\Container')) {
            // exception
        }

        $this->container = new DI\Container();

        $this->registerServices();
        $this->setEnabledImageSizes();
    }

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setEnabledImageSizes()
    {
        if ($dbEnabledImageSizes = get_option(self::DB_OPTION_ENABLED_IMAGE_SIZES)) {
            $this->enabledImageSizes = $dbEnabledImageSizes;
        } else {
            if (!empty(get_intermediate_image_sizes())) {
                $this->enabledImageSizes = get_intermediate_image_sizes();
            }

            global $_wp_additional_image_sizes;
            if (!empty($_wp_additional_image_sizes)) {
                $this->enabledImageSizes = array_merge($this->enabledImageSizes, $_wp_additional_image_sizes);
            }
        }
    }

    public function getEnabledImageSizes()
    {
        return $this->enabledImageSizes;
    }

    public function toggleThumbnailActivation($thumbnailName)
    {
        if (in_array($thumbnailName, $this->enabledImageSizes)) {
            $this->enabledImageSizes = array_diff($this->enabledImageSizes, [$thumbnailName]);
        } else {
            $this->enabledImageSizes[] = $thumbnailName;
        }

        update_option(self::DB_OPTION_ENABLED_IMAGE_SIZES, $this->enabledImageSizes, false);
        $dbEnabledImageSizes = get_option(self::DB_OPTION_ENABLED_IMAGE_SIZES);

        return in_array($thumbnailName, $dbEnabledImageSizes) ? 'enabled' : 'disabled';
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

    private function registerServices()
    {
        $serviceNamespace = "ThumbnailMaster\\Services\\";

        $serviceFileNames = array_diff(scandir(__DIR__ . '/Services'), ['.', '..']);
        foreach ($serviceFileNames as $serviceFileName) {
            $serviceClassName = basename($serviceFileName, '.php');
            $serviceNameWithNamespace = $serviceNamespace . $serviceClassName;

            if (class_exists($serviceNameWithNamespace)) {
                $service = $this->container->get($serviceNameWithNamespace);
                $service->register(self::PLUGIN_PREFIX, self::ADMIN_PAGE);
            }
        }
    }
}

if (class_exists('ThumbnailMaster')) {
    $thumbnailMaster = ThumbnailMaster::getInstance();

    register_activation_hook(__FILE__, [$thumbnailMaster, 'activate']);
    register_deactivation_hook(__FILE__, [$thumbnailMaster, 'deactivate']);
}