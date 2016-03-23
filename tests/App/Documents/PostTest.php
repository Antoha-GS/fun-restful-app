<?php

namespace App\Documents;


class PostTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers       \App\Documents\Post::setTags()
     * @dataProvider provideSetTagsCases()
     *
     * @param mixed $inputTags
     * @param array $expectedTags
     */
    public function testSetTags($inputTags, $expectedTags)
    {
        $post = new Post();
        $post->setTags($inputTags);
        static::assertEquals($expectedTags, $post->getTags());
    }

    public function provideSetTagsCases()
    {
        return [
            [null, []],
            ['#tag1', ['tag1']],
            ['#tag1, #tag2, #tag3', ['tag1', 'tag2', 'tag3']],
            ['#tag1#tag2#tag3', ['tag1', 'tag2', 'tag3']],
            ['tag1 tag2 tag3  ', ['tag1', 'tag2', 'tag3']],
            [['tag1', '#tag2', '  #tag3  '], ['tag1', 'tag2', 'tag3']],
        ];
    }
}