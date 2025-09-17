<?php

namespace Composer\Installers;

class PortoInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('container' => 'app/Containers/{$name}/');
}
