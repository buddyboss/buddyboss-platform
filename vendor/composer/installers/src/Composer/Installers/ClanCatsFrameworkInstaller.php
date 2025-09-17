<?php

namespace Composer\Installers;

class ClanCatsFrameworkInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('ship' => 'CCF/orbit/{$name}/', 'theme' => 'CCF/app/themes/{$name}/');
}
