<?php

namespace TallCoder\JsonApiNormalizer;

use Illuminate\Support\Collection;
use TallCoder\JsonApiNormalizer\Exceptions\InvalidJsonStringException;
use TallCoder\JsonApiNormalizer\Exceptions\NoInputDataException;

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
    public $data;

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

        if ($this->original->get('included')) {
            $this->storeIncluded($this->original->get('included'));
        }
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
            $result->put($this->data->get('id'), $this->buildObject($this->data));
//            $this->data->each(function ($object, $key) use ($result) {
//                if (!isset($object['id'])) {
//                    stilcroInfo($key);
//                    stilcroInfo($object);
//                    stilcroInfo(print_r($this->data)); die;
//                }
//                $result->put($object['id'], $this->buildObject($object));
//            });
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
        $object = json_decode(json_encode($object));

        $relations = $this->getRelationships($object);

        foreach ($relations as $key => $relation) {
            if ($key === 'attributes') {
                $object->product_attributes = $relation;
            } else {
                $object->{$key} = $relation;
            }
        }
        unset($object->relationships);

        $attributes = $object->attributes;

        foreach ($attributes as $key => $value) {
            $object->{$key} = $value;
        }
        // unset($object->attributes);

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
            $relationships = (object)$object->relationships;
        }

        if (isset($relationships)) {
            foreach ($relationships as $key => $relation) {
                // stilcroInfo(json_decode(json_encode($relation), true), true, __LINE__);
                $relationObject = $this->buildRelationship(json_decode(json_encode($relation), true));

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

        $typeFound = false;
        foreach ($relationData as $relData) {
            if (isset($relData['type'])) {
                $typeFound = true;
            }
        }

        if ($typeFound) {
            $relationObject = [];
            foreach ($relationData as $relData) {
                $object = $this->findInclude($relData['type'], $relData['id']);
                if (!empty($object)) {
                    $relationObject[] = $this->buildObject($object);
                }
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

        // stilcroInfo($relationObject, true, __LINE__);

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
        $found = $this->included
            ->where('type', $type)
            ->where('id', $id)
            ->first();

        return $found;
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
