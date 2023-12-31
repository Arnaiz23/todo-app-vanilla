<?php declare(strict_types=1);

namespace Lkrms\Curler\Catalog;

use Lkrms\Concept\Dictionary;

/**
 * Curler property names
 *
 * @extends Dictionary<string>
 */
final class CurlerProperty extends Dictionary
{
    public const CACHE_RESPONSE = 'CacheResponse';
    public const CACHE_POST_RESPONSE = 'CachePostResponse';
    public const EXPIRY = 'Expiry';
    public const FLUSH = 'Flush';
    public const RESPONSE_CACHE_KEY_CALLBACK = 'ResponseCacheKeyCallback';
    public const THROW_HTTP_ERRORS = 'ThrowHttpErrors';
    public const RESPONSE_CALLBACK = 'ResponseCallback';
    public const CONNECT_TIMEOUT = 'ConnectTimeout';
    public const TIMEOUT = 'Timeout';
    public const FOLLOW_REDIRECTS = 'FollowRedirects';
    public const MAX_REDIRECTS = 'MaxRedirects';
    public const HANDLE_COOKIES = 'HandleCookies';
    public const COOKIE_CACHE_KEY = 'CookieCacheKey';
    public const RETRY_AFTER_TOO_MANY_REQUESTS = 'RetryAfterTooManyRequests';
    public const RETRY_AFTER_MAX_SECONDS = 'RetryAfterMaxSeconds';
    public const EXPECT_JSON = 'ExpectJson';
    public const POST_JSON = 'PostJson';
    public const PRESERVE_KEYS = 'PreserveKeys';
    public const DATE_FORMATTER = 'DateFormatter';
    public const USER_AGENT = 'UserAgent';
    public const ALWAYS_PAGINATE = 'AlwaysPaginate';
    public const OBJECT_AS_ARRAY = 'ObjectAsArray';
}
