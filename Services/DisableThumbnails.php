<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class DisableThumbnails extends Service
{
    private $dbOptionEnabledImageSizes;
    private $enabledImageSizes = [];

    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;
        $this->dbOptionEnabledImageSizes = $prefix . 'enabled_image_sizes';

        $this->enqueueScriptsAndStyles();
        $this->setAjaxDisableHandler();
        $this->setEnabledImageSizes();
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

    private function setEnabledImageSizes()
    {
        if ($dbEnabledImageSizes = get_option($this->dbOptionEnabledImageSizes)) {
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

    public function disableThumbnail()
    {
        if (isset($_POST['thumbnail_name'])) {

            if (DOING_AJAX) {
                wp_send_json([
                    'status' => $this->toggleThumbnailActivation(filter_var($_POST['thumbnail_name'], FILTER_SANITIZE_STRING))
                ]);

                wp_die();
            }
        }
    }

    public function toggleThumbnailActivation($thumbnailName)
    {
        if (in_array($thumbnailName, $this->enabledImageSizes)) {
            $this->enabledImageSizes = array_diff($this->enabledImageSizes, [$thumbnailName]);
        } else {
            $this->enabledImageSizes[] = $thumbnailName;
        }

        update_option($this->dbOptionEnabledImageSizes, $this->enabledImageSizes, false);
        $dbEnabledImageSizes = get_option($this->dbOptionEnabledImageSizes);

        return in_array($thumbnailName, $dbEnabledImageSizes) ? 'enabled' : 'disabled';
    }
}