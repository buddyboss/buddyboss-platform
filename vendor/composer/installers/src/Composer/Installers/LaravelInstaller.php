<?php

namespace Composer\Installers;

class LaravelInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('library' => 'libraries/{$name}/');
}
