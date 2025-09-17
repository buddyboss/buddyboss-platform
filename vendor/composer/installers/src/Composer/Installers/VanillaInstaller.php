<?php

namespace Composer\Installers;

class VanillaInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('plugin' => 'plugins/{$name}/', 'theme' => 'themes/{$name}/');
}
