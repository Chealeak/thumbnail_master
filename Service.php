<?php

namespace ThumbnailMaster;

abstract class Service
{
    protected string $prefix;
    protected string $adminPage;

    public function __construct(string $prefix, string $adminPage)
    {
        $this->prefix = $prefix;
        $this->adminPage = $adminPage;
    }

    abstract public function register();
}