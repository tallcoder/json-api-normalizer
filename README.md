# json-api-normalizer
Utility to normalize and build JSON:API response data.

## Install
Add the following to your composer.json file (currently need this because it's not on an official site):
```
repositories: [
    {
        "type": "vcs",
        "url": "git@github.com:tallcoder/json-api-normalizer.git"
    }
],
```

```bash
$ composer require tallcoder/json-api-normalizer
```

## Example

Building with the normalizer
```php
<?php

use JacobFennik\JsonApiNormalizer\Normalizer;

$apiResponse = '
{
    "data": [{
        "type": "articles",
        "id": "1",
        "attributes": {
            "title": "JSON:API paints my bikeshed!"
        },
        "relationships": {
            "author": {
                "data": { "type": "people", "id": "9" }
            },
        }
    }],
    "included": [{
        "type": "people",
        "id": "9",
        "attributes": {
            "firstName": "Dan",
            "lastName": "Gebhardt",
            "twitter": "dgeb"
        },
    }]
}
';

$normalizer = new Normalizer($apiResponse);
$built = $normalizer->build();
```

Accessing built data
```php
<?php
$normalizer = new Normalizer($apiResponse);

$article = $normalizer->build(1); // Build object with id '1' 

$title = $article->title;
$authorFirstName = $article->author->firstName;
```
