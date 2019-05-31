<?php
/**
 * MyTelegramOrgWrapper module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Artax\Request;

/**
 * Wrapper for my.telegram.org.
 */
class MyTelegramOrgWrapper
{
    use Tools;

    private $logged = false;
    private $hash = '';
    private $token;
    private $number;
    private $creation_hash;
    private $settings;
    public $async = false;
    const MY_TELEGRAM_URL = 'https://my.telegram.org';

    public function __sleep()
    {
        return ['logged', 'hash', 'token', 'number', 'creation_hash', 'settings'];
    }
    public function __construct($settings)
    {
        if (!isset($settings['all'])) {
            $settings['connection_settings'] = ['all' => [
                // These settings will be applied on every datacenter that hasn't a custom settings subarray...
                'protocol' => Magic::$altervista ? 'http' : 'tcp_abridged',
                // can be tcp_full, tcp_abridged, tcp_intermediate, http, https, obfuscated2, udp (unsupported)
                'test_mode' => false,
                // decides whether to connect to the main telegram servers or to the testing servers (deep telegram)
                'ipv6' => \danog\MadelineProto\Magic::$ipv6,
                // decides whether to use ipv6, ipv6 attribute of API attribute of API class contains autodetected boolean
                'timeout' => 2,
                // timeout for sockets
                'proxy' => Magic::$altervista ? '\\HttpProxy' : '\\Socket',
                // The proxy class to use
                'proxy_extra' => Magic::$altervista ? ['address' => 'localhost', 'port' => 80] : [],
                // Extra parameters to pass to the proxy class using setExtra
                'obfuscated' => false,
                'transport' => 'tcp',
                'pfs' => extension_loaded('gmp'),
            ],
            ];
        }
        $this->settings = $settings;
        $this->__wakeup();
    }
    public function __wakeup()
    {
        $this->datacenter = new DataCenter(
            new class($this->settings)
        {
                public function __construct($settings)
            {
                    $this->logger = new Logger(
                        isset($settings['logger']['logger']) ? $settings['logger']['logger'] : php_sapi_name() === 'cli' ? 3 : 2,
                        isset($settings['logger']['logger_param']) ? $settings['logger']['logger_param'] : Magic::$script_cwd.'/MadelineProto.log',
                        isset($settings['logger']['logger_level']) ? $settings['logger']['logger_level'] : Logger::VERBOSE,
                        isset($settings['logger']['max_size']) ? $settings['logger']['max_size'] : 100 * 1024 * 1024);
                }
            },
            [],
            $this->settings['connection_settings']
        );
    }
    public function login_async($number)
    {
        $this->number = $number;
        $request = new Request(self::MY_TELEGRAM_URL.'/auth/send_password', 'POST');
        $request = $request->withBody(http_build_query(['phone' => $number]));
        $request = $request->withHeaders($this->getHeaders('origin'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody();
        $resulta = json_decode($result, true);

        if (!isset($resulta['random_hash'])) {
            throw new Exception($result);
        }
        $this->hash = $resulta['random_hash'];
    }

    public function complete_login_async($password)
    {
        if ($this->logged) {
            throw new Exception('Already logged in!');
        }

        $request = new Request(self::MY_TELEGRAM_URL.'/auth/login', 'POST');
        $request = $request->withBody(http_build_query(['phone' => $this->number, 'random_hash' => $this->hash, 'password' => $password]));
        $request = $request->withHeaders($this->getHeaders('origin'));
        $request = $request->withHeader('user-agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody();

        switch ($result) {
            case 'true':
                //Logger::log(['Login OK'], Logger::VERBOSE);
                break;
            default:
                throw new Exception($result);
        }

        $this->token = explode(';', explode('stel_token=', $response->getHeader('Set-Cookie'))[1])[0];

        return $this->logged = true;
    }

    public function logged_in()
    {
        return $this->logged;
    }

    public function has_app_async()
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }

        $request = new Request(self::MY_TELEGRAM_URL.'/apps');
        $request = $request->withHeaders($this->getHeaders('refer'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody();

        $title = explode('</title>', explode('<title>', $result)[1])[0];
        switch ($title) {
            case 'App configuration':return true;
            case 'Create new application':
                $this->creation_hash = explode('"/>', explode('<input type="hidden" name="hash" value="', $result)[1])[0];

                return false;
        }

        throw new Exception($title);
    }

    public function get_app_async()
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }

        $request = new Request(self::MY_TELEGRAM_URL.'/apps');
        $request = $request->withHeaders($this->getHeaders('refer'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody();

        $cose = explode('<label for="app_id" class="col-md-4 text-right control-label">App api_id:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();"><strong>', $result);
        $asd = explode('</strong></span>', $cose[1]);
        $api_id = $asd[0];
        $cose = explode('<label for="app_hash" class="col-md-4 text-right control-label">App api_hash:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();">', $result);
        $asd = explode('</span>', $cose[1]);
        $api_hash = $asd[0];

        return ['api_id' => (int) $api_id, 'api_hash' => $api_hash];
    }

    public function create_app_async($settings)
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        if (yield $this->has_app_async()) {
            throw new Exception('The app was already created!');
        }

        $request = new Request(self::MY_TELEGRAM_URL.'/apps/create', 'POST');
        $request = $request->withHeaders($this->getHeaders('app'));
        $request = $request->withBody(http_build_query(['hash' => $this->creation_hash, 'app_title' => $settings['app_title'], 'app_shortname' => $settings['app_shortname'], 'app_url' => $settings['app_url'], 'app_platform' => $settings['app_platform'], 'app_desc' => $settings['app_desc']]));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody();

        if ($result) {
            throw new Exception($result);
        }

        $request = new Request(self::MY_TELEGRAM_URL.'/apps');
        $request = $request->withHeaders($this->getHeaders('refer'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody();

        $title = explode('</title>', explode('<title>', $result)[1])[0];
        if ($title === 'Create new application') {
            $this->creation_hash = explode('"/>', explode('<input type="hidden" name="hash" value="', $result)[1])[0];

            throw new \danog\MadelineProto\Exception('App creation failed');
        }

        $cose = explode('<label for="app_id" class="col-md-4 text-right control-label">App api_id:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();"><strong>', $result);
        $asd = explode('</strong></span>', $cose['1']);
        $api_id = $asd['0'];
        $cose = explode('<label for="app_hash" class="col-md-4 text-right control-label">App api_hash:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();">', $result);
        $asd = explode('</span>', $cose['1']);
        $api_hash = $asd['0'];

        return ['api_id' => (int) $api_id, 'api_hash' => $api_hash];
    }

    /**
     * Function for generating curl request headers.
     */
    private function getHeaders($httpType)
    {
        // Common header flags.
        $headers = [];
        $headers[] = 'Dnt: 1';
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';

        // Add additional headers based on the type of request.
        switch ($httpType) {
            case 'origin':
                $headers[] = 'Origin: '.self::MY_TELEGRAM_URL;
                //$headers[] = 'Accept-Encoding: gzip, deflate, br';
                $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
                $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';
                $headers[] = 'Referer: '.self::MY_TELEGRAM_URL.'/auth';
                $headers[] = 'X-Requested-With: XMLHttpRequest';
                break;
            case 'refer':
                //$headers[] = 'Accept-Encoding: gzip, deflate, sdch, br';
                $headers[] = 'Upgrade-Insecure-Requests: 1';
                $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
                $headers[] = 'Referer: '.self::MY_TELEGRAM_URL;
                $headers[] = 'Cache-Control: max-age=0';
                break;
            case 'app':
                $headers[] = 'Origin: '.self::MY_TELEGRAM_URL;
                //$headers[] = 'Accept-Encoding: gzip, deflate, br';
                $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
                $headers[] = 'Accept: */*';
                $headers[] = 'Referer: '.self::MY_TELEGRAM_URL.'/apps';
                $headers[] = 'X-Requested-With: XMLHttpRequest';
                break;
        }

        $final_headers = [];
        foreach ($headers as $header) {
            list($key, $value) = explode(':', $header, 2);
            $final_headers[trim($key)] = trim($value);
        }

        return $final_headers;
    }
    public function async($async)
    {
        $this->async = $async;
    }
    public function __call($name, $arguments)
    {
        $name .= '_async';
        $async = is_array(end($arguments)) && isset(end($arguments)['async']) ? end($arguments)['async'] : $this->async;
        return $async ? $this->{$name}(...$arguments) : $this->wait($this->{$name}(...$arguments));
    }
}
