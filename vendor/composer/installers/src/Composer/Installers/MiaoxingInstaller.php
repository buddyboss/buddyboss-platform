<?php

namespace Composer\Installers;

class MiaoxingInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('plugin' => 'plugins/{$name}/');
}
