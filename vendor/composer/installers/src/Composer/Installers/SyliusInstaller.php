<?php

namespace Composer\Installers;

class SyliusInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('theme' => 'themes/{$name}/');
}
