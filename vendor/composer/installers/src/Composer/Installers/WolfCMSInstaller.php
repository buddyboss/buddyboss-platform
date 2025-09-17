<?php

namespace Composer\Installers;

class WolfCMSInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('plugin' => 'wolf/plugins/{$name}/');
}
