<?php

namespace Composer\Installers;

class DecibelInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array */
    protected $locations = array('app' => 'app/{$name}/');
}
