# PSR-7 HTTP Message

Strict, minimal PSR-7 implementation.

## Features

- **Native URI Handling:** Uses PHP's `ext-uri` for fast URI parsing and manipulation.
- **RFC-focused validation:** Strict RFC-compliant encoding and validation of HTTP messages, URIs, and streams.
- **Secure File Uploads:** Mitigates path traversal and control character injection while validating nested upload structures.

## Requirements

PHP `8.5` or later

## Installation

Install the package using Composer:

`composer require sabroan/psr-7`

## Usage
This package provides PSR-7 HTTP message implementations, including requests, responses, streams, URIs, and uploaded files.

For the interface specifications, see the [PSR-7 documentation](https://www.php-fig.org/psr/psr-7/).

## Classes
- `Sabroan\Http\Message\Request`
- `Sabroan\Http\Message\Response`
- `Sabroan\Http\Message\ServerRequest`
- `Sabroan\Http\Message\Stream`
- `Sabroan\Http\Message\UploadedFile`
- `Sabroan\Http\Message\Uri`

## License

This package is released under the MIT License. See the LICENSE file for details.
