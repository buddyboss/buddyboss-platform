<?php

namespace BuddyBossPlatform\Alchemy\Tests\BinaryDriver;

use BuddyBossPlatform\Alchemy\BinaryDriver\ProcessBuilderFactory;
class NONLTSProcessBuilderFactoryTest extends AbstractProcessBuilderFactoryTest
{
    protected function getProcessBuilderFactory($binary)
    {
        ProcessBuilderFactory::$emulateSfLTS = \true;
        return new ProcessBuilderFactory($binary);
    }
}
