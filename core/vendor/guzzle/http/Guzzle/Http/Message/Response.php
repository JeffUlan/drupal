<?php

namespace Guzzle\Http\Message;

use Guzzle\Common\Collection;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Parser\ParserRegistry;

/**
 * Guzzle HTTP response object
 */
class Response extends AbstractMessage
{
    /**
     * @var array Array of reason phrases and their corresponding status codes
     */
    private static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Reserved for WebDAV advanced collections expired proposal',
        426 => 'Upgrade required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    );

    /**
     * @var EntityBodyInterface The response body
     */
    protected $body;

    /**
     * @var string The reason phrase of the response (human readable code)
     */
    protected $reasonPhrase;

    /**
     * @var string The status code of the response
     */
    protected $statusCode;

    /**
     * @var string Response protocol
     */
    protected $protocol = 'HTTP';

    /**
     * @var array Information about the request
     */
    protected $info = array();

    /**
     * @var RequestInterface Request object that may or may not be set
     */
    protected $request = null;

    /**
     * @var array Cacheable response codes (see RFC 2616:13.4)
     */
    protected $cacheResponseCodes = array(200, 203, 206, 300, 301, 410);

    /**
     * @var Response If a redirect was issued or an intermediate response was issued
     */
    protected $previous;

    /**
     * Create a new Response based on a raw response message
     *
     * @param string $message Response message
     *
     * @return Response|bool Returns false on error
     */
    public static function fromMessage($message)
    {
        $data = ParserRegistry::getInstance()->getParser('message')->parseResponse($message);
        if (!$data) {
            return false;
        }

        $response = new static($data['code'], $data['headers'], $data['body']);
        $response->setProtocol($data['protocol'], $data['version'])
                 ->setStatus($data['code'], $data['reason_phrase']);

        // Set the appropriate Content-Length if the one set is inaccurate (e.g. setting to X)
        $contentLength = (string) $response->getHeader('Content-Length');
        $actualLength = strlen($data['body']);
        if (strlen($data['body']) > 0 && $contentLength != $actualLength) {
            $response->setHeader('Content-Length', $actualLength);
        }

        return $response;
    }

    /**
     * Construct the response
     *
     * @param string                              $statusCode The response status code (e.g. 200, 404, etc)
     * @param Collection|array                    $headers    The response headers
     * @param string|resource|EntityBodyInterface $body       The body of the response
     *
     * @throws BadResponseException if an invalid response code is given
     */
    public function __construct($statusCode, $headers = null, $body = null)
    {
        $this->setStatus($statusCode);
        $this->params = new Collection();
        $this->body = EntityBody::factory($body !== null ? $body : '');

        if ($headers) {
            if (!is_array($headers) && !($headers instanceof Collection)) {
                throw new BadResponseException('Invalid headers argument received');
            }
            foreach ($headers as $key => $value) {
                $this->addHeaders(array($key => $value));
            }
        }
    }

    /**
     * Convert the response object to a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage();
    }

    /**
     * Get the response entity body
     *
     * @param bool $asString Set to TRUE to return a string of the body rather than a full body object
     *
     * @return EntityBodyInterface|string
     */
    public function getBody($asString = false)
    {
        return $asString ? (string) $this->body : $this->body;
    }

    /**
     * Set the response entity body
     *
     * @param EntityBodyInterface|string $body Body to set
     *
     * @return self
     */
    public function setBody($body)
    {
        $this->body = EntityBody::factory($body);

        return $this;
    }

    /**
     * Set the protocol and protocol version of the response
     *
     * @param string $protocol Response protocol
     * @param string $version  Protocol version
     *
     * @return Response
     */
    public function setProtocol($protocol, $version)
    {
        $this->protocol = $protocol;
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * Get the protocol used for the response (e.g. HTTP)
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol ?: 'HTTP';
    }

    /**
     * Get the HTTP protocol version
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion ?: '1.1';
    }

    /**
     * Get a cURL transfer information
     *
     * @param string $key A single statistic to check
     *
     * @return array|string|null Returns all stats if no key is set, a single stat if a key is set, or null if a key
     *                           is set and not found
     * @link http://www.php.net/manual/en/function.curl-getinfo.php
     */
    public function getInfo($key = null)
    {
        if ($key === null) {
            return $this->info;
        } elseif (array_key_exists($key, $this->info)) {
            return $this->info[$key];
        } else {
            return null;
        }
    }

