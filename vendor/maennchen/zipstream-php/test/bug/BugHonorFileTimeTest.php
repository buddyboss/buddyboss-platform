<?php

declare (strict_types=1);
namespace BuddyBossPlatform\BugHonorFileTimeTest;

use DateTime;
use function fopen;
use BuddyBossPlatform\PHPUnit\Framework\TestCase;
use BuddyBossPlatform\ZipStream\Option\Archive;
use BuddyBossPlatform\ZipStream\Option\File;
use BuddyBossPlatform\ZipStream\ZipStream;
/**
 * Asserts that specified last-modified timestamps are not overwritten when a
 * file is added
 */
class BugHonorFileTimeTest extends TestCase
{
    public function testHonorsFileTime() : void
    {
        $archiveOpt = new Archive();
        $fileOpt = new File();
        $expectedTime = new DateTime('2019-04-21T19:25:00-0800');
        $archiveOpt->setOutputStream(fopen('php://memory', 'wb'));
        $fileOpt->setTime(clone $expectedTime);
        $zip = new ZipStream(null, $archiveOpt);
        $zip->addFile('sample.txt', 'Sample', $fileOpt);
        $zip->finish();
        $this->assertEquals($expectedTime, $fileOpt->getTime());
    }
}
