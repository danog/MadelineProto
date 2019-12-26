<?php

/**
 * Random_* Compatibility Library
 * for using the new PHP 7 random_* API in PHP 5 projects.
 *
 * @version 2.0.17
 * @released 2018-07-04
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 - 2018 Paragon Initiative Enterprises
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

if (!\defined('PHP_VERSION_ID')) {
    // This constant was introduced in PHP 5.2.7
    $RandomCompatversion = \array_map('intval', \explode('.', PHP_VERSION));
    \define('PHP_VERSION_ID', $RandomCompatversion[0] * 10000 + $RandomCompatversion[1] * 100 + $RandomCompatversion[2]);
    $RandomCompatversion = null;
}
/**
 * PHP 7.0.0 and newer have these functions natively.
 */
if (PHP_VERSION_ID >= 70000) {
    return;
}
if (!\defined('RANDOM_COMPAT_READ_BUFFER')) {
    \define('RANDOM_COMPAT_READ_BUFFER', 8);
}
$RandomCompatDIR = \dirname(__FILE__);
require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'byte_safe_strings.php';
require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'cast_to_int.php';
require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'error_polyfill.php';
if (!\is_callable('random_bytes')) {
    /**
     * PHP 5.2.0 - 5.6.x way to implement random_bytes().
     *
     * We use conditional statements here to define the function in accordance
     * to the operating environment. It's a micro-optimization.
     *
     * In order of preference:
     *   1. Use libsodium if available.
     *   2. fread() /dev/urandom if available (never on Windows)
     *   3. mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM)
     *   4. COM('CAPICOM.Utilities.1')->GetRandom()
     *
     * See RATIONALE.md for our reasoning behind this particular order
     */
    if (\extension_loaded('libsodium')) {
        // See random_bytes_libsodium.php
        if (PHP_VERSION_ID >= 50300 && \is_callable('\\Sodium\\randombytes_buf')) {
            require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_libsodium.php';
        } elseif (\method_exists('Sodium', 'randombytes_buf')) {
            require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_libsodium_legacy.php';
        }
    }
    /**
     * Reading directly from /dev/urandom:.
     */
    if (DIRECTORY_SEPARATOR === '/') {
        // DIRECTORY_SEPARATOR === '/' on Unix-like OSes -- this is a fast
        // way to exclude Windows.
        $RandomCompatUrandom = true;
        $RandomCompat_basedir = \ini_get('open_basedir');
        if (!empty($RandomCompat_basedir)) {
            $RandomCompat_open_basedir = \explode(PATH_SEPARATOR, \strtolower($RandomCompat_basedir));
            $RandomCompatUrandom = [] !== \array_intersect(['/dev', '/dev/', '/dev/urandom'], $RandomCompat_open_basedir);
            $RandomCompat_open_basedir = null;
        }
        if (!\is_callable('random_bytes') && $RandomCompatUrandom && @\is_readable('/dev/urandom')) {
            // Error suppression on is_readable() in case of an open_basedir
            // or safe_mode failure. All we care about is whether or not we
            // can read it at this point. If the PHP environment is going to
            // panic over trying to see if the file can be read in the first
            // place, that is not helpful to us here.
            // See random_bytes_dev_urandom.php
            require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_dev_urandom.php';
        }
        // Unset variables after use
        $RandomCompat_basedir = null;
    } else {
        $RandomCompatUrandom = false;
    }
    /**
     * mcrypt_create_iv().
     *
     * We only want to use mcypt_create_iv() if:
     *
     * - random_bytes() hasn't already been defined
     * - the mcrypt extensions is loaded
     * - One of these two conditions is true:
     *   - We're on Windows (DIRECTORY_SEPARATOR !== '/')
     *   - We're not on Windows and /dev/urandom is readabale
     *     (i.e. we're not in a chroot jail)
     * - Special case:
     *   - If we're not on Windows, but the PHP version is between
     *     5.6.10 and 5.6.12, we don't want to use mcrypt. It will
     *     hang indefinitely. This is bad.
     *   - If we're on Windows, we want to use PHP >= 5.3.7 or else
     *     we get insufficient entropy errors.
     */
    if (!\is_callable('random_bytes') && (DIRECTORY_SEPARATOR === '/' || PHP_VERSION_ID >= 50307) && (DIRECTORY_SEPARATOR !== '/' || (PHP_VERSION_ID <= 50609 || PHP_VERSION_ID >= 50613)) && \extension_loaded('mcrypt')) {
        // See random_bytes_mcrypt.php
        require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_mcrypt.php';
    }
    $RandomCompatUrandom = null;
    /**
     * This is a Windows-specific fallback, for when the mcrypt extension
     * isn't loaded.
     */
    if (!\is_callable('random_bytes') && \extension_loaded('com_dotnet') && \class_exists('COM')) {
        $RandomCompat_disabled_classes = \preg_split('#\\s*,\\s*#', \strtolower(\ini_get('disable_classes')));
        if (!\in_array('com', $RandomCompat_disabled_classes)) {
            try {
                $RandomCompatCOMtest = new COM('CAPICOM.Utilities.1');
                if (\method_exists($RandomCompatCOMtest, 'GetRandom')) {
                    // See random_bytes_com_dotnet.php
                    require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_bytes_com_dotnet.php';
                }
            } catch (com_exception $e) {
                // Don't try to use it.
            }
        }
        $RandomCompat_disabled_classes = null;
        $RandomCompatCOMtest = null;
    }
    /**
     * throw new Exception.
     */
    if (!\is_callable('random_bytes')) {
        /**
         * Safely serialize variables.
         *
         * If a class has a private __sleep() it'll emit a warning
         * @return mixed
         * @param mixed $arr
         */
        function safe_serialize(&$arr)
        {
            if (\is_object($arr)) {
                return '';
            }
            if (!\is_array($arr)) {
                return \serialize($arr);
            }
            // prevent circular array recursion
            if (isset($arr['__tgseclib_marker'])) {
                return '';
            }
            $safearr = [];
            $arr['__tgseclib_marker'] = true;
            foreach (\array_keys($arr) as $key) {
                // do not recurse on the '__tgseclib_marker' key itself, for smaller memory usage
                if ($key !== '__tgseclib_marker') {
                    $safearr[$key] = safe_serialize($arr[$key]);
                }
            }
            unset($arr['__tgseclib_marker']);
            return \serialize($safearr);
        }
        /**
         * We don't have any more options, so let's throw an exception right now
         * and hope the developer won't let it fail silently.
         *
         * @param mixed $length
         * @psalm-suppress InvalidReturnType
         * @throws Exception
         * @return string
         */
        function random_bytes($length)
        {
            static $crypto = false, $v;
            if ($crypto === false) {
                // save old session data
                $old_session_id = \session_id();
                $old_use_cookies = \ini_get('session.use_cookies');
                $old_session_cache_limiter = \session_cache_limiter();
                $_OLD_SESSION = isset($_SESSION) ? $_SESSION : false;
                if ($old_session_id != '') {
                    \session_write_close();
                }
                \session_id(1);
                \ini_set('session.use_cookies', 0);
                \session_cache_limiter('');
                \session_start();
                $v = (isset($_SERVER) ? safe_serialize($_SERVER) : '') . (isset($_POST) ? safe_serialize($_POST) : '') . (isset($_GET) ? safe_serialize($_GET) : '') . (isset($_COOKIE) ? safe_serialize($_COOKIE) : '') . safe_serialize($GLOBALS) . safe_serialize($_SESSION) . safe_serialize($_OLD_SESSION);
                $v = $seed = $_SESSION['seed'] = \sha1($v, true);
                if (!isset($_SESSION['count'])) {
                    $_SESSION['count'] = 0;
                }
                $_SESSION['count']++;
                \session_write_close();
                // restore old session data
                if ($old_session_id != '') {
                    \session_id($old_session_id);
                    \session_start();
                    \ini_set('session.use_cookies', $old_use_cookies);
                    \session_cache_limiter($old_session_cache_limiter);
                } else {
                    if ($_OLD_SESSION !== false) {
                        $_SESSION = $_OLD_SESSION;
                        unset($_OLD_SESSION);
                    } else {
                        unset($_SESSION);
                    }
                }
                // in SSH2 a shared secret and an exchange hash are generated through the key exchange process.
                // the IV client to server is the hash of that "nonce" with the letter A and for the encryption key it's the letter C.
                // if the hash doesn't produce enough a key or an IV that's long enough concat successive hashes of the
                // original hash and the current hash. we'll be emulating that. for more info see the following URL:
                //
                // http://tools.ietf.org/html/rfc4253#section-7.2
                //
                // see the is_string($crypto) part for an example of how to expand the keys
                $key = \sha1($seed . 'A', true);
                $iv = \sha1($seed . 'C', true);
                // ciphers are used as per the nist.gov link below. also, see this link:
                //
                // http://en.wikipedia.org/wiki/Cryptographically_secure_pseudorandom_number_generator#Designs_based_on_cryptographic_primitives
                switch (true) {
                case \class_exists('\\tgseclib\\Crypt\\AES'):
                    $crypto = new \tgseclib\Crypt\AES('ctr');
                    break;
                case \class_exists('\\tgseclib\\Crypt\\Twofish'):
                    $crypto = new \tgseclib\Crypt\Twofish('ctr');
                    break;
                case \class_exists('\\tgseclib\\Crypt\\Blowfish'):
                    $crypto = new \tgseclib\Crypt\Blowfish('ctr');
                    break;
                case \class_exists('\\tgseclib\\Crypt\\TripleDES'):
                    $crypto = new \tgseclib\Crypt\TripleDES('ctr');
                    break;
                case \class_exists('\\tgseclib\\Crypt\\DES'):
                    $crypto = new \tgseclib\Crypt\DES('ctr');
                    break;
                case \class_exists('\\tgseclib\\Crypt\\RC4'):
                    $crypto = new \tgseclib\Crypt\RC4();
                    break;
                default:
                    throw new \RuntimeException(__CLASS__ . ' requires at least one symmetric cipher be loaded');
            }
                $crypto->setKey(\substr($key, 0, $crypto->getKeyLength() >> 3));
                $crypto->setIV(\substr($iv, 0, $crypto->getBlockLength() >> 3));
                $crypto->enableContinuousBuffer();
            }
            //return $crypto->encrypt(str_repeat("\0", $length));
            // the following is based off of ANSI X9.31:
            //
            // http://csrc.nist.gov/groups/STM/cavp/documents/rng/931rngext.pdf
            //
            // OpenSSL uses that same standard for it's random numbers:
            //
            // http://www.opensource.apple.com/source/OpenSSL/OpenSSL-38/openssl/fips-1.0/rand/fips_rand.c
            // (do a search for "ANS X9.31 A.2.4")
            $result = '';
            while (\strlen($result) < $length) {
                $i = $crypto->encrypt(\microtime());
                // strlen(microtime()) == 21
                $r = $crypto->encrypt($i ^ $v);
                // strlen($v) == 20
                $v = $crypto->encrypt($r ^ $i);
                // strlen($r) == 20
                $result .= $r;
            }
            return \substr($result, 0, $length);
        }
    }
}
if (!\is_callable('random_int')) {
    require_once $RandomCompatDIR . DIRECTORY_SEPARATOR . 'random_int.php';
}
$RandomCompatDIR = null;
