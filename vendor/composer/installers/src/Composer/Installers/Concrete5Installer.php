<?php

namespace Composer\Installers;

class Concrete5Installer extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('core' => 'concrete/', 'block' => 'application/blocks/{$name}/', 'package' => 'packages/{$name}/', 'theme' => 'application/themes/{$name}/', 'update' => 'updates/{$name}/');
}
