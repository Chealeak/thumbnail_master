<?php

namespace ThumbnailMaster;

abstract class Service
{
    protected string $prefix;
    protected string $adminPage;
    protected string $dbOptionExistedImageSizes;

    protected $storedThumbnailsInfo;

    public function __construct(Storage $storage)
    {
        add_action('init', function () use ($storage) {
            $this->storedThumbnailsInfo = $storage->getStoredThumbnailsInfo();
        });
    }

    abstract public function register(string $prefix, string $adminPage);
}