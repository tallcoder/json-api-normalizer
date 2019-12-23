<?php
namespace TallCoder\JsonApiNormalizer\Laravel;

use Illuminate\Support\ServiceProvider;

/**
 * Class JsonApiNormalizerProvider
 *
 * @package TallCoder\JsonApiNormalizer\Laravel
 */
class JsonApiNormalizerProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        require_once(__DIR__ . '/helpers.php');
    }
}
