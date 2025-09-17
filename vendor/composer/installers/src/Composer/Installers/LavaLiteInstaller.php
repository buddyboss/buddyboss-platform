<?php

namespace Composer\Installers;

class LavaLiteInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('package' => 'packages/{$vendor}/{$name}/', 'theme' => 'public/themes/{$name}/');
}
