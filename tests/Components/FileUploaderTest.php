<?php

namespace Components;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploaderTest extends \PHPUnit_Framework_TestCase
{
    const TMP_DIR = '/tmp/fun_restful_app/tests';

    private static $embeddedFileName = 'test.jpg';
    private static $embeddedFileDir = ROOT_PATH . '/tests/Components/_data';
    private static $uploadSrcDir = self::TMP_DIR . '/file_uploader_src';
    private static $uploadDstDir = self::TMP_DIR . '/file_uploader_dst';

    /** @var FileUploader */
    private $uploader;

    protected function setUp()
    {
        parent::setUp();
        static::prepareTestFile();
        $this->uploader = new FileUploader(self::TMP_DIR, self::$uploadDstDir);
    }

    protected function tearDown()
    {
        static::clearTempDirectory(self::TMP_DIR);
        parent::tearDown();
    }

    /**
     * @covers \Components\FileUploader::upload()
     */
    public function testUpload()
    {
        $filePath = self::$uploadSrcDir . '/' . self::$embeddedFileName;
        $file = new UploadedFile($filePath, self::$embeddedFileName, null, null, null, true);

        $newFileName = $this->uploader->upload($file);

        static::assertEquals(self::$embeddedFileName, $newFileName);
        static::assertFileExists(self::TMP_DIR . self::$uploadDstDir . '/' . $newFileName);
    }


    private static function prepareTestFile()
    {
        if (!@mkdir(self::$uploadSrcDir, 0777, true) && !is_dir(self::$uploadSrcDir)) {
            throw new \RuntimeException(
                sprintf('Unable to create directory %s: %s', self::$uploadSrcDir, error_get_last()['message']));
        }

        $src = self::$embeddedFileDir . '/' . self::$embeddedFileName;
        $dst = self::$uploadSrcDir . '/' . self::$embeddedFileName;
        if (!@copy($src, $dst)) {
            throw new \RuntimeException(
                sprintf('Unable to copy file %s to %s: %s', $src, $dst, error_get_last()['message']));
        }
    }

    private static function clearTempDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir . '/' . $object)) {
                        static::clearTempDirectory($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}