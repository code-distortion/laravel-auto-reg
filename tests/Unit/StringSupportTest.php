<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Unit;

use CodeDistortion\LaravelAutoReg\Support\StringSupport;
use CodeDistortion\LaravelAutoReg\Tests\PHPUnitTestCase;

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
     * @return void
     */
    public function test_case_conversion(): void
    {
        $this->assertSame('abcDef.ghiJlk', StringSupport::changeCase('abcDef.ghiJlk', 'camel'));
        $this->assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abcDef.ghiJlk', 'pascal'));
        $this->assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abcDef.ghiJlk', 'kebab'));
        $this->assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abcDef.ghiJlk', 'snake'));

        $this->assertSame('abcDef.ghiJlk', StringSupport::changeCase('AbcDef.GhiJlk', 'camel'));
        $this->assertSame('AbcDef.GhiJlk', StringSupport::changeCase('AbcDef.GhiJlk', 'pascal'));
        $this->assertSame('abc-def.ghi-jlk', StringSupport::changeCase('AbcDef.GhiJlk', 'kebab'));
        $this->assertSame('abc_def.ghi_jlk', StringSupport::changeCase('AbcDef.GhiJlk', 'snake'));

        $this->assertSame('abcDef.ghiJlk', StringSupport::changeCase('abc-def.ghi-jlk', 'camel'));
        $this->assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abc-def.ghi-jlk', 'pascal'));
        $this->assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abc-def.ghi-jlk', 'kebab'));
        $this->assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abc-def.ghi-jlk', 'snake'));

        $this->assertSame('abcDef.ghiJlk', StringSupport::changeCase('abc_def.ghi_jlk', 'camel'));
        $this->assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abc_def.ghi_jlk', 'pascal'));
        $this->assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abc_def.ghi_jlk', 'kebab'));
        $this->assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abc_def.ghi_jlk', 'snake'));

        $this->assertSame('abcDef.ghiJlk', StringSupport::changeCase('abc def.ghi jlk', 'camel'));
        $this->assertSame('AbcDef.GhiJlk', StringSupport::changeCase('abc def.ghi jlk', 'pascal'));
        $this->assertSame('abc-def.ghi-jlk', StringSupport::changeCase('abc def.ghi jlk', 'kebab'));
        $this->assertSame('abc_def.ghi_jlk', StringSupport::changeCase('abc def.ghi jlk', 'snake'));
    }
}
