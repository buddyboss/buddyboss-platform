<?php

namespace Composer\Installers;

class FuelphpInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('component' => 'components/{$name}/');
}
