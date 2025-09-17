<?php

namespace Composer\Installers;

class ZikulaInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'modules/{$vendor}-{$name}/', 'theme' => 'themes/{$vendor}-{$name}/');
}
