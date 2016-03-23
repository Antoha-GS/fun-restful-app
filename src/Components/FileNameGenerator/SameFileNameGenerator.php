<?php

namespace Components\FileNameGenerator;

use Symfony\Component\HttpFoundation\File\File;

/**
 * File name generator for generate the names are the same as with the given files.
 *
 * @package Components\FileNameGenerator
 */
class SameFileNameGenerator implements FileNameGeneratorInterface
{
    public function generateFileName(File $file)
    {
        return $file->getBasename();
    }
}