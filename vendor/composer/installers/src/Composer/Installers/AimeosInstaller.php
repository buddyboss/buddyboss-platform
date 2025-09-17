<?php

namespace Composer\Installers;

class AimeosInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('extension' => 'ext/{$name}/');
}
