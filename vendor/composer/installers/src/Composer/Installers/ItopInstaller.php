<?php

namespace Composer\Installers;

class ItopInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('extension' => 'extensions/{$name}/');
}
