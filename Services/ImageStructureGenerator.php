<?php

namespace ThumbnailMaster\Services;

use ThumbnailMaster\Service;

class ImageStructureGenerator extends Service
{
    const RETINA_IMAGE_RATIO = 2;

    const SCREEN_SMALL_MOBILE = 'small_mobile';
    const SCREEN_MOBILE = 'mobile';
    const SCREEN_TABLET = 'tablet';
    const SCREEN_DESKTOP = 'desktop';
    const SCREEN_LARGE = 'large';
    const SCREEN_EXTRA_LARGE = 'extra_large';

    const SCREEN_SMALL_MOBILE_SIZE = '375.99px';
    const SCREEN_MOBILE_SIZE = '767.99px';
    const SCREEN_TABLET_SIZE = '991.99px';
    const SCREEN_DESKTOP_SIZE = '1199.99px';
    const SCREEN_LARGE_SIZE = '1920.99px';
    const SCREEN_EXTRA_LARGE_SIZE = '1921px';

    public function register(string $prefix, string $textDomain, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->textDomain = $textDomain;
        $this->adminPage = $adminPage;

        add_theme_support('post-thumbnails');

        $this->createImageLayoutShortcode();
    }

    private function createImageLayoutShortcode()
    {
        add_shortcode('master_image_layout', [$this, 'createImageLayout']);
    }

    public function createImageLayout($atts)
    {
        $imageLayout = '';

        if (isset($atts['image_id']) && isset($atts['thumbnail_name'])) {
            $imageSizeGroup = $this->getThumbnailSizeGroup($atts['image_id'], $atts['thumbnail_name']);

            if (!empty($imageSizeGroup)) {
                $pictureTagClassesHtmlPart = $atts['picture_classes'] ? 'classes=' . strip_tags($atts['picture_classes']) : '';
                $imageLayout .= '<picture ' . $pictureTagClassesHtmlPart . '>';

                $desktopSrc = '';
                foreach ($imageSizeGroup as $screenName => $imageSizeInfo) {
                    $screenWidth = constant('self::' . strtoupper('SCREEN_' . $screenName . '_SIZE'));
                    $imageLayout .= '<source media="(max-width: ' . $screenWidth . ')" srcset="' . $imageSizeInfo->srcset_1x . ', ' . $imageSizeInfo->srcset_2x . ' 2x">';
                    if ($screenName === self::SCREEN_DESKTOP) {
                        $desktopSrc = $imageSizeInfo->srcset_1x;
                    }
                }
                $imgTagClassesHtmlPart = $atts['img_classes'] ? 'classes=' . strip_tags($atts['img_classes']) : '';
                $altText = get_post_meta($atts['image_id'], '_wp_attachment_image_alt', true);
                $imageLayout .= '<img ' . $imgTagClassesHtmlPart . ' src="' . $desktopSrc . '" alt="' . $altText . '">';

                $imageLayout .= '</picture>';
            }
        }

        echo $imageLayout;
    }

    public function addThumbnailSize(string $name, int $width, int $height, $crop)
    {
        $this->addRegularThumbnailSize($name, $width, $height, $crop);
        $this->addRetinaThumbnailSize($name, $width, $height, $crop);
    }

    private function addRegularThumbnailSize(string $name, int $width, int $height, $crop)
    {
        add_image_size($name, $width, $height, $crop);
    }

    private function addRetinaThumbnailSize(string $name, int $width, int $height, $crop)
    {
        $retinaRatio = self::RETINA_IMAGE_RATIO;

        add_image_size("{$name}@{$retinaRatio}x", $width * $retinaRatio, $height * $retinaRatio, $crop);
    }

    public function getPostThumbnailInfoById(int $thumbnailId, string $size)
    {
        $imageInfo = new \stdClass();
        $retinaRatio = self::RETINA_IMAGE_RATIO;

        $imageInfo->srcset_1x = $thumbnailId ? wp_get_attachment_image_src($thumbnailId, $size)[0] : null;
        $imageInfo->srcset_2x = $thumbnailId ? wp_get_attachment_image_src($thumbnailId, "{$size}@{$retinaRatio}x")[0] : null;

        $imageInfo->alt = $thumbnailId ? get_post_meta($thumbnailId, '_wp_attachment_image_alt', true) : null;

        return $imageInfo;
    }

    public function getThumbnailSizeGroup($imageId, $thumbnailName) {
        return [
            self::SCREEN_SMALL_MOBILE => $this->getPostThumbnailInfoById($imageId, $thumbnailName . '_' . self::SCREEN_SMALL_MOBILE),
            self::SCREEN_MOBILE => $this->getPostThumbnailInfoById($imageId, $thumbnailName . '_' . self::SCREEN_MOBILE),
            self::SCREEN_TABLET => $this->getPostThumbnailInfoById($imageId, $thumbnailName . '_' . self::SCREEN_TABLET),
            self::SCREEN_DESKTOP => $this->getPostThumbnailInfoById($imageId, $thumbnailName . '_' . self::SCREEN_DESKTOP),
            self::SCREEN_LARGE => $this->getPostThumbnailInfoById($imageId, $thumbnailName . '_' . self::SCREEN_LARGE),
            self::SCREEN_EXTRA_LARGE => $this->getPostThumbnailInfoById($imageId, $thumbnailName . '_' . self::SCREEN_EXTRA_LARGE)
        ];
    }
}