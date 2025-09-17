<?php

namespace Composer\Installers;

class PuppetInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}
