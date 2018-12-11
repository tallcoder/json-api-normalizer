<?php

namespace JacobFennik\JsonApiNormalizer;

use Illuminate\Support\Collection;
use JacobFennik\JsonApiNormalizer\Exceptions\InvalidJsonStringException;
use JacobFennik\JsonApiNormalizer\Exceptions\NoInputDataException;

/**
 * Class Normalizer
 *
 * @package JacobFennik\JsonApiNormalizer
 */
class Normalizer {

    /**
     * @var Collection
     */
    protected $dataObjects;

    /**
     * @var Collection
     */
    protected $includedObjects;

    /**
     * @var Collection
     */
    protected $original;

    /**
     * @var bool
     */
    protected $includeType;

    /**
     * Normalizer constructor.
     *
     * @param null $inputData
     * @param bool $includeType
     *
     * @throws InvalidJsonStringException
     */
    public function __construct($inputData = null, $includeType = false)
    {
        $this->includeType = $includeType;

        if (!empty($inputData)) {
            $this->process($inputData);
        }
    }

    /**
     * Process input data.
     *
     * @param $inputData
     *
     * @throws InvalidJsonStringException
     */
    public function process($inputData)
    {
        if (is_string($inputData)) {
            if (!$this->isValidJson($inputData)) {
                throw new InvalidJsonStringException('Input string is not valid JSON');
            }

            $inputData = json_decode($inputData, true);
        }

        $this->recordOriginal($inputData);
        $this->processData();
        $this->processIncluded();
    }

    /**
     * Build json response
     *
     * @param null $dataId
     *
     * @return array|object
     * @throws NoInputDataException
     */
    public function build($dataId = null)
    {
        if (!$this->original instanceof Collection) {
            throw new NoInputDataException('No input data supplied to normalizer');
        }

        $result = new Collection();

        if ($dataId) {
            $object = $this->dataObjects->where('id', $dataId)->first();
            $result = $this->buildObject($object);
        } else {
            $this->dataObjects->each(function ($object) use ($result) {
                $result->put($object->id, $this->buildObject($object));
            });
        }

        return $result;
    }

    /**
     * Set include type option.
     *
     * @param $includeType
     */
    public function includeType($includeType)
    {
        $this->includeType = $includeType;
    }

    private function processData()
    {
        $this->dataObjects = new Collection($this->original->get('data'));
    }

    private function processIncluded()
    {
        $this->includedObjects = new Collection($this->original->get('included'));
    }

    private function recordOriginal($inputData)
    {
        $this->original = new Collection($inputData);
    }

    private function buildObject($object)
    {
        if (is_array($object)) {
            $object = (object) $object;
        }

        $relations = $this->getRelationships($object);

        foreach ($relations as $key => $relation) {
            $object->{$key} = $relation;
        }
        unset($object->relationships);

        $attributes = $object->attributes;
        foreach ($attributes as $key => $value) {
            $object->{$key} = $value;
        }
        unset($object->attributes);

        if (!$this->includeType) {
            unset($object->type);
        }

        return $object;
    }

    private function getRelationships($object)
    {
        $relations = [];

        if ($object->relationships) {
            foreach ($object->relationships as $key => $relation) {
                $relationObject = null;
                $data = $relation['data'];

                if (array_key_exists('type', $data)) {
                    $object = $this->findInclude($data['type'], $data['id']);
                    if (!empty($object)) {
                        $relationObject = $this->buildObject($object);
                    }
                } else {
                    $relationObject = new Collection();
                    foreach ($data as $multiData) {
                        $object = $this->findInclude($multiData['type'], $multiData['id']);
                        if (!empty($object)) {
                            $relationObject->put($object->id, $this->buildObject($object));
                        }
                    }
                }

                if ($relationObject) {
                    $relations[$key] = $relationObject;
                }
            }
        }

        return $relations;
    }

    private function findInclude($type, $id)
    {
        return $this->includedObjects
            ->where('type', $type)
            ->where('id', $id)
            ->first();
    }

    private function isValidJson($string)
    {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
