<?php

namespace Composer\Installers;

class ZendInstaller extends \Composer\Installers\BaseInstaller
{
    protected $locations = array('library' => 'library/{$name}/', 'extra' => 'extras/library/{$name}/', 'module' => 'module/{$name}/');
}
