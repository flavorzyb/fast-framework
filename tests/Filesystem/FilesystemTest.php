<?php

namespace Fast\Tests\Filesystem;

use Mockery as m;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Fast\Filesystem\Filesystem;

class FilesystemTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root = null;
    private $tempDir = 'tmp';
    private $filePath = '';
    public function setUp()
    {
        $this->root = vfsStream::setup($this->tempDir);
        $this->filePath = vfsStream::url($this->tempDir.'/file.txt');
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @throws \Fast\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetRetrievesFiles()
    {
        file_put_contents($this->filePath, 'Hello World');

        $files = new Filesystem;
        $this->assertEquals('Hello World', $files->get($this->filePath));
    }

    public function testPutStoresFiles()
    {
        $files = new Filesystem;
        $files->put($this->filePath, 'Hello World');
        $this->assertStringEqualsFile($this->filePath, 'Hello World');
    }

    public function testSetChmod()
    {
        file_put_contents($this->filePath, 'Hello World');
        $files = new Filesystem;
        $files->chmod($this->filePath, 0755);
        $filePermission = substr(sprintf('%o', fileperms($this->filePath)), -4);
        $this->assertEquals('0755', $filePermission);
    }

    public function testGetChmod()
    {
        file_put_contents($this->filePath, 'Hello World');
        chmod($this->filePath, 0755);

        $files = new Filesystem;
        $filePermisson = $files->chmod($this->filePath);
        $this->assertEquals('0755', $filePermisson);
    }

    public function testDeleteRemovesFiles()
    {
        file_put_contents(vfsStream::url($this->tempDir.'/file1.txt'), 'Hello World');
        file_put_contents(vfsStream::url($this->tempDir.'/file2.txt'), 'Hello World');
        file_put_contents(vfsStream::url($this->tempDir.'/file3.txt'), 'Hello World');

        $files = new Filesystem;
        $files->delete(vfsStream::url($this->tempDir.'/file1.txt'));
        $this->assertFileNotExists(vfsStream::url($this->tempDir.'/file1.txt'));

        $files->delete([vfsStream::url($this->tempDir.'/file2.txt'), vfsStream::url($this->tempDir.'/file3.txt')]);
        $this->assertFileNotExists(vfsStream::url($this->tempDir.'/file1.txt'));
        $this->assertFileNotExists(vfsStream::url($this->tempDir.'/file1.txt'));
    }

    /**
     * @throws \Fast\Contracts\Filesystem\FileNotFoundException
     */
    public function testPrependExistingFiles()
    {
        $files = new Filesystem;
        $files->put($this->filePath, 'World');
        $files->prepend($this->filePath, 'Hello ');
        $this->assertStringEqualsFile($this->filePath, 'Hello World');
    }

    /**
     * @throws \Fast\Contracts\Filesystem\FileNotFoundException
     */
    public function testPrependNewFiles()
    {
        $files = new Filesystem;
        $files->prepend($this->filePath, 'Hello World');
        $this->assertStringEqualsFile($this->filePath, 'Hello World');
    }

    public function testDeleteDirectory()
    {
        $dir = vfsStream::url($this->tempDir . '/foo');
        mkdir($dir);
        mkdir($dir . '/test');
        mkdir($dir . '/test/test');
        file_put_contents($dir . '/file.txt', 'Hello World');
        file_put_contents($dir . '/test/test/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->deleteDirectory($dir);
        $this->assertDirectoryNotExists($dir);
        $this->assertFileNotExists($dir . '/file.txt');
    }

    public function testDeleteDirectoryReturnFalseWhenNotADirectory()
    {
        $dir = vfsStream::url($this->tempDir . '/foo');
        mkdir($dir);
        file_put_contents($dir .'/file.txt', 'Hello World');
        $files = new Filesystem;
        $this->assertFalse($files->deleteDirectory($dir .'/file.txt'));
    }

    public function testCleanDirectory()
    {
        $dir = vfsStream::url($this->tempDir . '/foo');
        mkdir($dir);
        file_put_contents($dir.'/file.txt', 'Hello World');
        $files = new Filesystem;
        $files->cleanDirectory($dir);
        $this->assertDirectoryExists($dir);
        $this->assertFileNotExists($dir .'/file.txt');
    }

    public function testFilesMethod()
    {
        $dir = vfsStream::url($this->tempDir . '/foo');
        mkdir($dir);
        file_put_contents($dir.'/1.txt', '1');
        file_put_contents($dir.'/2.txt', '2');
        mkdir($dir .'/bar');
        $files = new Filesystem;
        $results = $files->files($dir);
        $this->assertInstanceOf('SplFileInfo', $results[0]);
        $this->assertInstanceOf('SplFileInfo', $results[1]);
    }

    public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
    {
        $files = new Filesystem;
        $this->assertFalse($files->copyDirectory(vfsStream::url($this->tempDir.'/foo/bar/baz/breeze/boom'), vfsStream::url($this->tempDir)));
    }

    public function testCopyDirectoryMovesEntireDirectory()
    {
        $tmp = vfsStream::url($this->tempDir . '/tmp');
        $tmp2 = vfsStream::url($this->tempDir . '/tmp2');

        mkdir($tmp, 0777, true);
        file_put_contents($tmp . '/foo.txt', '');
        file_put_contents($tmp.'/bar.txt', '');
        mkdir($tmp . '/nested', 0777, true);
        file_put_contents($tmp.'/nested/baz.txt', '');

        $files = new Filesystem;
        $files->copyDirectory($tmp, $tmp2);
        $this->assertDirectoryExists($tmp2);
        $this->assertFileExists($tmp2 . '/foo.txt');
        $this->assertFileExists($tmp2 . '/bar.txt');
        $this->assertDirectoryExists($tmp2 . '/nested');
        $this->assertFileExists($tmp2.'/nested/baz.txt');
    }

    public function testMoveDirectoryMovesEntireDirectory()
    {
        $tmp = vfsStream::url($this->tempDir . '/tmp');
        $tmp2 = vfsStream::url($this->tempDir . '/tmp2');

        mkdir($tmp, 0777, true);
        file_put_contents($tmp.'/foo.txt', '');
        file_put_contents($tmp.'/bar.txt', '');
        mkdir($tmp.'/nested', 0777, true);
        file_put_contents($tmp.'/nested/baz.txt', '');

        $files = new Filesystem;
        $files->moveDirectory($tmp, $tmp2);
        $this->assertDirectoryExists($tmp2);
        $this->assertFileExists($tmp2.'/foo.txt');
        $this->assertFileExists($tmp2.'/bar.txt');
        $this->assertDirectoryExists($tmp2.'/nested');
        $this->assertFileExists($tmp2.'/nested/baz.txt');
        $this->assertDirectoryNotExists($tmp);
    }

    public function testMoveDirectoryMovesEntireDirectoryAndOverwrites()
    {
        $tmp = vfsStream::url($this->tempDir . '/tmp');
        $tmp2 = vfsStream::url($this->tempDir . '/tmp2');

        mkdir($tmp, 0777, true);
        file_put_contents($tmp.'/foo.txt', '');
        file_put_contents($tmp.'/bar.txt', '');
        mkdir($tmp.'/nested', 0777, true);
        file_put_contents($tmp.'/nested/baz.txt', '');
        mkdir($tmp2, 0777, true);
        file_put_contents($tmp2.'/foo2.txt', '');
        file_put_contents($tmp2.'/bar2.txt', '');

        $files = new Filesystem;
        $files->moveDirectory($tmp, $tmp2, true);
        $this->assertDirectoryExists($tmp2);
        $this->assertFileExists($tmp2.'/foo.txt');
        $this->assertFileExists($tmp2.'/bar.txt');
        $this->assertDirectoryExists($tmp2.'/nested');
        $this->assertFileExists($tmp2.'/nested/baz.txt');
        $this->assertFileNotExists($tmp2.'/foo2.txt');
        $this->assertFileNotExists($tmp2.'/bar2.txt');
        $this->assertDirectoryNotExists($tmp);
    }

    public function testMoveDirectoryReturnsFalseWhileOverwritingAndUnableToDeleteDestinationDirectory()
    {
        $tmp = vfsStream::url($this->tempDir . '/tmp');
        $tmp2 = vfsStream::url($this->tempDir . '/tmp2');

        mkdir($tmp, 0777, true);
        file_put_contents($tmp.'/foo.txt', '');
        mkdir($tmp2, 0777, true);

        $files = m::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('deleteDirectory')->once()->andReturn(false);
        $this->assertFalse($files->moveDirectory($tmp, $tmp2, true));
    }

    /**
     * @expectedException \Fast\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem;
        $files->get(vfsStream::url($this->tempDir.'/unknown-file.txt'));
    }

    /**
     * @throws \Fast\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetRequireReturnsProperly()
    {
        $file = vfsStream::url($this->tempDir.'/file.php');
        file_put_contents($file, '<?php return "Howdy?"; ?>');
        $files = new Filesystem;
        $this->assertEquals('Howdy?', $files->getRequire($file));
    }

    /**
     * @expectedException \Fast\Contracts\Filesystem\FileNotFoundException
     */
    public function testGetRequireThrowsExceptionNonexisitingFile()
    {
        $files = new Filesystem;
        $files->getRequire(vfsStream::url($this->tempDir.'/file.php'));
    }

    public function testAppendAddsDataToFile()
    {
        file_put_contents($this->filePath, 'foo');
        $files = new Filesystem;
        $bytesWritten = $files->append($this->filePath, 'bar');
        $this->assertEquals(mb_strlen('bar', '8bit'), $bytesWritten);
        $this->assertFileExists($this->filePath);
        $this->assertStringEqualsFile($this->filePath, 'foobar');
    }

    public function testMoveMovesFiles()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        $bar = vfsStream::url($this->tempDir . '/bar.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        $files->move($path, $bar);
        $this->assertFileExists($bar);
        $this->assertFileNotExists($path);
    }

    public function testNameReturnsName()
    {
        $path = vfsStream::url($this->tempDir . '/foobar.txt');
        file_put_contents($path, 'foo');
        $filesystem = new Filesystem;
        $this->assertEquals('foobar', $filesystem->name($path));
    }

    public function testExtensionReturnsExtension()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        $this->assertEquals('txt', $files->extension($path));
    }

    public function testBasenameReturnsBasename()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        $this->assertEquals('foo.txt', $files->basename($path));
    }

    public function testDirNameReturnsDirectory()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        $this->assertEquals(vfsStream::url($this->tempDir), $files->dirname($path));
    }

    public function testTypeIdentifiesFile()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        $this->assertEquals('file', $files->type($path));
    }

    public function testTypeIdentifiesDirectory()
    {
        $path = vfsStream::url($this->tempDir.'/foo');
        mkdir($path);
        $files = new Filesystem;
        $this->assertEquals('dir', $files->type($path));
    }

    public function testSizeOutputsSize()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        $size = file_put_contents($path, 'foo');
        $files = new Filesystem;
        $this->assertEquals($size, $files->size($path));
    }

    /**
     * @requires extension fileinfo
     */
    public function testMimeTypeOutputsMimeType()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        $this->assertEquals('text/plain', $files->mimeType($path));
    }

    public function testIsWritable()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        @chmod($path, 0444);
        $this->assertFalse($files->isWritable($path));
        @chmod($path, 0777);
        $this->assertTrue($files->isWritable($path));
    }

    public function testIsReadable()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        file_put_contents($path, 'foo');
        $files = new Filesystem;
        // chmod is noneffective on Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->assertTrue($files->isReadable($path));
        } else {
            @chmod($path, 0000);
            $this->assertFalse($files->isReadable($path));
            @chmod($path, 0777);
            $this->assertTrue($files->isReadable($path));
        }
        $this->assertFalse($files->isReadable(vfsStream::url($this->tempDir.'/doesnotexist.txt')));
    }

    public function testGlobFindsFiles()
    {
        $tmp = __DIR__.'/tmp';
        @mkdir($tmp);
        file_put_contents($tmp . '/foo.txt', 'foo');
        file_put_contents($tmp . '/bar.txt', 'bar');
        $files = new Filesystem;
        $glob = $files->glob($tmp.'/*.txt');
        $this->assertContains($tmp . '/foo.txt', $glob);
        $this->assertContains($tmp . '/bar.txt', $glob);
        $files->deleteDirectory($tmp);
    }

    public function testAllFilesFindsFiles()
    {
        $path = vfsStream::url($this->tempDir . '/foo.txt');
        $bar = vfsStream::url($this->tempDir . '/bar.txt');
        file_put_contents($path, 'foo');
        file_put_contents($bar, 'bar');
        $files = new Filesystem;
        $allFiles = [];
        foreach ($files->allFiles(vfsStream::url($this->tempDir)) as $file) {
            $allFiles[] = $file->getFilename();
        }
        $this->assertContains('foo.txt', $allFiles);
        $this->assertContains('bar.txt', $allFiles);
    }

    public function testDirectoriesFindsDirectories()
    {
        $foo = vfsStream::url($this->tempDir.'/foo');
        $bar = vfsStream::url($this->tempDir.'/bar');
        $tmp = vfsStream::url($this->tempDir);
        mkdir($foo);
        mkdir($bar);
        $files = new Filesystem;
        $directories = $files->directories($tmp);
        $this->assertContains($tmp.DIRECTORY_SEPARATOR.'foo', $directories);
        $this->assertContains($tmp.DIRECTORY_SEPARATOR.'bar', $directories);
    }

    public function testMakeDirectory()
    {
        $foo = vfsStream::url($this->tempDir.'/foo');
        $files = new Filesystem;
        $this->assertTrue($files->makeDirectory($foo));
        $this->assertFileExists($foo);

        $forceDir = vfsStream::url($this->tempDir.'/ggg/test/ddd/ppp');
        $files = new Filesystem;
        $this->assertTrue($files->makeDirectory($forceDir, 0755, true, true));
        $this->assertFileExists($forceDir);
    }

    public function testLastModified() {
        $foo = vfsStream::url($this->tempDir.'/foo.txt');
        file_put_contents($foo, 'foo');
        $files = new Filesystem;
        $this->assertTrue($files->lastModified($foo) > 0);
    }

    public function testRequireOnceRequiresFileProperly()
    {
        $path = vfsStream::url($this->tempDir.'/foo');
        $filesystem = new Filesystem;
        mkdir($path);
        file_put_contents($path.'/foo.php', '<?php function random_function_xyz(){};');
        $filesystem->requireOnce($path.'/foo.php');
        file_put_contents($path.'/foo.php', '<?php function random_function_xyz_changed(){};');
        $filesystem->requireOnce($path.'/foo.php');
        $this->assertTrue(function_exists('random_function_xyz'));
        $this->assertFalse(function_exists('random_function_xyz_changed'));
    }

    public function testCopyCopiesFileProperly()
    {
        $path = vfsStream::url($this->tempDir.'/foo');
        $foo = vfsStream::url($this->tempDir.'/foo/foo.txt');
        $foo2 = vfsStream::url($this->tempDir.'/foo/foo2.txt');

        $filesystem = new Filesystem;
        $data = 'contents';
        mkdir($path);
        file_put_contents($foo, $data);
        $filesystem->copy($foo, $foo2);
        $this->assertFileExists($foo2);
        $this->assertEquals($data, file_get_contents($foo2));
    }

    public function testIsFileChecksFilesProperly()
    {
        $path = vfsStream::url($this->tempDir.'/foo');
        $foo = vfsStream::url($this->tempDir.'/foo/foo.txt');

        $filesystem = new Filesystem;
        mkdir($path);
        file_put_contents($foo, 'contents');
        $this->assertTrue($filesystem->isFile($foo));
        $this->assertFalse($filesystem->isFile($path));
    }

    public function testFilesMethodReturnsFileInfoObjects()
    {
        $path = vfsStream::url($this->tempDir.'/foo');
        $file1 = vfsStream::url($this->tempDir.'/foo/1.txt');
        $file2 = vfsStream::url($this->tempDir.'/foo/2.txt');

        mkdir($path);
        file_put_contents($file1, '1');
        file_put_contents($file2, '2');
        mkdir(vfsStream::url($this->tempDir.'/foo/bar'));
        $files = new Filesystem;
        $this->assertContainsOnlyInstancesOf(\SplFileInfo::class, $files->files($path));
    }

    public function testAllFilesReturnsFileInfoObjects()
    {
        file_put_contents(vfsStream::url($this->tempDir.'/foo.txt'), 'foo');
        file_put_contents(vfsStream::url($this->tempDir.'/bar.txt'), 'bar');
        $files = new Filesystem;
        $this->assertContainsOnlyInstancesOf(\SplFileInfo::class, $files->allFiles(vfsStream::url($this->tempDir)));
    }


    public function testHash()
    {
        $path = vfsStream::url($this->tempDir.'/foo.txt');
        file_put_contents($path, 'foo');
        $filesystem = new Filesystem;
        $this->assertEquals('acbd18db4cc2f85cedef654fccc4a4d8', $filesystem->hash($path));
    }
}
