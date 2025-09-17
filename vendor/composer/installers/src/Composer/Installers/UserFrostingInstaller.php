<?php

namespace Composer\Installers;

class UserFrostingInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('sprinkle' => 'app/sprinkles/{$name}/');
}
