<?php

$api = $app['controllers_factory'];

// Create resource Post
$api->post('/post', 'controllers.post:createPostAction');

// Fetch resource Post
$api->get('/post/{id}', 'controllers.post:getPostAction');

// Create resource Post.like
$api->post('/post/{id}/like', 'controllers.post:likePostAction');

// Delete resource Post
$api->delete('/post/{id}', 'controllers.post:deletePostAction');

// Fetch collection of resources Post
$api->get('/posts', 'controllers.post:getPostCollectionAction');

$app->mount($app['api.endpoint'] . '/' . $app['api.version'], $api);
