<?php

use Frasmage\Mediable\Media;
use Frasmage\Mediable\Exceptions\MediaUrlException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MediaTest extends TestCase{
    use DatabaseMigrations;

    public function test_it_has_path_accessors(){
        $media = factory(Media::class)->make([
            'disk' => 'tmp',
            'directory' => 'a/b/c',
            'filename' => 'foo.bar',
            'extension' => 'jpg',
        ]);

        $this->assertEquals(storage_path('tmp/a/b/c/foo.bar.jpg'), $media->absolutePath());
        $this->assertEquals(storage_path('tmp/a/b/c'), $media->dirname);
        $this->assertEquals('a/b/c/foo.bar.jpg', $media->diskPath());
        $this->assertEquals('a/b/c', $media->directory);
        $this->assertEquals('foo.bar.jpg', $media->basename);
        $this->assertEquals('foo.bar', $media->filename);
        $this->assertEquals('jpg', $media->extension);
    }

    public function test_it_can_be_queried_by_directory(){
        factory(Media::class)->create(['directory' => 'foo']);
        factory(Media::class)->create(['directory' => 'foo']);
        factory(Media::class)->create(['directory' => 'bar']);
        factory(Media::class)->create(['directory' => 'foo/baz']);

        $this->assertEquals(2, Media::inDirectory('tmp', 'foo')->count());
        $this->assertEquals(1, Media::inDirectory('tmp', 'foo/baz')->count());
    }

    public function test_it_can_be_queried_by_directory_recursively(){
        factory(Media::class)->create(['directory' => 'foo']);
        factory(Media::class)->create(['directory' => 'foo/bar']);
        factory(Media::class)->create(['directory' => 'foo/bar']);
        factory(Media::class)->create(['directory' => 'foo/bar/baz']);

        $this->assertEquals(4, Media::inDirectory('tmp', 'foo', true)->count());
        $this->assertEquals(3, Media::inOrUnderDirectory('tmp', 'foo/bar')->count());
        $this->assertEquals(1, Media::inDirectory('tmp', 'foo/bar/baz', true)->count());
    }

    public function test_it_can_be_queried_by_basename(){
        factory(Media::class)->create(['filename' => 'foo', 'extension' => 'bar']);
        factory(Media::class)->create(['id' => 99, 'filename' => 'baz', 'extension' => 'bat']);
        factory(Media::class)->create(['filename' => 'bar', 'extension' => 'foo']);

        $this->assertEquals(99, Media::whereBasename('baz.bat')->first()->id);
    }

    public function test_it_can_be_queried_by_path_on_disk(){
        factory(Media::class)->create([
            'id' => 4,
            'disk' => 'tmp',
            'directory' => 'foo/bar/baz',
            'filename' => 'bat',
            'extension' => 'jpg'
        ]);
        $this->assertEquals(4, Media::forPathOnDisk('tmp', 'foo/bar/baz/bat.jpg')->first()->id);
    }

    public function test_it_can_view_human_readable_file_size(){
        $media = factory(Media::class)->make(['size' => 0]);

        $this->assertEquals('0 bytes', $media->readableSize());

        $media->size = 1024 * 1024;
        $this->assertEquals('1 MB', $media->readableSize(0));

        $media->size = 1024 * 1024 + 1024 * 100;
        $this->assertEquals('1.1 MB', $media->readableSize(2));
    }

    public function test_it_can_be_checked_for_public_visibility(){
        $media = factory(Media::class)->make(['disk' => 'tmp']);
        $this->assertFalse($media->isPubliclyAccessible());

        $media = factory(Media::class)->make(['disk' => 'uploads']);
        $this->assertTrue($media->isPubliclyAccessible());
    }

    public function test_it_can_generate_its_path_from_the_webroot(){
        $media = factory(Media::class)->make(['disk' => 'uploads', 'directory' => 'foo/bar', 'filename' => 'baz', 'extension' => 'jpg']);
        $this->assertEquals('/uploads/foo/bar/baz.jpg', $media->publicPath());
    }

    public function test_non_public_access_to_public_path_throws_exception(){
        $media = factory(Media::class)->make(['disk' => 'tmp']);
        $this->expectException(MediaUrlException::class);
        $media->publicPath();
    }

    public function test_it_can_generate_a_url_to_the_file(){
        $media = factory(Media::class)->make(['disk' => 'uploads', 'directory' => 'foo/bar', 'filename' => 'baz', 'extension' => 'jpg']);
        $this->assertEquals('/uploads/foo/bar/baz.jpg', $media->url(false));
        $this->assertEquals('http://localhost/uploads/foo/bar/baz.jpg', $media->url(true));
    }

    protected function seedFileForMedia($media){
        app('filesystem')->disk($media->disk)->put($media->diskPath(), '');
    }
}