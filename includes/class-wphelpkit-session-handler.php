<?php
/**
 * Handle data for the current session.
 *
 * Based on `WC_Session_Handler` in @link https://wordpress.org/plugins/woocommerce/
 * but highly simplified.
 *
 * As of 0.1.1, only used for setting the "primary category article link" transient,
 * but may be used for other things in the future.
 *
 * @since 0.1.1
 */

defined('ABSPATH') || die;

/**
 * Session handler class.
 *
 * @since 0.1.1
 */
class WPHelpKit_Session_Handler
{
    /**
     * Our static instance.
     *
     * @since 0.1.1
     *
     * @var WPHelpKit_Session_Handler
     */
    private static $instance;

    /**
     * Our cookie prefix.
     *
     * The actual cookie with be this prefix with a runtime hash appended.
     *
     * @since 0.1.1
     *
     * @var string
     */
    public static $cookie_prefix = 'wphelpkit_session_';

    /**
     * Cookie name used for the session.
     *
     * @var string cookie name
     */
    protected $_cookie;

    /**
     * User ID for this session.
     *
     * Will be the empty string if the user is not logged in.
     *
     * @since 0.1.1
     *
     * @var int
     */
    protected $_user_id;

    /**
     * Stores session expiry.
     *
     * @var string session due to expire timestamp
     */
    protected $_session_expiring;

    /**
     * Stores session due to expire timestamp.
     *
     * @var string session expiration timestamp
     */
    protected $_session_expiration;

    /**
     * True when the cookie exists.
     *
     * @var bool Based on whether a cookie exists.
     */
    protected $_has_cookie = false;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.1.1
     *
     * @return WPHelpKit_Session_Handler
     */
    public static function get_instance()
    {
        if (! self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * Initialize our static instance and add hooks.
     *
     * @since 0.1.1
     */
    public function __construct()
    {
        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = $this;

        $this->_cookie = self::$cookie_prefix . COOKIEHASH;

        $this->init();
    }

    /**
     * Init hooks and session data.
     *
     * @since 0.1.1
     *
     * @return void
     */
    public function init()
    {
        $cookie = $this->get_session_cookie();

        if ($cookie) {
            $this->_user_id            = $cookie[0];
            $this->_session_expiration = $cookie[1];
            $this->_session_expiring   = $cookie[2];
            $this->_has_cookie         = true;

            // Update session if its close to expiring.
            if (time() > $this->_session_expiring) {
                $this->set_session_expiration();
            }
        } else {
            $this->set_session_expiration();
            $this->_user_id = $this->get_user_id();

            $this->set_session_cookie(true);
        }

        return;
    }

    /**
     * Sets the session cookie on-demand.
     *
     * Warning: Cookies will only be set if this is called before the headers are sent.
     *
     * @since 0.1.1
     *
     * @return void
     */
    public function set_session_cookie()
    {
        $to_hash           = sprintf('%s|%s', $this->_user_id, $this->_session_expiration);
        $cookie_hash       = hash_hmac('md5', $to_hash, wp_hash($to_hash));
        $cookie_value      = sprintf(
            '%s||%s||%s||%s||',
            $this->_user_id,
            $this->_session_expiration,
            $this->_session_expiring,
            $cookie_hash
        );
        $this->_has_cookie = true;

        $this->setcookie($this->_cookie, $cookie_value, $this->_session_expiration);

        return;
    }

    /**
     * Set a cookie - wrapper for setcookie using WP constants.
     *
     * @param  string  $name   Name of the cookie being set.
     * @param  string  $value  Value of the cookie.
     * @param  integer $expire Expiry of the cookie.
     * @param  bool    $secure Whether the cookie should be served only over https.
     * @return void
     */
    public function setcookie($name, $value, $expire = 0, $secure = false, $httponly = false)
    {
        if (! headers_sent()) {
            setcookie($name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, $httponly);
        } elseif (defined('WP_DEBUG') && WP_DEBUG) {
            headers_sent($file, $line);
			trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
        }

        return;
    }

    /**
     * Set session expiration.
     *
     * @since 0.1.1
     *
     * @return void
     */
    public function set_session_expiration()
    {
        /**
         * Filters the session expiring time.
         *
         * @since 0.1.1
         *
         * @param int $expiring The number of seconds ???.
         */
        $expiring = apply_filters('wphelpkit-session-expiring', 47 * HOUR_IN_SECONDS);
        $this->_session_expiring   = time() + intval($expiring);

        /**
         * Filters the session expiration time.
         *
         * @since 0.1.1
         *
         * @param int $expiration The number of seconds ???.
         */
        $expiration = apply_filters('wphelpkit-session-expiration', 48 * HOUR_IN_SECONDS);
        $this->_session_expiration = time() + intval($expiration);

        return;
    }

    /**
     * Generate a unique customer ID for guests, or return user ID if logged in.
     *
     * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
     *
     * @since 0.1.1
     *
     * @return int|string
     */
    public function get_user_id()
    {
        $user_id = '';

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        }

        if (empty($user_id)) {
            require_once ABSPATH . 'wp-includes/class-phpass.php';

            $hasher  = new PasswordHash(8, false);
            $user_id = md5($hasher->get_random_bytes(32));
        }

        return $user_id;
    }

    /**
     * Get the session cookie, if set. Otherwise return false.
     *
     * Session cookies without a user ID are invalid.
     *
     * @since 0.1.1
     *
     * @return bool|array
     */
    public function get_session_cookie()
    {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? sanitize_text_field(wp_unslash($_COOKIE[ $this->_cookie ])) : false; // @codingStandardsIgnoreLine.

        if (empty($cookie_value) || ! is_string($cookie_value)) {
            return false;
        }

        list( $user_id, $session_expiration, $session_expiring, $cookie_hash ) = explode('||', $cookie_value);

        if (empty($user_id)) {
            return false;
        }

        // Validate hash.
        $to_hash = $user_id . '|' . $session_expiration;
        $hash    = hash_hmac('md5', $to_hash, wp_hash($to_hash));

        if (empty($cookie_hash) || ! hash_equals($hash, $cookie_hash)) {
            return false;
        }

        return array( $user_id, $session_expiration, $session_expiring, $cookie_hash );
    }
}
