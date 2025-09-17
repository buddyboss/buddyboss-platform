<?php

namespace Composer\Installers;

class CiviCrmInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('ext' => 'ext/{$name}/');
}
