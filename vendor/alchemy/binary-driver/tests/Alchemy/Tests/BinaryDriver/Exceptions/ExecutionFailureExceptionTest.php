<?php

namespace BuddyBossPlatform\Alchemy\Tests\BinaryDriver\Exceptions;

use BuddyBossPlatform\Alchemy\BinaryDriver\BinaryDriverTestCase;
use BuddyBossPlatform\Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use BuddyBossPlatform\Alchemy\BinaryDriver\ProcessRunner;
class ExecutionFailureExceptionTest extends BinaryDriverTestCase
{
    public function getProcessRunner($logger)
    {
        return new ProcessRunner($logger, 'test-runner');
    }
    public function testGetExceptionInfo()
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);
        $process = $this->createProcessMock(1, \false, '--helloworld--', null, "Error Output", \true);
        try {
            $runner->run($process, new \SplObjectStorage(), \false);
            $this->fail('An exception should have been raised');
        } catch (ExecutionFailureException $e) {
            $this->assertEquals("--helloworld--", $e->getCommand());
            $this->assertEquals("Error Output", $e->getErrorOutput());
        }
    }
}
