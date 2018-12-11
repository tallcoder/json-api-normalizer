<?php

namespace JacobFennik\JsonApiNormalizer\Tests;

use JacobFennik\JsonApiNormalizer\Normalizer;
use PHPUnit\Framework\TestCase;

/**
 * Class NormalizeTest
 *
 * @package JacobFennik\JsonApiNormalizer\Tests
 */
class NormalizeTest extends TestCase
{
    /**
     * @var string;
     */
    protected $responseData;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->responseData = file_get_contents(__DIR__ . '/jsonapi.json');
    }

    /**
     * testProcessResponse.
     */
    public function testProcessResponse()
    {
        $normalized = new Normalizer($this->responseData);
        $built = $normalized->build(1);

        print_r($built->author);

        if ($normalized) {
            return true;
        }

        return false;
    }
}
