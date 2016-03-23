<?php

namespace Components\FileNameGenerator;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Interface for file name generator.
 *
 * @package Components\FileNameGenerator
 */
interface FileNameGeneratorInterface
{
    public function generateFileName(File $file);
}