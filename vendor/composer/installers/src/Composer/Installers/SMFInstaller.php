<?php

namespace Composer\Installers;

class SMFInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'Sources/{$name}/', 'theme' => 'Themes/{$name}/');
}
