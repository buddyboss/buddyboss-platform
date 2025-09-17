<?php

namespace Composer\Installers;

class ImageCMSInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('template' => 'templates/{$name}/', 'module' => 'application/modules/{$name}/', 'library' => 'application/libraries/{$name}/');
}
