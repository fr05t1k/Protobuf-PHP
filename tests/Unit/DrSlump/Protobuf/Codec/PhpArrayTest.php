<?php

namespace Tests\Unit\DrSlumpProtobuf\Codec;

use PHPUnit_Framework_TestCase;
use DrSlump\Protobuf\Codec\PhpArray;
use tests\Repeated;

/**
 * Class PhpArrayTest
 */
class PhpArrayTest extends PHPUnit_Framework_TestCase
{

    public function testEncodeRepeatedEmpty()
    {
        $codec = new PhpArray();

        $repeated = new Repeated();

        $data = $codec->encode($repeated);
        self::assertEmpty($data);
    }

    public function testDecodeRepeatedEmpty()
    {
        $codec = new PhpArray();
        $data = [
            'string' => [],
            'int' => [],
            'nested' => []
        ];

        $repeated = new Repeated();
        /** @var Repeated $repeated */
        $repeated = $codec->decode($repeated, $data);

        self::assertEmpty($repeated->getString());
        self::assertEmpty($repeated->getInt());
        self::assertEmpty($repeated->getNested());
    }
}