    /**
     * Set the transfer information
     *
     * @param array $info Array of cURL transfer stats
     *
     * @return Response
     */
    public function setInfo(array $info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Set the response status
     *
     * @param int    $statusCode   Response status code to set
     * @param string $reasonPhrase Response reason phrase
     *
     * @return Response
     * @throws BadResponseException when an invalid response code is received
     */
    public function setStatus($statusCode, $reasonPhrase = '')
    {
        $this->statusCode = (int) $statusCode;

        if (!$reasonPhrase && array_key_exists($this->statusCode, self::$statusTexts)) {
            $this->reasonPhrase = self::$statusTexts[$this->statusCode];
        } else {
            $this->reasonPhrase = $reasonPhrase;
        }

        return $this;
    }

    /**
     * Get the response status code
     *
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get the entire response as a string
     *
     * @return string
     */
    public function getMessage()
    {
        $message = $this->getRawHeaders();

        // Only include the body in the message if the size is < 2MB
        $size = $this->body->getSize();
        if ($size < 2097152) {
            $message .= (string) $this->body;
        }

        return $message;
    }

    /**
     * Get the the raw message headers as a string
     *
     * @return string
     */
    public function getRawHeaders()
    {
        $headers = 'HTTP/1.1 ' . $this->statusCode . ' ' . $this->reasonPhrase . "\r\n";
        $lines = $this->getHeaderLines();
        if (!empty($lines)) {
            $headers .= implode("\r\n", $lines) . "\r\n";
        }

        return $headers . "\r\n";
    }

    /**
     * Get the request object (or null) that is associated with this response
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response reason phrase- a human readable version of the numeric
     * status code
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * Get the Accept-Ranges HTTP header
     *
     * @return string Returns what partial content range types this server supports.
     */
    public function getAcceptRanges()
    {
        return $this->getHeader('Accept-Ranges', true);
    }

    /**
     * Get the Age HTTP header
     *
     * @param bool $headerOnly Set to TRUE to only retrieve the Age header rather than calculating the age
     *
     * @return integer|null Returns the age the object has been in a proxy cache in seconds.
     */
    public function getAge($headerOnly = false)
    {
        $age = $this->getHeader('Age', true);

        if (!$headerOnly && $age === null && $this->getDate()) {
            $age = time() - strtotime($this->getDate());
        }

        return $age;
    }

    /**
     * Get the Allow HTTP header
     *
     * @return string|null Returns valid actions for a specified resource. To be used for a 405 Method not allowed.
     */
    public function getAllow()
    {
        return $this->getHeader('Allow', true);
    }

    /**
     * Check if an HTTP method is allowed by checking the Allow response header
     *
     * @param string $method Method to check
     *
     * @return bool
     */
    public function isMethodAllowed($method)
    {
        $allow = $this->getHeader('Allow');
        if ($allow) {
            foreach (explode(',', $allow) as $allowable) {
                if (!strcasecmp(trim($allowable), $method)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the Cache-Control HTTP header
     *
     * @return Header|null Returns a Header object that tells all caching mechanisms from server to client whether they
     *                     may cache this object.
     */
    public function getCacheControl()
    {
        return $this->getHeader('Cache-Control');
    }

    /**
     * Get the Connection HTTP header
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->getHeader('Connection', true);
    }

    /**
     * Get the Content-Encoding HTTP header
     *
     * @return string|null Returns the type of encoding used on the data. One of compress, deflate, gzip, identity.
     */
    public function getContentEncoding()
    {
        return $this->getHeader('Content-Encoding', true);
    }

    /**
     * Get the Content-Language HTTP header
     *
     * @return string|null Returns the language the content is in.
     */
    public function getContentLanguage()
    {
        return $this->getHeader('Content-Language', true);
    }

    /**
     * Get the Content-Length HTTP header
     *
     * @return integer Returns the length of the response body in bytes
     */
    public function getContentLength()
    {
        return (int) $this->getHeader('Content-Length', true);
    }

    /**
     * Get the Content-Location HTTP header
     *
     * @return string|null Returns an alternate location for the returned data (e.g /index.htm)
     */
    public function getContentLocation()
    {
        return $this->getHeader('Content-Location', true);
    }

    /**
     * Get the Content-Disposition HTTP header
     *
     * @return string|null Returns the Content-Disposition header
     */
    public function getContentDisposition()
    {
        return (string) $this->getHeader('Content-Disposition')->setGlue(';');
    }

    /**
     * Get the Content-MD5 HTTP header
     *
     * @return string|null Returns a Base64-encoded binary MD5 sum of the content of the response.
     */
    public function getContentMd5()
    {
        return $this->getHeader('Content-MD5', true);
    }

    /**
     * Get the Content-Range HTTP header
     *
     * @return string Returns where in a full body message this partial message belongs (e.g. bytes 21010-47021/47022).
     */
    public function getContentRange()
    {
        return $this->getHeader('Content-Range', true);
    }

    /**
     * Get the Content-Type HTTP header
     *
     * @return string Returns the mime type of this content.
     */
    public function getContentType()
    {
        return $this->getHeader('Content-Type', true);
    }

    /**
     * Checks if the Content-Type is of a certain type.  This is useful if the
     * Content-Type header contains charset information and you need to know if
     * the Content-Type matches a particular type.
     *
     * @param string $type Content type to check against
     *
     * @return bool
     */
    public function isContentType($type)
    {
        return stripos($this->getContentType(), $type) !== false;
    }

    /**
     * Get the Date HTTP header
     *
     * @return string|null Returns the date and time that the message was sent.
     */
    public function getDate()
    {
        return $this->getHeader('Date', true);
    }

    /**
     * Get the ETag HTTP header
     *
     * @return string|null Returns an identifier for a specific version of a resource, often a Message digest.
     */
    public function getEtag()
    {
        $etag = $this->getHeader('ETag', true);

        return $etag ? str_replace('"', '', $etag) : null;
    }

    /**
     * Get the Expires HTTP header
     *
     * @return string|null Returns the date/time after which the response is considered stale.
     */
    public function getExpires()
    {
        return $this->getHeader('Expires', true);
    }

    /**
     * Get the Last-Modified HTTP header
     *
     * @return string|null Returns the last modified date for the requested object, in RFC 2822 format
     *                     (e.g. Tue, 15 Nov 1994 12:45:26 GMT)
     */
    public function getLastModified()
    {
        return $this->getHeader('Last-Modified', true);
    }

    /**
     * Get the Location HTTP header
     *
     * @return string|null Used in redirection, or when a new resource has been created.
     */
    public function getLocation()
    {
        return $this->getHeader('Location', true);
    }

    /**
     * Get the Pragma HTTP header
     *
     * @return Header|null Returns the implementation-specific headers that may have various effects anywhere along
     *                     the request-response chain.
     */
    public function getPragma()
    {
        return $this->getHeader('Pragma');
    }

    /**
     * Get the Proxy-Authenticate HTTP header
     *
     * @return string|null Authentication to access the proxy (e.g. Basic)
     */
    public function getProxyAuthenticate()
    {
        return $this->getHeader('Proxy-Authenticate', true);
    }

    /**
     * Get the Retry-After HTTP header
     *
     * @return int|null If an entity is temporarily unavailable, this instructs the client to try again after a
     *                  specified period of time.
     */
    public function getRetryAfter()
    {
        $time = $this->getHeader('Retry-After', true);
        if ($time === null) {
            return null;
        }

        if (!is_numeric($time)) {
            $time = strtotime($time) - time();
        }

        return (int) $time;
    }

    /**
     * Get the Server HTTP header
     *
     * @return string|null A name for the server
     */
    public function getServer()
    {
        return $this->getHeader('Server', true);
    }

    /**
     * Get the Set-Cookie HTTP header
     *
     * @return Header|null An HTTP cookie.
     */
    public function getSetCookie()
    {
        return $this->getHeader('Set-Cookie');
    }

    /**
     * Get the Trailer HTTP header
     *
     * @return string|null The Trailer general field value indicates that the given set of header fields is present in
     *                     the trailer of a message encoded with chunked transfer-coding.
     */
    public function getTrailer()
    {
        return $this->getHeader('Trailer', true);
    }

    /**
     * Get the Transfer-Encoding HTTP header
     *
     * @return string|null The form of encoding used to safely transfer the entity to the user. Currently defined
     *                     methods are: chunked
     */
    public function getTransferEncoding()
    {
        return $this->getHeader('Transfer-Encoding', true);
    }

    /**
     * Get the Vary HTTP header
     *
     * @return string|null Tells downstream proxies how to match future request headers to decide whether the cached
     *                     response can be used rather than requesting a fresh one from the origin server.
     */
    public function getVary()
    {
        return $this->getHeader('Vary', true);
    }

    /**
     * Get the Via HTTP header
     *
     * @return string|null Informs the client of proxies through which the response was sent.
     *                     (e.g. 1.0 fred, 1.1 nowhere.com (Apache/1.1))
     */
    public function getVia()
    {
        return $this->getHeader('Via', true);
    }

    /**
     * Get the Warning HTTP header
     *
     * @return string|null A general warning about possible problems with the entity body.
     *                     (e.g. 199 Miscellaneous warning)
     */
    public function getWarning()
    {
        return $this->getHeader('Warning', true);
    }

    /**
     * Get the WWW-Authenticate HTTP header
     *
     * @return string|null Indicates the authentication scheme that should be used to access the requested entity
     *                     (e.g. Basic)
     */
    public function getWwwAuthenticate()
    {
        return $this->getHeader('WWW-Authenticate', true);
    }

    /**
     * Checks if HTTP Status code is a Client Error (4xx)
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Checks if HTTP Status code is Server OR Client Error (4xx or 5xx)
     *
     * @return boolean
     */
    public function isError()
    {
        return $this->isClientError() || $this->isServerError();
    }

    /**
     * Checks if HTTP Status code is Information (1xx)
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->statusCode < 200;
    }

    /**
     * Checks if HTTP Status code is a Redirect (3xx)
     *
     * @return bool
     */
    public function isRedirect()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Checks if HTTP Status code is Server Error (5xx)
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Checks if HTTP Status code is Successful (2xx | 304)
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return ($this->statusCode >= 200 && $this->statusCode < 300) || $this->statusCode == 304;
    }

    /**
     * Set the request object associated with the response
     *
     * @param RequestInterface $request The request object used to generate the response
     *
     * @return Response
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Check if the response can be cached
     *
     * @return bool Returns TRUE if the response can be cached or false if not
     */
    public function canCache()
    {
        // Check if the response is cacheable based on the code
        if (!in_array((int) $this->getStatusCode(), $this->cacheResponseCodes)) {
            return false;
        }

        // Make sure a valid body was returned and can be cached
        if ((!$this->getBody()->isReadable() || !$this->getBody()->isSeekable())
            && ($this->getContentLength() > 0 || $this->getTransferEncoding() == 'chunked')) {
            return false;
        }

        // Never cache no-store resources (this is a private cache, so private
        // can be cached)
        if ($this->hasCacheControlDirective('no-store')) {
            return false;
        }

        return $this->isFresh() || $this->getFreshness() === null || $this->canValidate();
    }

    /**
     * Gets the number of seconds from the current time in which this response is still considered fresh
     *
     * @return int|null Returns the number of seconds
     */
    public function getMaxAge()
    {
        // s-max-age, then max-age, then Expires
        if ($age = $this->getCacheControlDirective('s-maxage')) {
            return $age;
        }

        if ($age = $this->getCacheControlDirective('max-age')) {
            return $age;
        }

        if ($this->getHeader('Expires')) {
            return strtotime($this->getExpires()) - time();
        }

        return null;
    }

    /**
     * Check if the response is considered fresh.
     *
     * A response is considered fresh when its age is less than the freshness lifetime (maximum age) of the response.
     *
     * @return bool|null
     */
    public function isFresh()
    {
        $fresh = $this->getFreshness();

        return $fresh === null ? null : $this->getFreshness() > 0;
    }

    /**
     * Check if the response can be validated against the origin server using a conditional GET request.
     *
     * @return bool
     */
    public function canValidate()
    {
        return $this->getEtag() || $this->getLastModified();
    }

    /**
     * Get the freshness of the response by returning the difference of the maximum lifetime of the response and the
     * age of the response (max-age - age).
     *
     * Freshness values less than 0 mean that the response is no longer fresh and is ABS(freshness) seconds expired.
     * Freshness values of greater than zero is the number of seconds until the response is no longer fresh. A NULL
     * result means that no freshness information is available.
     *
     * @return int
     */
    public function getFreshness()
    {
        $maxAge = $this->getMaxAge();
        $age = $this->getAge();

        return $maxAge && $age ? ($maxAge - $age) : null;
    }

    /**
     * Get the previous response (e.g. Redirect response)
     *
     * @return null|Response
     */
    public function getPreviousResponse()
    {
        return $this->previous;
    }

    /**
     * Set the previous response
     *
     * @param Response $response Response to set
     *
     * @return self
     */
    public function setPreviousResponse(Response $response)
    {
        $this->previous = $response;

        return $this;
    }

    /**
     * Parse the JSON response body and return an array
     *
     * @return array
     * @throws RuntimeException if the response body is not in JSON format
     */
    public function json()
    {
        $data = json_decode((string) $this->body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException('Unable to parse response body into JSON: ' . json_last_error());
        }

        return $data ?: array();
    }

    /**
     * Parse the XML response body and return a SimpleXMLElement
     *
     * @return \SimpleXMLElement
     * @throws RuntimeException if the response body is not in XML format
     */
    public function xml()
    {
        try {
            // Allow XML to be retrieved even if there is no response body
            $xml = new \SimpleXMLElement((string) $this->body ?: '<root />');
        } catch (\Exception $e) {
            throw new RuntimeException('Unable to parse response body into XML: ' . $e->getMessage());
        }

        return $xml;
    }
}
