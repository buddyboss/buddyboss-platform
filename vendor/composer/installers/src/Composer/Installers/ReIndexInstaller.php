<?php

namespace Composer\Installers;

class ReIndexInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('theme' => 'themes/{$name}/', 'plugin' => 'plugins/{$name}/');
}
