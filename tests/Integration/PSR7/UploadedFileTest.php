<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests\Integration\PSR7;

use PHPUnit\Framework\Attributes\CoversClass;
use Sabroan\Http\Message\UploadedFile;

#[CoversClass(UploadedFile::class)]
final class UploadedFileTest extends \Http\Psr7Test\UploadedFileIntegrationTest
{
    use BaseTrait;

    public function createSubject(): \Psr\Http\Message\UploadedFileInterface
    {
        return $this->buildUploadableFile('');
    }
}
