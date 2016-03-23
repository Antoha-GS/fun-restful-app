<?php

namespace Components\FileNameGenerator;

use Symfony\Component\HttpFoundation\File\File;

/**
 * File name generator for generate the unique names based on php function `uniqid()`.
 *
 * @package Components\FileNameGenerator
 */
class UniqueFileNameGenerator implements FileNameGeneratorInterface
{
    private $prefix;

    /**
     * UniqueFileNameGenerator constructor.
     *
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function generateFileName(File $file)
    {
        return uniqid($this->prefix, true) . '.' . $file->guessExtension();
    }
}