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
     * @var Normalizer;
     */
    protected $normalizer;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $responseData = file_get_contents(__DIR__ . '/examples/single.json');
        $this->normalizer = new Normalizer($responseData);
    }

    /**
     * testNormalizerInstance.
     */
    public function testNormalizerInstance()
    {
        $this->assertInstanceOf('JacobFennik\JsonApiNormalizer\Normalizer', $this->normalizer);
    }

    /**
     * testBuild.
     */
    public function testBuild()
    {
        $built = $this->normalizer->build();

        $this->assertInstanceOf('Illuminate\Support\Collection', $built);
    }

    /**
     * testRelationCollection.
     */
    public function testRelationCollection()
    {
        $built = $this->normalizer->build(1);

        $this->assertInstanceOf('Illuminate\Support\Collection', $built->comments);
    }

    /**
     * testMainObject.
     */
    public function testMainObject()
    {
        $built = $this->normalizer->build(1);

        $this->assertSame('JSON:API paints my bikeshed!', $built->title);
    }

    /**
     * testSingleRelationObject.
     */
    public function testSingleRelationObject()
    {
        $built = $this->normalizer->build(1);

        $this->assertSame('Dan', $built->author->firstName);
    }

    /**
     * testCollectionRelationObject
     */
    public function testCollectionRelationObject()
    {
        $built = $this->normalizer->build(1);

        $this->assertSame('First!', $built->comments->first()->body);
    }

    /**
     * testObjectTypeInclusion
     */
    public function testObjectTypeInclusion()
    {
        $this->normalizer->includeType(true);
        $built = $this->normalizer->build(1);

        $this->assertTrue(property_exists($built, 'type'));
    }

    /**
     * testObjectTypeExclusion
     */
    public function testObjectTypeExclusion()
    {
        $this->normalizer->includeType(false);
        $built = $this->normalizer->build(1);

        $this->assertFalse(property_exists($built, 'type'));
    }
}
