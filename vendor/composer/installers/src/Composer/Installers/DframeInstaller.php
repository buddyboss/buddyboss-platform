<?php

namespace Composer\Installers;

class DframeInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('module' => 'modules/{$vendor}/{$name}/');
}
