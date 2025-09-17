<?php

namespace Composer\Installers;

class MODULEWorkInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}
