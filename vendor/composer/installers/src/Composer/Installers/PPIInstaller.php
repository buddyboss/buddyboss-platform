<?php

namespace Composer\Installers;

class PPIInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}
