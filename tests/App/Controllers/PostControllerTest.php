<?php

namespace App\Controllers;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use App\Documents\Post;
use Components\FileUploader;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use Silex\Application;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostControllerTest extends PHPUnit_Framework_TestCase
{
    const TMP_DIR = '/tmp/fun_restful_app/tests';

    private static $embeddedFileName = 'test.jpg';
    private static $embeddedFileDir = ROOT_PATH . '/tests/Components/_data';
    private static $uploadSrcDir = self::TMP_DIR . '/file_uploader_src';
    private static $uploadDstDir = self::TMP_DIR . '/file_uploader_dst/{author}';

    /** @var PostController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();

        $app = (new Application())
            ->register(new FormServiceProvider())
            ->register(new ValidatorServiceProvider());
        $app['doctrine.odm.mongodb.dm'] = $this->getDocumentManagerMock();
        $app['logger'] = $this->getLoggerStub();
        $app['config.upload'] = [
            'root_path' => self::TMP_DIR,
            'path' => self::$uploadDstDir,
        ];
        $app['services.file_uploader_factory'] = $app->protect(function (array $config) {
            return new FileUploader(
                rtrim($config['root_path'], '/'),
                trim($config['path'], '/'),
                isset($config['file_name_generator']) ? $config['file_name_generator'] : null
            );
        });

        $this->controller = new PostController($app);
    }

    /**
     * @covers \App\Controllers\PostController::createPostAction()
     */
    public function testCreatePostAction()
    {
        static::clearTempDirectory(self::TMP_DIR);
        static::prepareTestFile();

        $imagePath = self::$uploadSrcDir . '/' . self::$embeddedFileName;
        $image = new UploadedFile($imagePath, self::$embeddedFileName, 'image/jpg', filesize($imagePath), null, true);
        $request = new Request([], [
            'form' => [
                'author' => 'Author New',
                'tags' => '#tag1 #tag2 #tag3',
                'image' => $image,
            ],
        ]);
        $request->setMethod('POST');

        $response = $this->controller->createPostAction($request);

        static::assertInstanceOf(Response::class, $response);
        static::assertEquals(201, $response->getStatusCode());
        static::assertEquals([
            'id' => 'new_id',
            'image' => '/tmp/fun_restful_app/tests/file_uploader_dst/author_new/test.jpg',
            'author' => 'Author New',
            'tags' => ['tag1', 'tag2', 'tag3'],
            'likes' => 0,
            'createdAt' => strtotime('2016-03-22 22:00:22'),
        ], json_decode($response->getContent(), true));

        static::clearTempDirectory(self::TMP_DIR);
    }

    /**
     * @covers \App\Controllers\PostController::getPostAction()
     */
    public function testGetPostAction()
    {
        $response = $this->controller->getPostAction('existing_id_1');
        static::assertInstanceOf(Response::class, $response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals([
            'id' => 'existing_id_1',
            'image' => '/tmp/fun_restful_app/tests/file_uploader_dst/author__1/image_1.jpg',
            'author' => 'Author #1',
            'tags' => ['yet', 'another', 'post'],
            'likes' => 0,
            'createdAt' => strtotime('2016-03-22 22:00:01'),
        ], json_decode($response->getContent(), true));
    }

    /**
     * @covers \App\Controllers\PostController::likePostAction()
     */
    public function testLikePostAction()
    {
        $response = $this->controller->likePostAction('existing_id_1');
        static::assertInstanceOf(Response::class, $response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals([
            'id' => 'existing_id_1',
            'image' => '/tmp/fun_restful_app/tests/file_uploader_dst/author__1/image_1.jpg',
            'author' => 'Author #1',
            'tags' => ['yet', 'another', 'post'],
            'likes' => 1,
            'createdAt' => strtotime('2016-03-22 22:00:01'),
        ], json_decode($response->getContent(), true));
    }

    /**
     * @covers \App\Controllers\PostController::deletePostAction()
     */
    public function testDeletePostAction()
    {
        $response = $this->controller->deletePostAction('existing_id_1');
        static::assertInstanceOf(Response::class, $response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEmpty($response->getContent());
    }

    /**
     * @covers \App\Controllers\PostController::getPostCollectionAction()
     */
    public function testGetPostCollectionAction()
    {
        $request = new Request();
        $response = $this->controller->getPostCollectionAction($request);
        static::assertInstanceOf(Response::class, $response);
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals([
            [
                'id' => 'existing_id_3',
                'image' => '/tmp/fun_restful_app/tests/file_uploader_dst/author__3/image_3.jpg',
                'author' => 'Author #3',
                'tags' => ['yet', 'another', 'post'],
                'likes' => 0,
                'createdAt' => strtotime('2016-03-22 22:00:03'),
            ],
            [
                'id' => 'existing_id_2',
                'image' => '/tmp/fun_restful_app/tests/file_uploader_dst/author__2/image_2.jpg',
                'author' => 'Author #2',
                'tags' => ['yet', 'another', 'post'],
                'likes' => 0,
                'createdAt' => strtotime('2016-03-22 22:00:02'),
            ],
            [
                'id' => 'existing_id_1',
                'image' => '/tmp/fun_restful_app/tests/file_uploader_dst/author__1/image_1.jpg',
                'author' => 'Author #1',
                'tags' => ['yet', 'another', 'post'],
                'likes' => 0,
                'createdAt' => strtotime('2016-03-22 22:00:01'),
            ],
        ], json_decode($response->getContent(), true));
    }


    /**
     * @return DocumentManager
     */
    private function getDocumentManagerMock()
    {
        $dm = $this->getMockBuilder(DocumentManager::class)->disableOriginalConstructor()->getMock();
        $dm->expects(static::any())->method('getRepository')->will(static::returnValue($this->getDocumentRepositoryMock()));
        $dm->expects(static::any())->method('persist')->will(static::returnCallback(function (Post $post) {
            $post->setId('new_id');
            $post->setCreatedAt(new DateTime('2016-03-22 22:00:22'));
        }));
        return $dm;
    }

    private function getDocumentRepositoryMock()
    {
        $rep = $this->getMockBuilder(DocumentRepository::class)->disableOriginalConstructor()->getMock();
        $repository = [
            'existing_id_3' => self::buildPost(3),
            'existing_id_2' => self::buildPost(2),
            'existing_id_1' => self::buildPost(1),
        ];
        $rep->expects(static::any())->method('find')->will(static::returnCallback(function ($id) use ($repository) {
            return isset($repository[$id]) ? $repository[$id] : null;
        }));
        $rep->expects(static::any())->method('findBy')->will(static::returnValue(array_values($repository)));
        return $rep;
    }

    private static function buildPost($number)
    {
        $post = new Post();
        $post->setId('existing_id_' . $number);
        $post->setAuthor('Author #' . $number);
        $post->setImageName('image_' . $number . '.jpg');
        $post->setTags('#yet,#another,#post');
        $post->setCreatedAt(new DateTime('2016-03-22 22:00:0' . $number));
        return $post;
    }

    private function getLoggerStub()
    {
        return new NullLogger();
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