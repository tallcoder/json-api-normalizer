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
    protected $original;

    /**
     * @var Collection
     */
    protected $data;

    /**
     * @var Collection
     */
    protected $included;

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

        $this->storeOriginal($inputData);
        $this->storeData($this->original->get('data'));

        $this->storeIncluded($this->original->get('included'));
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
            $object = $this->data->where('id', $dataId)->first();
            $result = $this->buildObject($object);
        } else {
            $this->data->each(function ($object) use ($result) {
                $result->put($object['id'], $this->buildObject($object));
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

    /**
     * Store original response.
     *
     * @param array $responseArray
     */
    private function storeOriginal(array $responseArray)
    {
        $this->original = new Collection($responseArray);
    }

    /**
     * Store response data.
     *
     * @param array $data
     */
    private function storeData(array $data)
    {
        $this->data = new Collection($data);
    }

    /**
     * Store response includes.
     *
     * @param array $included
     */
    private function storeIncluded(array $included)
    {
        $this->included = new Collection($included);
    }

    /**
     * Compile object with relationships.
     *
     * @param $object
     *
     * @return object
     */
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

    /**
     * Get relationships for object and build recursively.
     *
     * @param $object
     *
     * @return array
     */
    private function getRelationships($object)
    {
        $relations = [];

        if (isset($object->relationships)) {
            foreach ($object->relationships as $key => $relation) {
                $relationObject = $this->buildRelationship($relation);

                if ($relationObject) {
                    $relations[$key] = $relationObject;
                }
            }
        }

        return $relations;
    }

    /**
     * Build relationship and build its objects.
     *
     * @param array $relation
     *
     * @return Collection|object|null
     */
    private function buildRelationship(array $relation)
    {
        $relationObject = null;
        $relationData = $relation['data'];

        if (array_key_exists('type', $relationData)) {
            $object = $this->findInclude($relationData['type'], $relationData['id']);
            if (!empty($object)) {
                $relationObject = $this->buildObject($object);
            }
        } else {
            $relationObject = new Collection();

            foreach ($relationData as $multiData) {
                $object = $this->findInclude($multiData['type'], $multiData['id']);
                if (!empty($object)) {
                    $relationObject->put($object['id'], $this->buildObject($object));
                }
            }
        }

        return $relationObject;
    }

    /**
     * Find object by type and id.
     *
     * @param $type
     * @param $id
     *
     * @return mixed
     */
    private function findInclude($type, $id)
    {
        return $this->included
            ->where('type', $type)
            ->where('id', $id)
            ->first();
    }

    /**
     * Test if string is valid json format.
     *
     * @param $string
     *
     * @return bool
     */
    private function isValidJson($string)
    {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
