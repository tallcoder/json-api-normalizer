<?php

if (!function_exists('normalize')) {
    /**
     * @param $inputData
     * @param bool $includeType
     *
     * @return \JacobFennik\JsonApiNormalizer\Normalizer
     * @throws \JacobFennik\JsonApiNormalizer\Exceptions\InvalidJsonStringException
     */
    function normalize($inputData, $includeType = false) {
        return new \JacobFennik\JsonApiNormalizer\Normalizer($inputData, $includeType);
    }
}
