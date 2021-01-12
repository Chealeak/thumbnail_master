<?php

namespace ThumbnailMaster;

interface Service
{
    public function register();
    public function setPrefix($prefix);
    public function setAdminPage($adminPage);
}