<?php
/**
 *
 * @package XCube
 * @version $Id: XCube_Utils.class.php,v 1.5 2008/10/12 04:30:27 minahito Exp $
 * @copyright Copyright 2005-2007 XOOPS Cube Project  <https://github.com/xoopscube/legacy>
 * @license https://github.com/xoopscube/legacy/blob/master/docs/bsd_licenses.txt Modified BSD license
 *
 */

/**
 * @public
 * @brief The utility class collecting static helper functions.
 */
class XCube_Utils
{
    /**
     * @private
     * @brief Private Constructor. In other words, it's impossible to generate an instance of this class.
     */
    public function __construct()
    {
    }

    /**
     * @public
     * @brief [Static] The alias for the current controller::redirectHeader(). This function will be deprecated.
     * @param string $url
     * @param int    $time
     * @param null   $messages
     * @return void
     *
     * @deprecated XCube 1.0 will remove this method. Don't use static function of XCube
     *             layer for redirect.
     */
    public static function redirectHeader($url, $time, $messages = null)
    {
        $root =& XCube_Root::getSingleton();
        $root->mController->executeRedirect($url, $time, $messages);
    }

    /**
     * @public
     * @brief [Static] Formats string with special care for international.
     * @return string - Rendered string.
     *
     * This method renders string with modifiers and parameters. Parameters are .NET
     * style, not printf style. Because .NET style is clear.
     *
     * \code
     *   XCube_Utils::formatString("{0} is {1}", "Time", "Money");
     *   // Result "Time is Money"
     * \endcode
     *
     * It's possible to add a modifier to parameter place holders with ':' mark.
     *
     * \code
     *   XCube_Utils::formatString("{0:ucFirst} is {1:toLower}", "Time", "Money");
     *   // Result "Time is Money"
     * \endcode
     *
     * This feature is useful for automatic combined messages by message catalogs.
     *
     * \par Modifiers
     *   -li ufFirst - Upper the first charactor of the parameter's value.
     *   -li toUpper - Upper the parameter's value.
     *   -li toLower - Lower the parameter's value.
     *
     * @remarks
     *     This method doesn't implement the provider which knows how to format
     *     for each locales. So, this method is interim implement.
     */
    public static function formatString()
    {
        $arr = func_get_args();

        if (count($arr) === 0) {
            return null;
        }

        $message = $arr[0];

        $variables = [];
        if (is_array($arr[1])) {
            $variables = $arr[1];
        } else {
            $variables = $arr;
            array_shift($variables);
        }

        foreach ($variables as $i => $iValue) {
            // Temporary....
            $message = str_replace(array('{' . ($i) . '}', '{' . ($i) . ':ucFirst}', '{' . ($i) . ':toLower}', '{' . ($i) . ':toUpper}'), array($variables[$i], ucfirst($iValue), strtolower($iValue), strtoupper($iValue)), $message);
        }

        return $message;
    }

    /**
     * @public
     * @brief [Static] To encrypt strings by "DES-ECB".
     * @param string $plain_text
     * @param string $key
     * @return string - Encrypted string.
     */
    public static function encrypt($plain_text, $key = null)
    {
        if ($plain_text === '') {
            return $plain_text;
        }

        if (null === $key || ! is_string($key)) {
            if (! defined('XOOPS_SALT')) {
                return $plain_text;
            }
            $key = XOOPS_SALT;
        }
        $key = substr(md5($key), 0, 8);

        if (! function_exists('openssl_encrypt')) {
            if (! extension_loaded('mcrypt')) {
                return $plain_text;
            }
            $td  = mcrypt_module_open('des', '', 'ecb', '');

            if (mcrypt_generic_init($td, $key, 'iv_dummy') < 0) {
                return $plain_text;
            }

            $crypt_text = base64_encode(mcrypt_generic($td, $plain_text));

            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
        } else {
            $crypt_text = openssl_encrypt($plain_text, 'DES-ECB', $key);
        }

        return false === $crypt_text ? $plain_text : $crypt_text;
    }

    /**
     * @public
     * @brief [Static] To decrypt strings by "DES-ECB".
     * @param string $crypt_text
     * @param string $key
     * @return string - Decrypted string.
     */
    public static function decrypt($crypt_text, $key = null)
    {
        if ('' === $crypt_text) {
            return $crypt_text;
        }

        if (null === $key || ! is_string($key)) {
            if (! defined('XOOPS_SALT')) {
                return $crypt_text;
            }
            $key = XOOPS_SALT;
        }
        $key = substr(md5($key), 0, 8);

        // PHP < 5.4.0 can not use `OPENSSL_ZERO_PADDING`
        if (! function_exists('openssl_decrypt') || PHP_VERSION_ID < 50400) {
            if (! extension_loaded('mcrypt')) {
                return $crypt_text;
            }
            $td  = mcrypt_module_open('des', '', 'ecb', '');

            if (mcrypt_generic_init($td, $key, 'iv_dummy') < 0) {
                return $crypt_text;
            }

            $plain_text = mdecrypt_generic($td, base64_decode($crypt_text));

            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
        } else {
            $plain_text = openssl_decrypt($crypt_text, 'DES-ECB', $key, OPENSSL_ZERO_PADDING);
        }
        // remove \0 padding for mcrypt encrypted text
        $plain_text = rtrim($plain_text, "\0");
        // remove pkcs#7 padding for openssl encrypted text if padding string found
        $pad_ch = substr($plain_text, -1);
        $pad_len = ord($pad_ch);
        if (substr_compare($plain_text, str_repeat($pad_ch, $pad_len), -$pad_len) === 0) {
            $plain_text = substr($plain_text, 0, strlen($plain_text) - $pad_len);
        }

        return false === $plain_text ? $crypt_text : $plain_text;
    }

    /**
     * @deprecated XCube 1.0 will remove this method.
     * @see XCube_Utils::formatString()
     */
    public function formatMessage()
    {
        $arr = func_get_args();

        if (count($arr) === 0) {
            return null;
        }

        if (count($arr) === 1) {
            return self::formatString($arr[0]);
        }

        if (count($arr) > 1) {
            $vals = $arr;
            array_shift($vals);
            return self::formatString($arr[0], $vals);
        }
    }

    /**
     * @param $subject
     * @param $arr
     * @return string|string[]
     * @deprecated XCube 1.0 will remove this method.
     */
    public function formatMessageByMap($subject, $arr)
    {
        $searches= [];
        $replaces= [];
        foreach ($arr as $key=>$value) {
            $searches[]= '{' . $key . '}';
            $replaces[]=$value;
        }

        return str_replace($searches, $replaces, $subject);
    }
}
