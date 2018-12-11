<?php

namespace JacobFennik\JsonApiNormalizer;

use Illuminate\Support\Collection;

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
     */
    public function process($inputData)
    {
        if (is_string($inputData)) {
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
     */
    public function build($dataId = null)
    {
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
                    $object = $this->includedObjects
                        ->where('type', $data['type'])
                        ->where('id', $data['id'])
                        ->first();
                    if (!empty($object)) {
                        $relationObject = $this->buildObject($object);
                    }
                } else {
                    foreach ($data as $multiData) {
                        $object = $this->includedObjects
                            ->where('type', $multiData['type'])
                            ->where('id', $multiData['id'])
                            ->first();
                        if (!empty($object)) {
                            $relationObject[] = $this->buildObject($object);
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
}
