<?php

namespace Composer\Installers;

class KohanaInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}
