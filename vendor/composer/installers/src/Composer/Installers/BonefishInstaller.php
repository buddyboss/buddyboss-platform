<?php

namespace Composer\Installers;

class BonefishInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('package' => 'Packages/{$vendor}/{$name}/');
}
