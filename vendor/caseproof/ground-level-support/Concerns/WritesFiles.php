<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Concerns;

trait WritesFiles
{
    /**
     * Wrapper for native file_put_contents.
     *
     * @param  string $filepath The path to the file to write.
     * @param  string $contents The contents to write.
     * @return integer|false The number of bytes written or false on failure.
     */
    protected function filePutContents(string $filepath, string $contents)
    {
        return \file_put_contents($filepath, $contents);
    }
}
