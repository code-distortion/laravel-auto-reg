<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Integration;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Tests\Integration\Support\TestInitTrait;
use CodeDistortion\LaravelAutoReg\Tests\LaravelTestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that caching works.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CachingTest extends LaravelTestCase
{
    use TestInitTrait;



    /**
     * Test that the cache is written to and loaded from.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_that_cache_is_created(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1');
        static::runServiceProvider($detect);

        static::assertFalse($detect->wasLoadedFromCache());
        static::assertFalse(file_exists($detect->getMainCachePath()));
        static::assertFalse(file_exists($detect->getMetaCachePath()));

        $detect->loadFresh(true);
        $detect->saveCache();

        static::assertFileExists($detect->getMainCachePath());
        static::assertFileExists($detect->getMetaCachePath());



        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', [], true, false);
        static::runServiceProvider($detect);

        static::assertFileExists($detect->getMainCachePath());
        static::assertFileExists($detect->getMetaCachePath());
        static::assertTrue($detect->wasLoadedFromCache());
    }



    /**
     * Provide data for the test_that_cache_is_fixed_when_corrupt test.
     *
     * @return array[]
     */
    public static function cacheFileReplacementDataProvider(): array
    {
        return [
            'main-cache-path invalid cacheDataVersion value' => [
                'getPathMethod' => 'mainCachePath',
                'search' => "'cacheDataVersion' => " . Settings::CACHE_DATA_VERSION . ",",
                'replace' => "'cacheDataVersion' => 'x',",
            ],
            'main-cache-path missing cacheDataVersion value' => [
                'getPathMethod' => 'mainCachePath',
                'search' => "'cacheDataVersion'",
                'replace' => "'X',",
            ],
            'main-cache-path invalid laravelBaseDir value' => [
                'getPathMethod' => 'mainCachePath',
                'search' => "'laravelBaseDir' => '",
                'replace' => "'laravelBaseDir' => 'a",
            ],
            'main-cache-path missing laravelBaseDir value' => [
                'getPathMethod' => 'mainCachePath',
                'search' => "'laravelBaseDir'",
                'replace' => "'XXX',",
            ],
            'main-cache-path syntax error' => [
                'getPathMethod' => 'mainCachePath',
                'search' => "'cacheDataVersion'",
                'replace' => "',",
            ],

            'meta-cache-path invalid cacheDataVersion value' => [
                'getPathMethod' => 'metaCachePath',
                'search' => "'cacheDataVersion' => " . Settings::CACHE_DATA_VERSION . ",",
                'replace' => "'cacheDataVersion' => 'x',",
            ],
            'meta-cache-path missing cacheDataVersion value' => [
                'getPathMethod' => 'metaCachePath',
                'search' => "'cacheDataVersion'",
                'replace' => "'X',",
            ],
            'meta-cache-path syntax error' => [
                'getPathMethod' => 'metaCachePath',
                'search' => "'cacheDataVersion'",
                'replace' => "',",
            ],
        ];
    }


    /**
     * Test that the cache is replaced when its content is corrupt.
     *
     * @test
     * @dataProvider cacheFileReplacementDataProvider
     *
     * @param string $getPathMethod The cache file to change.
     * @param string $search        The content to replace.
     * @param string $replace       The content to replace with.
     * @return void
     * @throws FileNotFoundException Thrown when a file cannot be read when checking if it has content.
     */
    #[Test]
    #[DataProvider("cacheFileReplacementDataProvider")]
    public static function test_that_cache_is_fixed_when_corrupt(
        string $getPathMethod,
        string $search,
        string $replace
    ): void {

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1');
        static::runServiceProvider($detect);

        static::assertFalse($detect->wasLoadedFromCache());
        static::assertFalse(file_exists($detect->getMainCachePath()));
        static::assertFalse(file_exists($detect->getMetaCachePath()));

        $detect->loadFresh(true);
        $detect->saveCache();

        static::assertFileExists($detect->getMainCachePath());
        static::assertFileExists($detect->getMetaCachePath());

        static::assertTrue(static::fileHasContent(static::$getPathMethod(), $search));
        static::stringReplaceFile(static::$getPathMethod(), $search, $replace);
        static::assertTrue(static::fileHasContent(static::$getPathMethod(), $replace));



        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', [], true, false);
        static::runServiceProvider($detect);

        static::assertFalse($detect->wasLoadedFromCache());
        static::assertTrue(static::fileHasContent(static::$getPathMethod(), $search));
    }



    /**
     * Provide data for the test_that_cache_fixed_when_missing_files test.
     *
     * @return array[]
     */
    public static function cacheFileRemovalDataProvider(): array
    {
        return [
            'main-cache-path missing' => [
                'removePathMethods' => ['mainCachePath'],
                'needMeta' => true,
                'wasLoadedFromCache' => false,
                'willRebuildCache' => false,
            ],
            'main-cache-path missing - no meta' => [
                'removePathMethods' => ['mainCachePath'],
                'needMeta' => false,
                'wasLoadedFromCache' => false,
                'willRebuildCache' => false,
            ],

            'meta-cache-path missing' => [
                'removePathMethods' => ['metaCachePath'],
                'needMeta' => true,
                'wasLoadedFromCache' => false,
                'willRebuildCache' => true,
            ],
            'meta-cache-path missing - no meta' => [
                'removePathMethods' => ['metaCachePath'],
                'needMeta' => false,
                'wasLoadedFromCache' => true,
                'willRebuildCache' => false,
            ],
        ];
    }

    /**
     * Test that the cache is replaced when one of its files is missing.
     *
     * @test
     * @dataProvider cacheFileRemovalDataProvider
     *
     * @param array<int, string> $removePathMethods  The file/s to remove.
     * @param boolean            $needMeta           Should the meta-data be generated / loaded from cache?.
     * @param boolean            $wasLoadedFromCache Should the cache have been successfully loaded from the second
     *                                               time?.
     * @param boolean            $willRebuildCache   Will the cache be rebuilt afterwards?.
     * @return void
     */
    #[Test]
    #[DataProvider("cacheFileRemovalDataProvider")]
    public static function test_that_cache_fixed_when_missing_files(
        array $removePathMethods,
        bool $needMeta,
        bool $wasLoadedFromCache,
        bool $willRebuildCache
    ): void {

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1');
        static::runServiceProvider($detect);

        static::assertFalse($detect->wasLoadedFromCache());
        static::assertFalse(file_exists($detect->getMainCachePath()));
        static::assertFalse(file_exists($detect->getMetaCachePath()));

        $detect->loadFresh(true);
        $detect->saveCache();

        static::assertFileExists($detect->getMainCachePath());
        static::assertFileExists($detect->getMetaCachePath());

        foreach ($removePathMethods as $removePathMethod) {
            static::assertTrue(file_exists(static::$removePathMethod()));
            static::removeFile(static::$removePathMethod());
            static::assertFalse(file_exists(static::$removePathMethod()));
        }



        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', [], $needMeta, false);
        static::runServiceProvider($detect);

        static::assertSame($wasLoadedFromCache, $detect->wasLoadedFromCache());

        foreach ($removePathMethods as $removePathMethod) {
            static::assertSame($willRebuildCache, file_exists(static::$removePathMethod()));
        }
    }



    /**
     * Perform a string-replace on the content of a file.
     *
     * @param string $path    The path to the file.
     * @param string $search  The content to search for.
     * @param string $replace The content to replace it with.
     * @return void
     * @throws FileNotFoundException Thrown when a file cannot be read from.
     */
    private static function stringReplaceFile(string $path, string $search, string $replace): void
    {
        $filesystem = new Filesystem();
        $content = str_replace($search, $replace, $filesystem->get($path, true));
        $filesystem->put($path, $content, true);
    }

    /**
     * Check that the file contains some content.
     *
     * @param string $path   The path to the file.
     * @param string $search The content to search for.
     * @return boolean
     * @throws FileNotFoundException Thrown when a file cannot be read from.
     */
    private static function fileHasContent(string $path, string $search): bool
    {
        return mb_strpos((new Filesystem())->get($path, true), $search) !== false;
    }

    /**
     * Remove a file.
     *
     * @param string|array<int, string> $path The path/s to remove.
     * @return void
     */
    private static function removeFile($path): void
    {
        (new Filesystem())->delete($path);
    }
}
