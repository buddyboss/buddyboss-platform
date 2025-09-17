<?php

namespace Composer\Installers;

class ElggInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('plugin' => 'mod/{$name}/');
}
