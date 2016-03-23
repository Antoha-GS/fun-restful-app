<?php

namespace App\Controllers;

use App\Documents\Post;
use Components\FileUploader;
use Silex\Application;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * PostController.
 *
 * @package App\Controllers
 */
class PostController
{
    private $app;

    /**
     * PostsController constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Processes the route POST /post.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws BadRequestHttpException When validation errors occur.
     * @throws FileException           For any reason when the image could not have been uploaded
     */
    public function createPostAction(Request $request)
    {
        $post = new Post();
        $form = $this->createForm($post);

        $form->handleRequest($request);

        if (!$form->isValid()) {
            return new JsonResponse(['message' => $this->getErrorMessages($form)], Response::HTTP_BAD_REQUEST);
        }

        $this->uploadPost($post);
        $post->setCreatedAt(new \DateTime());

        $this->app['doctrine.odm.mongodb.dm']->persist($post);
        $this->app['doctrine.odm.mongodb.dm']->flush($post);

        return $this->buildResponse($post, Response::HTTP_CREATED);
    }

    /**
     * Creates and returns the Form object.
     *
     * @param mixed $data The initial data for the form
     *
     * @return Form
     */
    private function createForm($data)
    {
        return $this->app['form.factory']
            ->createBuilder(FormType::class, $data, ['csrf_protection' => false, 'allow_extra_fields' => true])
            ->add('image', FileType::class)
            ->add('author', TextType::class)
            ->add('tags', TextType::class)
            ->getForm();
    }

    /**
     * Gets all validation errors recursively from the Form object.
     *
     * @param FormInterface $form
     *
     * @return array
     */
    private function getErrorMessages(FormInterface $form) {
        $errors = [];

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            /** @var FormInterface $child */
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    /**
     * Processes uploading of the Post.
     *
     * @param Post $post
     *
     * @throws BadRequestHttpException When has no image file
     * @throws FileException           For any reason when the image could not have been uploaded
     */
    private function uploadPost(Post $post)
    {
        if (null === $post->getImage()) {
            throw new BadRequestHttpException('Image not found.');
        }
        $uploaderConfig = $this->app['config.upload'];
        $uploaderConfig['path'] = $this->resolveImagePathForPost($post);
        /** @var FileUploader $uploader */
        $uploader = $this->app['services.file_uploader_factory']($uploaderConfig);
        $imageName = $uploader->upload($post->getImage());
        $post->setImageName($imageName);
        $post->setImage(null);
    }

    /**
     * Processes the route GET /post/{id}.
     *
     * @param mixed $id
     *
     * @return JsonResponse
     *
     * @throws NotFoundHttpException When Post could not be resolved
     */
    public function getPostAction($id)
    {
        $post = $this->resolvePostById($id);
        return $this->buildResponse($post);
    }

    /**
     * Processes the route POST /post/{id}/like.
     *
     * @param mixed $id
     *
     * @return JsonResponse
     *
     * @throws NotFoundHttpException When Post could not be resolved
     */
    public function likePostAction($id)
    {
        $post = $this->resolvePostById($id);
        $post->like();
        $this->app['doctrine.odm.mongodb.dm']->flush($post);
        return $this->buildResponse($post);
    }

    /**
     * Processes the route DELETE /post/{id}.
     *
     * @param mixed $id
     *
     * @return JsonResponse
     *
     * @throws NotFoundHttpException When Post could not be resolved
     */
    public function deletePostAction($id)
    {
        try {
            $post = $this->resolvePostById($id);
            $this->app['doctrine.odm.mongodb.dm']->remove($post);
        } catch (NotFoundHttpException $e) {
            $this->app['logger']->debug('Trying to remove post that not found.');
        }
        return $this->buildResponse();
    }

    /**
     * Fetches and returns Post object by its identifier.
     *
     * @param mixed $id
     *
     * @return Post
     *
     * @throws NotFoundHttpException When Post could not be resolved
     */
    private function resolvePostById($id)
    {
        $post = $this->app['doctrine.odm.mongodb.dm']->getRepository(Post::class)->find($id);
        if (null === $post) {
            throw new NotFoundHttpException(sprintf('Post with id %s could not be found.', $id));
        }
        return $post;
    }

    /**
     * Processes the route GET /posts.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getPostCollectionAction(Request $request)
    {
        // start with 1
        $page = max((int)$request->get('page', 1), 1);
        // minimum 1, maximum 100
        $perPage = max(min((int)$request->get('per_page', 20), 100), 1);
        $skip = ($page - 1) * $perPage;
        $posts = $this->app['doctrine.odm.mongodb.dm']->getRepository(
            Post::class)->findBy([], ['createdAt' => -1], $perPage, $skip);
        return $this->buildResponse($posts);
    }

    /**
     * Builds and returns the Response object represents an HTTP response in JSON format.
     *
     * @param Post|Post[]|null $postOrPostCollection
     * @param int              $status
     * @param array            $headers
     *
     * @return Response
     */
    private function buildResponse($postOrPostCollection = null, $status = 200, array $headers = [])
    {
        if (null === $postOrPostCollection) {
            return new Response();
        }

        $data = array_map(function (Post $post) {
            return [
                'id' => $post->getId(),
                'image' => $this->resolveImagePathForPost($post),
                'author' => $post->getAuthor(),
                'tags' => $post->getTags() ,
                'likes' => $post->getLikes() ,
                'createdAt' => $post->getCreatedAt() ? $post->getCreatedAt()->getTimestamp() : null,
            ];
        }, is_array($postOrPostCollection) ? $postOrPostCollection : [$postOrPostCollection]);

        return new JsonResponse(count($data) === 1 ? $data[0] : $data, $status, $headers);
    }

    /**
     * Resolves and returns web path to image corresponding to given resource `Post` by configured template.
     *
     * @param Post $post
     *
     * @return string The web path to image
     */
    private function resolveImagePathForPost(Post $post)
    {
        return preg_replace_callback('/{(\w+)}/', function ($matches) use ($post) {
            $propertyGetter = 'get' . $matches[1];
            if (method_exists($post, $propertyGetter)) {
                return preg_replace('/\W/', '_', strtolower((string)$post->$propertyGetter()));
            }
            return $matches[1];
        }, $this->app['config.upload']['path']) . '/' . $post->getImageName();
    }
}