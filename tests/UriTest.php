<?php

declare(strict_types=1);

namespace Sabroan\Http\Message\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Sabroan\Http\Message\Uri;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    private function createUri(string $uri = ''): UriInterface
    {
        $parts = \parse_url($uri);
        $parts = \is_array($parts) ? $parts : [];

        return (new Uri())
            ->withScheme($parts['scheme'] ?? '')
            ->withHost($parts['host'] ?? '')
            ->withUserInfo(
                $parts['user'] ?? '',
                $parts['pass'] ?? null,
            )
            ->withPort($parts['port'] ?? null)
            ->withPath($parts['path'] ?? '')
            ->withQuery($parts['query'] ?? '')
            ->withFragment($parts['fragment'] ?? '');
    }

    #[DataProvider('schemeProvider')]
    public function testGetSchemeReturnsNormalizedScheme(string $inputUri, string $expectedScheme): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedScheme, $uri->getScheme());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function schemeProvider(): array
    {
        return [
            'http scheme' => ['http://example.com', 'http'],
            'https scheme' => ['https://example.com', 'https'],
            'ftp scheme' => ['ftp://files.example.com', 'ftp'],
            'urn scheme' => ['urn:isbn:0451450523', 'urn'],
            'uppercase http' => ['HTTP://EXAMPLE.COM', 'http'],
            'mixed case https' => ['HtTpS://example.com', 'https'],
            'uppercase custom scheme' => ['VIEW-SOURCE:http://example.com', 'view-source'],
            'scheme with digits' => ['a123://example.com', 'a123'],
            'scheme with plus' => ['git+ssh://git.example.com', 'git+ssh'],
            'scheme with hyphen' => ['custom-scheme://example.com', 'custom-scheme'],
            'scheme with dot' => ['custom.scheme://example.com', 'custom.scheme'],
            'protocol-relative URI' => ['//example.com/path', ''],
            'absolute path' => ['/path/to/resource', ''],
            'relative path' => ['path/to/resource', ''],
            'query parameters only' => ['?foo=bar', ''],
            'fragment only' => ['#section-1', ''],
            'empty URI' => ['', ''],
        ];
    }

    #[DataProvider('authorityProvider')]
    public function testGetAuthorityReturnsFormattedAuthority(string $inputUri, string $expectedAuthority): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedAuthority, $uri->getAuthority());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorityProvider(): array
    {
        return [
            'relative path' => ['path/to/resource', ''],
            'absolute path' => ['/path/to/resource', ''],
            'query parameters only' => ['?foo=bar', ''],
            'fragment only' => ['#section', ''],
            'scheme only' => ['http:', ''],
            'host only' => ['http://example.com', 'example.com'],
            'uppercase host normalized' => ['http://EXAMPLE.COM', 'example.com'],
            'protocol relative' => ['//example.com', 'example.com'],
            'ipv4 address' => ['http://127.0.0.1', '127.0.0.1'],
            'ipv6 address' => ['http://[::1]', '[::1]'],
            'user only' => ['http://user@example.com', 'user@example.com'],
            'user and password' => ['http://user:pass@example.com', 'user:pass@example.com'],
            'http standard port 80' => ['http://example.com:80', 'example.com'],
            'https standard port 443' => ['https://example.com:443', 'example.com'],
            'user-info + standard port' => ['http://user:pass@example.com:80', 'user:pass@example.com'],
            'http non-standard port' => ['http://example.com:8080', 'example.com:8080'],
            'http with https port' => ['http://example.com:443', 'example.com:443'],
            'https with http port' => ['https://example.com:80', 'example.com:80'],
            'unknown scheme with port' => ['custom://example.com:80', 'example.com:80'],
            'no scheme with port' => ['//example.com:80', 'example.com:80'],
            'user, pass, host, and custom port' => ['http://admin:secret@example.com:9000', 'admin:secret@example.com:9000'],
        ];
    }

    #[DataProvider('userInfoProvider')]
    public function testGetUserInfoReturnsFormattedUserInfo(string $inputUri, string $expectedUserInfo): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedUserInfo, $uri->getUserInfo());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function userInfoProvider(): array
    {
        return [
            'no user info' => ['http://example.com', ''],
            'host with port only' => ['http://example.com:8080', ''],
            'relative path' => ['/path/to/resource', ''],
            'empty URI' => ['', ''],
            'username only' => ['http://username@example.com', 'username'],
            'username with path' => ['http://john@example.com/path', 'john'],
            'case sensitive username' => ['http://UserNAME@example.com', 'UserNAME'],
            'username and password' => ['http://username:password@example.com', 'username:password'],
            'username with empty password' => ['http://username:@example.com', 'username'],
            'percent encoded credentials' => ['http://user%20name:p%40ssword@example.com', 'user%20name:p%40ssword'],
            'special unreserved characters' => ['http://user.name-123:pass_word~456@example.com', 'user.name-123:pass_word~456'],
        ];
    }

    #[DataProvider('hostProvider')]
    public function testGetHostReturnsNormalizedHost(string $inputUri, string $expectedHost): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedHost, $uri->getHost());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function hostProvider(): array
    {
        return [
            'relative path' => ['path/to/resource', ''],
            'absolute path' => ['/path/to/resource', ''],
            'query parameters only' => ['?foo=bar', ''],
            'fragment only' => ['#section', ''],
            'scheme only without authority' => ['mailto:user@example.com', ''],
            'empty URI' => ['', ''],
            'standard host' => ['http://example.com', 'example.com'],
            'mixed case host normalized' => ['http://ExAmPlE.CoM', 'example.com'],
            'fully qualified domain name' => ['http://example.com.', 'example.com.'],
            'protocol relative' => ['//example.com/path', 'example.com'],
            'host with port' => ['http://example.com:8080', 'example.com'],
            'host with userinfo' => ['http://user:pass@example.com', 'example.com'],
            'host with userinfo and port' => ['http://user:pass@EXAMPLE.COM:8080/path', 'example.com'],
            'ipv4 address' => ['http://192.168.1.1', '192.168.1.1'],
            'ipv6 address with brackets' => ['http://[2001:db8:1f70::999:de8:7648:6e8]', '[2001:db8:1f70::999:de8:7648:6e8]'],
            'ipv6 address with port' => ['http://[::1]:8080', '[::1]'],
        ];
    }

    #[DataProvider('portProvider')]
    public function testGetPortReturnsPortOrNull(string $inputUri, ?int $expectedPort): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedPort, $uri->getPort());
    }

    /**
     * @return array<string, array{string, ?int}>
     */
    public static function portProvider(): array
    {
        return [
            'no port, http scheme' => ['http://example.com', null],
            'no port, https scheme' => ['https://example.com', null],
            'no port, no scheme' => ['//example.com', null],
            'relative path' => ['/path/to/resource', null],
            'empty URI' => ['', null],
            'http standard port 80' => ['http://example.com:80', null],
            'https standard port 443' => ['https://example.com:443', null],
            'http non-standard port' => ['http://example.com:8080', 8080],
            'https non-standard port' => ['https://example.com:8443', 8443],
            'http with https port' => ['http://example.com:443', 443],
            'https with http port' => ['https://example.com:80', 80],
            'no scheme port 80' => ['//example.com:80', 80],
            'no scheme port 443' => ['//example.com:443', 443],
            'no scheme custom port' => ['//example.com:8080', 8080],
            'custom scheme with port' => ['git+ssh://git.example.com:22', 22],
            'custom scheme port 80' => ['custom://example.com:80', 80],
        ];
    }

    public function testPortNormalizationUpdatesWhenSchemeChanges(): void
    {
        $uri = $this->createUri('http://example.com:80');
        self::assertNull($uri->getPort());

        $httpsUri = $uri->withScheme('https');
        self::assertSame(80, $httpsUri->getPort());
        self::assertSame('example.com:80', $httpsUri->getAuthority());

        $httpUri = $httpsUri->withScheme('http');
        self::assertNull($httpUri->getPort());
        self::assertSame('example.com', $httpUri->getAuthority());
    }

    #[DataProvider('pathProvider')]
    public function testGetPathReturnsFormattedPath(string $inputUri, string $expectedPath): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedPath, $uri->getPath());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pathProvider(): array
    {
        return [
            'empty path with host' => ['http://example.com', ''],
            'root slash path with host' => ['http://example.com/', '/'],
            'absolute path' => ['http://example.com/path/to/resource', '/path/to/resource'],
            'rootless path without host' => ['path/to/resource', 'path/to/resource'],
            'rootless urn path' => ['urn:isbn:0451450523', 'isbn:0451450523'],
            'empty URI' => ['', ''],
            'no path retention' => ['//example.com', ''],
            'explicit slash retention' => ['//example.com/', '/'],
            'encoded slash remains encoded' => ['http://example.com/foo%2Fbar', '/foo%2Fbar'],
            'prevent double encoding' => ['http://example.com/foo%20bar', '/foo%20bar'],
            'encode raw unreserved spaces' => ['http://example.com/foo bar', '/foo%20bar'],
            'allowed path characters' => ['http://example.com/v1.0/user:123@domain/foo-bar_baz~test', '/v1.0/user:123@domain/foo-bar_baz~test'],
            'sub-delimiters in path' => ['http://example.com/a$b!c&d\'e(f)*g+h,i;j=k', '/a$b!c&d\'e(f)*g+h,i;j=k'],
        ];
    }

    #[DataProvider('slashReductionProvider')]
    public function testLeadingSlashReductionWithoutAuthority(string $inputPath, string $expectedPath): void
    {
        $uri = (new Uri())->withPath($inputPath);

        self::assertSame($expectedPath, $uri->getPath());
        self::assertSame($expectedPath, (string) $uri);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function slashReductionProvider(): array
    {
        return [
            'double slash path reduced' => ['//double/slash/path', '/double/slash/path'],
            'triple slash path reduced' => ['///triple/slash/path', '/triple/slash/path'],
        ];
    }

    #[DataProvider('queryProvider')]
    public function testGetQueryReturnsFormattedQuery(string $inputUri, string $expectedQuery): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedQuery, $uri->getQuery());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function queryProvider(): array
    {
        return [
            'no query present' => ['http://example.com/path', ''],
            'question mark without query' => ['http://example.com/path?', ''],
            'fragment without query' => ['http://example.com/path#fragment', ''],
            'empty URI' => ['', ''],
            'simple key-value pair' => ['http://example.com?foo=bar', 'foo=bar'],
            'multiple key-value pairs' => ['http://example.com?foo=bar&baz=qux', 'foo=bar&baz=qux'],
            'valueless query parameters' => ['http://example.com?flag&foo=bar', 'flag&foo=bar'],
            'empty value parameter' => ['http://example.com?foo=&bar=', 'foo=&bar='],
            'already percent-encoded spaces' => ['http://example.com?query=foo%20bar', 'query=foo%20bar'],
            'raw space auto percent-encoded' => ['http://example.com?query=foo bar', 'query=foo%20bar'],
            'prevent double encoding' => ['http://example.com?query=foo%2520bar', 'query=foo%2520bar'],
            'lowercase percent hex normalized to uppercase' => ['http://example.com?query=foo%2fbar', 'query=foo%2Fbar'],
            'encoded ampersand in value' => ['http://example.com?foo=a%26b', 'foo=a%26b'],
            'encoded equals sign in key' => ['http://example.com?a%3Db=1', 'a%3Db=1'],
            'allowed unencoded query characters' => ['http://example.com?q=/path?foo:bar@baz', 'q=/path?foo:bar@baz'],
            'sub-delimiters in query' => ['http://example.com?a=!$&\'()*+,;=', 'a=!$&\'()*+,;='],
        ];
    }

    #[DataProvider('fragmentProvider')]
    public function testGetFragmentReturnsFormattedFragment(string $inputUri, string $expectedFragment): void
    {
        $uri = $this->createUri($inputUri);
        self::assertSame($expectedFragment, $uri->getFragment());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function fragmentProvider(): array
    {
        return [
            'no fragment present' => ['http://example.com/path', ''],
            'hash sign without fragment' => ['http://example.com/path#', ''],
            'query without fragment' => ['http://example.com/path?query=1', ''],
            'empty URI' => ['', ''],
            'simple fragment' => ['http://example.com#section-1', 'section-1'],
            'fragment with query and path' => ['http://example.com/path?foo=bar#header', 'header'],
            'already percent-encoded spaces' => ['http://example.com#section%201', 'section%201'],
            'raw space auto percent-encoded' => ['http://example.com#section 1', 'section%201'],
            'prevent double encoding' => ['http://example.com#section%25201', 'section%25201'],
            'lowercase percent hex normalized to uppercase' => ['http://example.com#foo%2fbar', 'foo%2Fbar'],
            'slashes and question marks allowed' => ['http://example.com#/path/to?query=1', '/path/to?query=1'],
            'allowed sub-delimiters and pchar' => ['http://example.com#foo:bar@baz!$&\'()*+,;=', 'foo:bar@baz!$&\'()*+,;='],
        ];
    }

    public function testWithMethodReturnsSameInstanceIfValueUnchanged(): void
    {
        $uri = $this->createUri('http://example.com:8080/path?query=1#fragment');

        self::assertSame($uri, $uri->withScheme('http'));
        self::assertSame($uri, $uri->withHost('example.com'));
        self::assertSame($uri, $uri->withPort(8080));
        self::assertSame($uri, $uri->withPath('/path'));
        self::assertSame($uri, $uri->withQuery('query=1'));
        self::assertSame($uri, $uri->withFragment('fragment'));
    }

    #[DataProvider('validSchemeProvider')]
    public function testWithSchemeReturnsNewInstanceWithUpdatedScheme(
        string $inputUri,
        string $scheme,
        string $expectedScheme,
    ): void {
        $uri = $this->createUri($inputUri);
        $newUri = $uri->withScheme($scheme);

        self::assertNotSame($uri, $newUri);
        self::assertSame($expectedScheme, $newUri->getScheme());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validSchemeProvider(): array
    {
        return [
            'change scheme to https' => ['http://example.com', 'https', 'https'],
            'uppercase normalized to lowercase' => ['http://example.com', 'HTTPS', 'https'],
            'mixed case scheme' => ['http://example.com', 'HtTp', 'http'],
            'remove scheme using empty string' => ['http://example.com', '', ''],
            'custom scheme with allowed characters' => ['http://example.com', 'git+ssh', 'git+ssh'],
        ];
    }

    #[DataProvider('invalidSchemeProvider')]
    public function testWithSchemeThrowsExceptionForInvalidScheme(string $invalidScheme): void
    {
        $uri = $this->createUri('http://example.com');

        self::expectException(\InvalidArgumentException::class);
        $uri->withScheme($invalidScheme);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidSchemeProvider(): array
    {
        return [
            'starts with digit' => ['1http'],
            'contains invalid underscore' => ['http_scheme'],
            'contains trailing colon' => ['https:'],
            'contains spaces' => ['http scheme'],
            'contains slashes' => ['http://'],
        ];
    }

    #[DataProvider('validUserInfoProvider')]
    public function testWithUserInfoReturnsNewInstanceWithUpdatedUserInfo(
        string $inputUri,
        string $user,
        ?string $password,
        string $expectedUserInfo,
    ): void {
        $uri = $this->createUri($inputUri);
        $newUri = $uri->withUserInfo($user, $password);

        self::assertNotSame($uri, $newUri);
        self::assertSame($expectedUserInfo, $newUri->getUserInfo());
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function validUserInfoProvider(): array
    {
        return [
            'set username only' => ['http://example.com', 'john', null, 'john'],
            'set username and password' => ['http://example.com', 'john', 'secret', 'john:secret'],
            'set username with empty string password' => ['http://example.com', 'john', '', 'john'],
            'replace existing user info' => ['http://old:pass@example.com', 'newuser', 'newpass', 'newuser:newpass'],
            'remove user info using empty username' => ['http://user:pass@example.com', '', null, ''],
            'remove user info using empty username despite password provided' => ['http://user:pass@example.com', '', 'pass', ''],
        ];
    }

    #[DataProvider('validHostProvider')]
    public function testWithHostReturnsNewInstanceWithUpdatedHost(
        string $inputUri,
        string $host,
        string $expectedHost,
    ): void {
        $uri = $this->createUri($inputUri);
        $newUri = $uri->withHost($host);

        self::assertNotSame($uri, $newUri);
        self::assertSame($expectedHost, $newUri->getHost());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validHostProvider(): array
    {
        return [
            'set standard domain' => ['http://example.com', 'domain.org', 'domain.org'],
            'uppercase normalized to lowercase' => ['http://example.com', 'DOMAIN.ORG', 'domain.org'],
            'replace existing host' => ['http://old.com/path', 'new.com', 'new.com'],
            'remove host using empty string' => ['http://example.com/path', '', ''],
            'set ipv4 host' => ['http://example.com', '192.168.0.1', '192.168.0.1'],
            'set ipv6 host with brackets' => ['http://example.com', '[::1]', '[::1]'],
        ];
    }

    #[DataProvider('invalidHostProvider')]
    public function testWithHostThrowsExceptionForInvalidHost(string $invalidHost): void
    {
        $uri = $this->createUri('http://example.com');

        self::expectException(\InvalidArgumentException::class);
        $uri->withHost($invalidHost);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidHostProvider(): array
    {
        return [
            'contains path delimiter slash' => ['example.com/path'],
            'contains port delimiter colon' => ['example.com:8080'],
            'contains userinfo delimiter at-sign' => ['user@example.com'],
            'contains spaces' => ['example .com'],
            'invalid ipv6 without brackets' => ['2001:db8:1f70::999'],
            'invalid characters' => ['example#com'],
        ];
    }

    #[DataProvider('validPortProvider')]
    public function testWithPortReturnsNewInstanceWithUpdatedPort(
        string $inputUri,
        ?int $port,
        ?int $expectedPort,
    ): void {
        $uri = $this->createUri($inputUri);
        $newUri = $uri->withPort($port);

        self::assertNotSame($uri, $newUri);
        self::assertSame($expectedPort, $newUri->getPort());
    }

    /**
     * @return array<string, array{string, ?int}>
     */
    public static function validPortProvider(): array
    {
        return [
            'set custom port' => ['http://example.com', 8080, 8080],
            'set standard http port (normalizes getPort to null)' => ['http://example.com', 80, null],
            'set standard https port (normalizes getPort to null)' => ['https://example.com', 443, null],
            'set port on schemeless URI' => ['//example.com', 80, 80],
            'replace existing port' => ['http://example.com:8080', 9000, 9000],
            'remove port using null' => ['http://example.com:8080', null, null],
            'minimum valid port range boundary (zero)' => ['http://example.com', 0, 0],
            'minimum non-zero valid port' => ['http://example.com', 1, 1],
            'maximum valid port range boundary' => ['http://example.com', 65535, 65535],
        ];
    }

    #[DataProvider('invalidPortProvider')]
    public function testWithPortThrowsExceptionForInvalidPort(int $invalidPort): void
    {
        $uri = $this->createUri('http://example.com');

        self::expectException(\InvalidArgumentException::class);
        $uri->withPort($invalidPort);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidPortProvider(): array
    {
        return [
            'negative port' => [-1],
            'port exceeding 65535' => [65536],
            'large integer out of range' => [999999],
        ];
    }

    #[DataProvider('pathDataProvider')]
    public function testWithPath(string $inputPath, string $expectedPath): void
    {
        $uri = $this->createUri('http://example.com/original');
        $newUri = $uri->withPath($inputPath);

        self::assertNotSame($uri, $newUri);
        self::assertSame('/original', $uri->getPath());
        self::assertSame($expectedPath, $newUri->getPath());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pathDataProvider(): array
    {
        return [
            'empty path' => ['', ''],
            'rootless path auto-prefixed with slash' => ['foo/bar', '/foo/bar'],
            'absolute path' => ['/foo/bar', '/foo/bar'],
            'root path' => ['/', '/'],
            'encodes spaces' => ['/foo bar/baz', '/foo%20bar/baz'],
            'preserves existing percent-encoding' => ['/foo%20bar', '/foo%20bar'],
            'encodes query delimiter ?' => ['/foo?bar', '/foo%3Fbar'],
            'encodes fragment delimiter #' => ['/foo#bar', '/foo%23bar'],
            'preserves RFC 3986 unreserved and pchar sub-delims' => [
                '/a-z_123.~!$&\'()*+,;=:@',
                '/a-z_123.~!$&\'()*+,;=:@',
            ],
            'encodes multibyte UTF-8 characters' => [
                '/testing/öäü',
                '/testing/%C3%B6%C3%A4%C3%BC',
            ],
            'does not double-encode percent signs' => ['/foo%2Fbar', '/foo%2Fbar'],
        ];
    }

    #[DataProvider('queryDataProvider')]
    public function testWithQuery(string $inputQuery, string $expectedQuery): void
    {
        $uri = $this->createUri('http://example.com?original=1');
        $newUri = $uri->withQuery($inputQuery);

        self::assertNotSame($uri, $newUri);
        self::assertSame('original=1', $uri->getQuery());
        self::assertSame($expectedQuery, $newUri->getQuery());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function queryDataProvider(): array
    {
        return [
            'empty query removes query string' => ['', ''],
            'standard key-value pair' => ['foo=bar', 'foo=bar'],
            'multiple parameters' => ['foo=bar&baz=qux', 'foo=bar&baz=qux'],
            'array parameter syntax' => ['foo[]=1&foo[]=2', 'foo%5B%5D=1&foo%5B%5D=2'],
            'encodes spaces' => ['foo=bar baz', 'foo=bar%20baz'],
            'preserves existing percent-encoding' => ['foo=bar%20baz', 'foo=bar%20baz'],
            'encodes fragment delimiter #' => ['foo=bar#baz', 'foo=bar%23baz'],
            'allows unencoded slashes and question marks' => [
                'redirect=http://example.com/path?a=1',
                'redirect=http://example.com/path?a=1',
            ],
            'preserves RFC 3986 unreserved, sub-delims, and pchar allowed chars' => [
                'a-z_123.~!$&\'()*+,;=:@/?',
                'a-z_123.~!$&\'()*+,;=:@/?',
            ],
            'encodes multibyte UTF-8 characters' => [
                'search=öäü',
                'search=%C3%B6%C3%A4%C3%BC',
            ],
            'does not double-encode percent signs' => ['foo=bar%2Fbaz', 'foo=bar%2Fbaz'],
        ];
    }

    #[DataProvider('invalidQueryProvider')]
    public function testWithQueryThrowsExceptionOnLeadingQuestionMark(string $invalidQuery): void
    {
        self::expectException(\InvalidArgumentException::class);
        $this->createUri('http://example.com')->withQuery($invalidQuery);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidQueryProvider(): array
    {
        return [
            'leading question mark' => ['?foo=bar'],
            'single question mark' => ['?'],
        ];
    }

    #[DataProvider('fragmentMutationProvider')]
    public function testWithFragment(string $initialUri, string $fragment, string $expectedFragment): void
    {
        $uri = $this->createUri($initialUri);
        $newUri = $uri->withFragment($fragment);

        self::assertNotSame($uri, $newUri);
        self::assertSame($expectedFragment, $newUri->getFragment());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function fragmentMutationProvider(): array
    {
        return [
            'simple string fragment' => ['http://example.com', 'section1', 'section1'],
            'empty string removes fragment' => ['http://example.com#section1', '', ''],
            'percent-encoded characters preserved' => ['http://example.com', 'foo%20bar', 'foo%20bar'],
            'unencoded spaces auto-encoded' => ['http://example.com', 'foo bar', 'foo%20bar'],
            'allowed RFC 3986 unreserved and sub-delims' => ['http://example.com', 'a-z_~.!:&=+', 'a-z_~.!:&=+'],
            'slashes and question marks allowed in fragment' => ['http://example.com', '/path?query', '/path?query'],
            'multibyte characters encoded' => ['http://example.com', 'fötter', 'f%C3%B6tter'],
            'double percent sign encoding' => ['http://example.com', '100%pure', '100%25pure'],
        ];
    }

    #[DataProvider('invalidFragmentProvider')]
    public function testWithFragmentThrowsExceptionOnLeadingHash(string $invalidFragment): void
    {
        self::expectException(\InvalidArgumentException::class);
        $this->createUri('http://example.com')->withFragment($invalidFragment);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidFragmentProvider(): array
    {
        return [
            'leading hash' => ['#section1'],
            'single hash' => ['#'],
        ];
    }

    #[DataProvider('toStringDataProvider')]
    public function testToString(string $inputUri, string $expectedString): void
    {
        $uri = $this->createUri($inputUri);

        self::assertSame($expectedString, (string) $uri);
        self::assertSame($expectedString, $uri->__toString());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function toStringDataProvider(): array
    {
        return [
            'full uri with all components' => [
                'https://user:pass@example.com:8080/path/to/resource?query=1#fragment',
                'https://user:pass@example.com:8080/path/to/resource?query=1#fragment',
            ],
            'empty string URI' => ['', ''],
            'scheme suffixed with colon' => ['http://example.com', 'http://example.com'],
            'authority prefixed with double slash' => ['//user:pass@example.com:8080/path', '//user:pass@example.com:8080/path'],
            'query prefixed with question mark' => ['/path?foo=bar', '/path?foo=bar'],
            'fragment prefixed with hash' => ['/path#section', '/path#section'],
            'omits delimiter when query is empty' => ['http://example.com/path?', 'http://example.com/path'],
            'omits delimiter when fragment is empty' => ['http://example.com/path#', 'http://example.com/path'],
            'relative path without authority or scheme' => ['path/to/resource', 'path/to/resource'],
            'absolute path without authority or scheme' => ['/path/to/resource', '/path/to/resource'],
        ];
    }
}
