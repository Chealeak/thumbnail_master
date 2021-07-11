<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class DisableThumbnails extends Service
{
    public function register(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;
        $this->dbOptionExistedImageSizes = $prefix . 'existed_image_sizes';

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

    public function disableThumbnail()
    {
        if (isset($_POST['thumbnail_name'])) {

            if (DOING_AJAX) {
                wp_send_json([
                    'enabled' => $this->toggleThumbnailActivation(filter_var($_POST['thumbnail_name'], FILTER_SANITIZE_STRING))
                ]);

                wp_die();
            }
        }
    }

    public function toggleThumbnailActivation($thumbnailName)
    {
        $thumbnailEnabled = true;

        if (isset($this->storedThumbnailsInfo[$thumbnailName])) {
            $thumbnailEnabled = $this->storedThumbnailsInfo[$thumbnailName]['enabled'];
            $this->storedThumbnailsInfo[$thumbnailName]['enabled'] = !$thumbnailEnabled;
            update_option($this->dbOptionExistedImageSizes, $this->storedThumbnailsInfo, false);
        }

        $dbExistedThumbnailsInfo = get_option($this->dbOptionExistedImageSizes);
        if (isset($dbExistedThumbnailsInfo[$thumbnailName])) {
            $thumbnailEnabled = $dbExistedThumbnailsInfo[$thumbnailName]['enabled'];
        }

        return $thumbnailEnabled;
    }
}