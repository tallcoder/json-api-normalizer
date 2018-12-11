<?php

namespace JacobFennik\JsonApiNormalizer\Tests;

use JacobFennik\JsonApiNormalizer\Exceptions\InvalidJsonStringException;
use JacobFennik\JsonApiNormalizer\Exceptions\NoInputDataException;
use JacobFennik\JsonApiNormalizer\Normalizer;
use PHPUnit\Framework\TestCase;

/**
 * Class NormalizerExceptionTest
 *
 * @package JacobFennik\JsonApiNormalizer\Tests
 */
class NormalizerExceptionTest extends TestCase
{
    /**
     * @var Normalizer;
     */
    protected $normalizer;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->normalizer = new Normalizer();
    }

    /**
     * testInvalidJsonStringException.
     */
    public function testInvalidJsonStringException()
    {
        $this->expectException(InvalidJsonStringException::class);

        $this->normalizer->process('}{');
    }

    /**
     * testNoInputDataException.
     */
    public function testNoInputDataException()
    {
        $this->expectException(NoInputDataException::class);

        $this->normalizer->build();
    }
}
