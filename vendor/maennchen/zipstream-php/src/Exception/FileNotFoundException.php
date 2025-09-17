<?php

declare (strict_types=1);
namespace BuddyBossPlatform\ZipStream\Exception;

use BuddyBossPlatform\ZipStream\Exception;
/**
 * This Exception gets invoked if a file wasn't found
 */
class FileNotFoundException extends Exception
{
    /**
     * Constructor of the Exception
     *
     * @param String $path - The path which wasn't found
     */
    public function __construct(string $path)
    {
        parent::__construct("The file with the path {$path} wasn't found.");
    }
}
