<?php

namespace Composer\Installers;

/**
 * An installer to handle MODX specifics when installing packages.
 */
class ModxInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('extra' => 'core/packages/{$name}/');
}
