<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Base Site URL
     * --------------------------------------------------------------------------
     *
     * URL to your CodeIgniter root. Typically, this will be your base URL,
     * WITH a trailing slash:
     *
     * E.g., http://example.com/
     */
    public string $baseURL = 'http://localhost:8080/';

    /**
     * --------------------------------------------------------------------------
     * Auto-detect Base Site URL
     * --------------------------------------------------------------------------
     *
     * When true, on web requests the baseURL is rebuilt from the incoming
     * request (Host header + scheme), so the application works unchanged
     * behind a domain, a reverse proxy or a temporary tunnel (e.g.
     * cloudflared). The scheme honors X-Forwarded-Proto / CF-Visitor so
     * https tunnels do not generate http:// links.
     *
     * Set to false (or app.autoDetectBaseURL = false in .env) to always use
     * the configured $baseURL, for example on a fixed production domain.
     */
    public bool $autoDetectBaseURL = true;

    /**
     * Allowed Hostnames in the Site URL other than the hostname in the baseURL.
     * If you want to accept multiple Hostnames, set this.
     *
     * E.g.,
     * When your site URL ($baseURL) is 'http://example.com/', and your site
     * also accepts 'http://media.example.com/' and 'http://accounts.example.com/':
     *     ['media.example.com', 'accounts.example.com']
     *
     * @var list<string>
     */
    public array $allowedHostnames = [];

    /**
     * --------------------------------------------------------------------------
     * Index File
     * --------------------------------------------------------------------------
     *
     * Typically, this will be your `index.php` file, unless you've renamed it to
     * something else. If you have configured your web server to remove this file
     * from your site URIs, set this variable to an empty string.
     */
    public string $indexPage = 'index.php';

    /**
     * --------------------------------------------------------------------------
     * URI PROTOCOL
     * --------------------------------------------------------------------------
     *
     * This item determines which server global should be used to retrieve the
     * URI string. The default setting of 'REQUEST_URI' works for most servers.
     * If your links do not seem to work, try one of the other delicious flavors:
     *
     *  'REQUEST_URI': Uses $_SERVER['REQUEST_URI']
     * 'QUERY_STRING': Uses $_SERVER['QUERY_STRING']
     *    'PATH_INFO': Uses $_SERVER['PATH_INFO']
     *
     * WARNING: If you set this to 'PATH_INFO', URIs will always be URL-decoded!
     */
    public string $uriProtocol = 'REQUEST_URI';

    /*
    |--------------------------------------------------------------------------
    | Allowed URL Characters
    |--------------------------------------------------------------------------
    |
    | This lets you specify which characters are permitted within your URLs.
    | When someone tries to submit a URL with disallowed characters they will
    | get a warning message.
    |
    | As a security measure you are STRONGLY encouraged to restrict URLs to
    | as few characters as possible.
    |
    | By default, only these are allowed: `a-z 0-9~%.:_-`
    |
    | Set an empty string to allow all characters -- but only if you are insane.
    |
    | The configured value is actually a regular expression character group
    | and it will be used as: '/\A[<permittedURIChars>]+\z/iu'
    |
    | DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!!
    |
    */
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    /**
     * --------------------------------------------------------------------------
     * Default Locale
     * --------------------------------------------------------------------------
     *
     * The Locale roughly represents the language and location that your visitor
     * is viewing the site from. It affects the language strings and other
     * strings (like currency markers, numbers, etc), that your program
     * should run under for this request.
     */
    public string $defaultLocale = 'en';

    /**
     * --------------------------------------------------------------------------
     * Negotiate Locale
     * --------------------------------------------------------------------------
     *
     * If true, the current Request object will automatically determine the
     * language to use based on the value of the Accept-Language header.
     *
     * If false, no automatic detection will be performed.
     */
    public bool $negotiateLocale = false;

    /**
     * --------------------------------------------------------------------------
     * Supported Locales
     * --------------------------------------------------------------------------
     *
     * If $negotiateLocale is true, this array lists the locales supported
     * by the application in descending order of priority. If no match is
     * found, the first locale will be used.
     *
     * IncomingRequest::setLocale() also uses this list.
     *
     * @var list<string>
     */
    public array $supportedLocales = ['en'];

    /**
     * --------------------------------------------------------------------------
     * Application Timezone
     * --------------------------------------------------------------------------
     *
     * The default timezone that will be used in your application to display
     * dates with the date helper, and can be retrieved through app_timezone()
     *
     * @see https://www.php.net/manual/en/timezones.php for list of timezones
     *      supported by PHP.
     */
    public string $appTimezone = 'America/Lima';

    /**
     * --------------------------------------------------------------------------
     * Default Character Set
     * --------------------------------------------------------------------------
     *
     * This determines which character set is used by default in various methods
     * that require a character set to be provided.
     *
     * @see http://php.net/htmlspecialchars for a list of supported charsets.
     */
    public string $charset = 'UTF-8';

    /**
     * --------------------------------------------------------------------------
     * Force Global Secure Requests
     * --------------------------------------------------------------------------
     *
     * If true, this will force every request made to this application to be
     * made via a secure connection (HTTPS). If the incoming request is not
     * secure, the user will be redirected to a secure version of the page
     * and the HTTP Strict Transport Security (HSTS) header will be set.
     */
    public bool $forceGlobalSecureRequests = false;

    /**
     * --------------------------------------------------------------------------
     * Reverse Proxy IPs
     * --------------------------------------------------------------------------
     *
     * If your server is behind a reverse proxy, you must whitelist the proxy
     * IP addresses from which CodeIgniter should trust headers such as
     * X-Forwarded-For or Client-IP in order to properly identify
     * the visitor's IP address.
     *
     * You need to set a proxy IP address or IP address with subnets and
     * the HTTP header for the client IP address.
     *
     * Here are some examples:
     *     [
     *         '10.0.1.200'     => 'X-Forwarded-For',
     *         '192.168.5.0/24' => 'X-Real-IP',
     *     ]
     *
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    /**
     * --------------------------------------------------------------------------
     * Content Security Policy
     * --------------------------------------------------------------------------
     *
     * Enables the Response's Content Secure Policy to restrict the sources that
     * can be used for images, scripts, CSS files, audio, video, etc. If enabled,
     * the Response object will populate default values for the policy from the
     * `ContentSecurityPolicy.php` file. Controllers can always add to those
     * restrictions at run time.
     *
     * For a better understanding of CSP, see these documents:
     *
     * @see http://www.html5rocks.com/en/tutorials/security/content-security-policy/
     * @see http://www.w3.org/TR/CSP/
     */
    public bool $CSPEnabled = false;

    public function __construct()
    {
        parent::__construct();

        if ($this->autoDetectBaseURL) {
            $detected = $this->detectBaseURL();

            if ($detected !== null) {
                $this->baseURL = $detected;
            }
        }
    }

    /**
     * Builds the base URL from the current request.
     * Returns null for CLI requests or invalid hosts, keeping $baseURL.
     */
    protected function detectBaseURL(): ?string
    {
        if (PHP_SAPI === 'cli' || ! isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $host = strtolower(trim((string) $_SERVER['HTTP_HOST']));

        // Only hostnames, IPv4/IPv6 and an optional port are accepted.
        if (preg_match('/^(?:[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|\[[0-9a-f:.]+\])\.?(?::\d{1,5})?$/i', $host) !== 1) {
            return null;
        }

        // The Host header is controlled by the client. Only trust it when the
        // hostname is explicitly allowed or when we are running locally.
        if (! $this->isTrustedHost($host)) {
            return null;
        }

        return $this->detectScheme() . '://' . $host . $this->detectBasePath();
    }

    /**
     * Comprueba que el host de la petición esté permitido.
     */
    protected function isTrustedHost(string $host): bool
    {
        $hostname = strtolower(preg_replace('/:\d{1,5}$/', '', $host) ?? $host);
        $hostname = trim($hostname, '[]');

        if (in_array($hostname, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        foreach ($this->allowedHostnames as $allowed) {
            if (strtolower(trim((string) $allowed)) === $hostname) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects the request scheme, including reverse proxy / tunnel headers.
     */
    protected function detectScheme(): string
    {
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

        if ($forwardedProto !== '') {
            // May contain a list: "https,http".
            $proto = strtolower(trim(explode(',', (string) $forwardedProto)[0]));

            if (in_array($proto, ['http', 'https'], true)) {
                return $proto;
            }
        }

        // Cloudflare sends {"scheme":"https"}.
        $cfVisitor = $_SERVER['HTTP_CF_VISITOR'] ?? '';

        if ($cfVisitor !== '') {
            $visitor = json_decode((string) $cfVisitor, true);

            if (is_array($visitor) && in_array($visitor['scheme'] ?? '', ['http', 'https'], true)) {
                return $visitor['scheme'];
            }
        }

        if (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return 'https';
        }

        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return 'https';
        }

        return 'http';
    }

    /**
     * Returns the sub-directory path where the front controller lives,
     * with leading and trailing slashes ("/" when served from the root).
     */
    protected function detectBasePath(): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        if (! str_ends_with($scriptName, '.php')) {
            return '/';
        }

        $directory = str_replace('\\', '/', dirname($scriptName));

        if ($directory === '.' || $directory === '' || $directory === '/') {
            return '/';
        }

        return '/' . trim($directory, '/') . '/';
    }
}
