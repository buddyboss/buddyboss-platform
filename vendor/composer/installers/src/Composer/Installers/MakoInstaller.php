<?php

namespace Composer\Installers;

class MakoInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('package' => 'app/packages/{$name}/');
}
