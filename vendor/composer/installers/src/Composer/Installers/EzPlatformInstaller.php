<?php

namespace Composer\Installers;

class EzPlatformInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('meta-assets' => 'web/assets/ezplatform/', 'assets' => 'web/assets/ezplatform/{$name}/');
}
