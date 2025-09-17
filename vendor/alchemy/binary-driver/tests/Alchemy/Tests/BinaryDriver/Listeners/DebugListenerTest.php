<?php

namespace BuddyBossPlatform\Alchemy\Tests\BinaryDriver\Listeners;

use BuddyBossPlatform\Alchemy\BinaryDriver\Listeners\DebugListener;
use BuddyBossPlatform\Symfony\Component\Process\Process;
class DebugListenerTest extends \BuddyBossPlatform\PHPUnit_Framework_TestCase
{
    public function testHandle()
    {
        $listener = new DebugListener();
        $lines = array();
        $listener->on('debug', function ($line) use(&$lines) {
            $lines[] = $line;
        });
        $listener->handle(Process::ERR, "first line\nsecond line");
        $listener->handle(Process::OUT, "cool output");
        $listener->handle('unknown', "lalala");
        $listener->handle(Process::OUT, "another output\n");
        $expected = array('[ERROR] first line', '[ERROR] second line', '[OUT] cool output', '[OUT] another output', '[OUT] ');
        $this->assertEquals($expected, $lines);
    }
}
