# Mime Types

[![Latest Stable Version](http://poser.pugx.org/borschphp/mimetype/v)](https://packagist.org/packages/borschphp/mimetype)
[![License](http://poser.pugx.org/borschphp/mimetype/license)](https://packagist.org/packages/borschphp/mimetype)
[![PHP Version Require](http://poser.pugx.org/borschphp/mimetype/require/php)](https://packagist.org/packages/borschphp/mimetype)
[![build](https://github.com/borschphp/borsch-mimetype/actions/workflows/php.yml/badge.svg)](https://github.com/borschphp/borsch-mimetype/actions/workflows/php.yml)
[![Code Coverage](https://scrutinizer-ci.com/g/borschphp/borsch-mimetype/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/borschphp/borsch-mimetype/?branch=main)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/borschphp/borsch-mimetype/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/borschphp/borsch-mimetype/?branch=main)

MimeType and MediaType implementation.

## Install

This project requires PHP 7.2 or higher.  
Via [Composer](https://getcomposer.org/), simply run:

```bash
composer require borschphp/mimetype
```

## Basic Usage

Easily create Mime Type for your requests:

```php
use Borsch\MimeType\MimeType;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;

$mime_type = new MimeType('application', 'json', ['charset' => 'UTF-8']);

$request = (new Request())
    ->withUri(new Uri('http://example.com'))
    ->withMethod('GET')
    ->withAddedHeader('Content-Type', (string)$mime_type);
```

Or Media Type:

```php
use Borsch\MimeType\MediaType;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;

$request = (new Request())
    ->withUri(new Uri('http://example.com'))
    ->withMethod('GET')
    ->withAddedHeader('Content-Type', MediaType::APPLICATION_JSON);
```

Parse Mime Types and get useful data:

```php
use Borsch\MimeType\MimeType;
use Borsch\MimeType\MediaType;

$mime_type = MimeType::createFromString(
    'application/atom+xml;charset=utf-8;boundary=3d6b6a416f9b5;name=some_file'
);

$mime_type->getType(); // application
$mime_type->getSubtype(); // atom+xml
$mime_type->getSubtypeSuffix() // xml
$mime_type->getCharset(); // utf-8
$mime_type->getParameters(); // ['charset' => 'utf-8', 'boundary' => '3d6b6a416f9b5', 'name' => 'some_file']
$mime_type->getParameter('boundary'); // 3d6b6a416f9b5

$media_type = new MediaType('image', 'png', ['q' => 0.8]);
$media_type->getQualityValue(); // 0.8
$media_type->removeQualityValue();
$media_type->getQualityValue(); // null
```

## Contributing

Please see [CONTRIBUTING.md](https://github.com/borschphp/borsch-mimetype/blob/main/CONTRIBUTING.md) for details.

## Testing

Made with [PHPUnit](https://phpunit.de/), simply run:

```bash
./vendor/bin/phpunit tests
```

## License

This project is licensed under the MIT license.  
See the [LICENSE.md](https://github.com/borschphp/borsch-mimetype/blob/main/LICENSE.md) file for more details.