<?php

namespace Components;

use Components\FileNameGenerator\FileNameGeneratorInterface;
use Components\FileNameGenerator\SameFileNameGenerator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FileUploader.
 *
 * @package Components
 */
class FileUploader
{
    private $uploadRootDir;
    private $uploadDir;
    private $fileNameGenerator;

    /**
     * FileUploader constructor.
     *
     * @param string                     $uploadRootDir
     * @param string                     $uploadDir
     * @param FileNameGeneratorInterface $fileNameGenerator
     */
    public function __construct($uploadRootDir, $uploadDir, FileNameGeneratorInterface $fileNameGenerator = null)
    {
        $this->uploadRootDir = $uploadRootDir;
        $this->uploadDir = $uploadDir;
        $this->fileNameGenerator = $fileNameGenerator ?: new SameFileNameGenerator();
    }

    /**
     * Moves the uploaded file to a new location.
     *
     * @param UploadedFile $file
     *
     * @return string
     *
     * @throws FileException For any reason when the file could not have been moved
     */
    public function upload(UploadedFile $file)
    {
        $uploadRootDir = $this->getUploadAbsolutePath();
        $this->createDirectoryIfNotExists($uploadRootDir);
        $newFileName = $this->createUniqueNameForFile($file);
        $file->move($uploadRootDir, $newFileName);
        return $newFileName;
    }

    protected function createDirectoryIfNotExists($path, $mode = 0777)
    {
        if (!@mkdir($path, $mode, true) && !is_dir($path)) {
            throw new FileException(sprintf('Unable to create directory %s: %s', $path, error_get_last()['message']));
        }
    }

    protected function createUniqueNameForFile(File $file)
    {
        return $this->fileNameGenerator->generateFileName($file);
    }

    public function getUploadAbsolutePath()
    {
        return $this->uploadRootDir . $this->uploadDir;
    }

    /**
     * @return string
     */
    public function getUploadRootDir()
    {
        return $this->uploadRootDir;
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return $this->uploadDir;
    }
}