<?php

if (!function_exists('normalize')) {
    /**
     * @param $inputData
     * @param bool $includeType
     *
     * @return \TallCoder\JsonApiNormalizer\Normalizer
     * @throws \TallCoder\JsonApiNormalizer\Exceptions\InvalidJsonStringException
     */
    function normalize($inputData, $includeType = false) {
        return new \TallCoder\JsonApiNormalizer\Normalizer($inputData, $includeType);
    }
}
