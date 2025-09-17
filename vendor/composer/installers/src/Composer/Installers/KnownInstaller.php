<?php

namespace Composer\Installers;

class KnownInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('plugin' => 'IdnoPlugins/{$name}/', 'theme' => 'Themes/{$name}/', 'console' => 'ConsolePlugins/{$name}/');
}
