<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\response;

/**
 * An HTTP Status Code registry class with `all()` method. Code & text resouces can be
 * found at https://www.iana.org/assignments/http-status-codes/http-status-codes.txt
 *
 * @package froq\http\response
 * @class   froq\http\response\Statuses
 * @author  Kerem Güneş
 * @since   4.0
 * @internal
 */
class Statuses
{
    /** Status constants. */
    public final const
        // Informationals (1xx).
        CONTINUE                        = 100,
        SWITCHING_PROTOCOLS             = 101,
        PROCESSING                      = 102,
        EARLY_HINTS                     = 103,

        // Successes (2xx).
        OK                              = 200,
        CREATED                         = 201,
        ACCEPTED                        = 202,
        NON_AUTHORITATIVE_INFORMATION   = 203,
        NO_CONTENT                      = 204,
        RESET_CONTENT                   = 205,
        PARTIAL_CONTENT                 = 206,
        MULTI_STATUS                    = 207,
        ALREADY_REPORTED                = 208,
        IM_USED                         = 226,

        // Redirections (3xx).
        MULTIPLE_CHOICES                = 300,
        MOVED_PERMANENTLY               = 301,
        FOUND                           = 302,
        SEE_OTHER                       = 303,
        NOT_MODIFIED                    = 304,
        USE_PROXY                       = 305,
        TEMPORARY_REDIRECT              = 307,
        PERMANENT_REDIRECT              = 308,

        // Client errors (4xx).
        BAD_REQUEST                     = 400,
        UNAUTHORIZED                    = 401,
        PAYMENT_REQUIRED                = 402,
        FORBIDDEN                       = 403,
        NOT_FOUND                       = 404,
        METHOD_NOT_ALLOWED              = 405,
        NOT_ALLOWED                     = 405, // @alias
        NOT_ACCEPTABLE                  = 406,
        PROXY_AUTHENTICATION_REQUIRED   = 407,
        REQUEST_TIMEOUT                 = 408,
        CONFLICT                        = 409,
        GONE                            = 410,
        LENGTH_REQUIRED                 = 411,
        PRECONDITION_FAILED             = 412,
        PAYLOAD_TOO_LARGE               = 413,
        URI_TOO_LONG                    = 414,
        UNSUPPORTED_MEDIA_TYPE          = 415,
        RANGE_NOT_SATISFIABLE           = 416,
        EXPECTATION_FAILED              = 417,
        IMA_TEAPOT                      = 418,
        ENHANCE_YOUR_CALM               = 420,
        MISDIRECTED_REQUEST             = 421,
        UNPROCESSABLE_ENTITY            = 422,
        LOCKED                          = 423,
        FAILED_DEPENDENCY               = 424,
        TOO_EARLY                       = 425,
        UPGRADE_REQUIRED                = 426,
        PRECONDITION_REQUIRED           = 428,
        TOO_MANY_REQUESTS               = 429,
        REQUEST_HEADER_FIELDS_TOO_LARGE = 431,
        NO_RESPONSE                     = 444,
        RETRY_WITH                      = 449,
        UNAVAILABLE_FOR_LEGAL_REASONS   = 451,
        CLIENT_CLOSED_REQUEST           = 499,

        // Server errors (5xx).
        INTERNAL_SERVER_ERROR           = 500,
        INTERNAL                        = 500, // @alias
        NOT_IMPLEMENTED                 = 501,
        BAD_GATEWAY                     = 502,
        SERVICE_UNAVAILABLE             = 503,
        GATEWAY_TIMEOUT                 = 504,
        HTTP_VERSION_NOT_SUPPORTED      = 505,
        VARIANT_ALSO_NEGOTIATES         = 506,
        INSUFFICIENT_STORAGE            = 507,
        LOOP_DETECTED                   = 508,
        BANDWIDTH_LIMIT_EXCEEDED        = 509,
        NOT_EXTENDED                    = 510,
        NETWORK_AUTHENTICATION_REQUIRED = 511,
        NETWORK_READ_TIMEOUT_ERROR      = 598,
        NETWORK_CONNECT_TIMEOUT_ERROR   = 599;

    /** Status map. */
    private static array $all = [
        // Informationals (1xx).
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // Successes (2xx).
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

        // Redirections (3xx).
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // Client errors (4xx).
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
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a Teapot',
        420 => 'Enhance Your Calm',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'No Response',
        449 => 'Retry With',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',

        // Server errors (5xx).
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        598 => 'Network Read Timeout Error',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * Get status map.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::$all;
    }
}
