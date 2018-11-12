<?php

namespace Recca0120\Olami\Tests;

use Recca0120\Olami\Hasher;
use PHPUnit\Framework\TestCase;

class HasherTest extends TestCase
{
    /** @test */
    public function test_hash()
    {
        $hasher = new Hasher('987654abcdef987654abcdef98765432');

        $this->assertSame('a6488df95515d87fe2ad4f9d2a5306fb', $hasher->make([
            'api' => 'asr',
            'appkey' => '012345abcdef012345abcdef01234567',
            'timestamp' => 1492099200000,
        ]));
    }
}
