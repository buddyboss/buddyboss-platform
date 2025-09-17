<?php

namespace Composer\Installers;

class AttogramInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'modules/{$name}/');
}
