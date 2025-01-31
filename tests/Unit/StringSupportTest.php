<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Unit;

use CodeDistortion\LaravelAutoReg\Support\StringSupport;
use CodeDistortion\LaravelAutoReg\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the StringSupport class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class StringSupportTest extends PHPUnitTestCase
{
    /**
     * Test the case of strings are converted properly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_case_conversion(): void
    {
        static::assertSame('abcDef.ghiJlk', StringSupport::changeCase('abcDef.ghiJlk', 'camel'));
        static::assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abcDef.ghiJlk', 'pascal'));
        static::assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abcDef.ghiJlk', 'kebab'));
        static::assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abcDef.ghiJlk', 'snake'));

        static::assertSame('abcDef.ghiJlk', StringSupport::changeCase('AbcDef.GhiJlk', 'camel'));
        static::assertSame('AbcDef.GhiJlk', StringSupport::changeCase('AbcDef.GhiJlk', 'pascal'));
        static::assertSame('abc-def.ghi-jlk', StringSupport::changeCase('AbcDef.GhiJlk', 'kebab'));
        static::assertSame('abc_def.ghi_jlk', StringSupport::changeCase('AbcDef.GhiJlk', 'snake'));

        static::assertSame('abcDef.ghiJlk', StringSupport::changeCase('abc-def.ghi-jlk', 'camel'));
        static::assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abc-def.ghi-jlk', 'pascal'));
        static::assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abc-def.ghi-jlk', 'kebab'));
        static::assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abc-def.ghi-jlk', 'snake'));

        static::assertSame('abcDef.ghiJlk', StringSupport::changeCase('abc_def.ghi_jlk', 'camel'));
        static::assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abc_def.ghi_jlk', 'pascal'));
        static::assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abc_def.ghi_jlk', 'kebab'));
        static::assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abc_def.ghi_jlk', 'snake'));

        static::assertSame('abcDef.ghiJlk', StringSupport::changeCase('abc def.ghi jlk', 'camel'));
        static::assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abc def.ghi jlk', 'pascal'));
        static::assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abc def.ghi jlk', 'kebab'));
        static::assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abc def.ghi jlk', 'snake'));
    }
}
