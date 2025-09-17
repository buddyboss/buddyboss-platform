<?php

namespace BuddyBossPlatform\Alchemy\Tests\BinaryDriver;

use BuddyBossPlatform\Alchemy\BinaryDriver\ProcessBuilderFactory;
class LTSProcessBuilderFactoryTest extends AbstractProcessBuilderFactoryTest
{
    public function setUp()
    {
        if (!\class_exists('BuddyBossPlatform\\Symfony\\Component\\Process\\ProcessBuilder')) {
            $this->markTestSkipped('ProcessBuilder is not available.');
            return;
        }
        parent::setUp();
    }
    protected function getProcessBuilderFactory($binary)
    {
        $factory = new ProcessBuilderFactory($binary);
        $factory->setBuilder(new LTSProcessBuilder());
        ProcessBuilderFactory::$emulateSfLTS = \false;
        $factory->useBinary($binary);
        return $factory;
    }
}
