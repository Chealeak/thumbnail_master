<?php

namespace ThumbnailMaster;

abstract class Service
{
    protected string $prefix;
    protected string $adminPage;

    abstract public function register(string $prefix, string $adminPage);
}