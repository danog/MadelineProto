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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Http\Client\Cookie\InMemoryCookieJar;
use Amp\Http\Client\Request;

/**
 * Wrapper for my.telegram.org.
 */
class MyTelegramOrgWrapper
{
    /**
     * Whether we're logged in.
     */
    private bool $logged = false;
    /**
     * Login hash.
     */
    private string $hash = '';
    /**
     * Phone number.
     *
     * @var string
     */
    private string $number = '';
    /**
     * Creation hash.
     */
    private string $creation_hash = '';
    /**
     * Settings.
     *
     * @var Settings
     */
    private $settings;
    /**
     * Async setting.
     */
    private bool $async = true;
    /**
     * Datacenter instance.
     */
    private DataCenter $datacenter;
    /**
     * Cooke jar.
     *
     * @var InMemoryCookieJar
     */
    private $jar;
    /**
     * Endpoint.
     */
    private const MY_TELEGRAM_URL = 'https://my.telegram.org';
    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['logged', 'hash', 'number', 'creation_hash', 'settings', 'async', 'jar'];
    }
    /**
     * Constructor.
     *
     * @param array|Settings $settings
     */
    public function __construct($settings)
    {
        $this->settings = Settings::parseFromLegacy($settings);
        if (!$this->settings instanceof Settings) {
            $settings = new Settings;
            $settings->merge($this->settings);
            $this->settings = $settings;
        }
        $this->__wakeup();
    }
    /**
     * Wakeup function.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        if (!$this->settings) {
            $this->settings = new Settings;
        } elseif (\is_array($this->settings)) {
            $this->settings = Settings::parseFromLegacy($this->settings);
            if (!$this->settings instanceof Settings) {
                $settings = new Settings;
                $settings->merge($this->settings);
                $this->settings = $settings;
            }
        }
        if (!$this->jar || !$this->jar instanceof InMemoryCookieJar) {
            $this->jar = new InMemoryCookieJar();
        }
        $this->datacenter = new DataCenter(new class(new Logger($this->settings->getLogger())) {
            public Logger $logger;
            public function __construct(Logger $logger)
            {
                $this->logger = $logger;
            }
            public function getLogger(): Logger
            {
                return $this->logger;
            }
        }, [], $this->settings->getConnection(), true, $this->jar);
    }
    /**
     * Login.
     *
     * @param string $number Phone number
     *
     * @return \Generator
     */
    public function login(string $number): \Generator
    {
        $this->number = $number;
        $request = new Request(self::MY_TELEGRAM_URL.'/auth/send_password', 'POST');
        $request->setBody(\http_build_query(['phone' => $number]));
        $request->setHeaders($this->getHeaders('origin'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody()->buffer();
        $resulta = \json_decode($result, true);
        if (!isset($resulta['random_hash'])) {
            throw new Exception($result);
        }
        $this->hash = $resulta['random_hash'];
    }
    /**
     * Complete login.
     *
     * @param string $password Password
     *
     * @return \Generator
     */
    public function completeLogin(string $password): \Generator
    {
        if ($this->logged) {
            throw new Exception('Already logged in!');
        }
        $request = new Request(self::MY_TELEGRAM_URL.'/auth/login', 'POST');
        $request->setBody(\http_build_query(['phone' => $this->number, 'random_hash' => $this->hash, 'password' => $password]));
        $request->setHeaders($this->getHeaders('origin'));
        $request->setHeader('user-agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody()->buffer();
        switch ($result) {
            case 'true':
                //Logger::log(['Login OK'], Logger::VERBOSE);
                break;
            default:
                throw new Exception($result);
        }
        return $this->logged = true;
    }
    /**
     * Whether we are logged in.
     *
     * @return boolean
     */
    public function loggedIn(): bool
    {
        return $this->logged;
    }
    /**
     * Check if an app was already created.
     *
     * @return \Generator
     */
    public function hasApp(): \Generator
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        $request = new Request(self::MY_TELEGRAM_URL.'/apps');
        $request->setHeaders($this->getHeaders('refer'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody()->buffer();
        $title = \explode('</title>', \explode('<title>', $result)[1])[0];
        switch ($title) {
            case 'App configuration':
                return true;
            case 'Create new application':
                $this->creation_hash = \explode('"/>', \explode('<input type="hidden" name="hash" value="', $result)[1])[0];
                return false;
        }
        $this->logged = false;
        throw new Exception($title);
    }
    /**
     * Get the currently created app.
     *
     * @return \Generator
     */
    public function getApp(): \Generator
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        $request = new Request(self::MY_TELEGRAM_URL.'/apps');
        $request->setHeaders($this->getHeaders('refer'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody()->buffer();
        $cose = \explode('<label for="app_id" class="col-md-4 text-right control-label">App api_id:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();"><strong>', $result);
        $asd = \explode('</strong></span>', $cose[1]);
        $api_id = $asd[0];
        $cose = \explode('<label for="app_hash" class="col-md-4 text-right control-label">App api_hash:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();">', $result);
        $asd = \explode('</span>', $cose[1]);
        $api_hash = $asd[0];
        return ['api_id' => (int) $api_id, 'api_hash' => $api_hash];
    }
    /**
     * Create an app.
     *
     * @param array $settings App parameters
     *
     * @return \Generator
     */
    public function createApp(array $settings): \Generator
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        if (yield from $this->hasApp()) {
            throw new Exception('The app was already created!');
        }
        $request = new Request(self::MY_TELEGRAM_URL.'/apps/create', 'POST');
        $request->setHeaders($this->getHeaders('app'));
        $request->setBody(\http_build_query(['hash' => $this->creation_hash, 'app_title' => $settings['app_title'], 'app_shortname' => $settings['app_shortname'], 'app_url' => $settings['app_url'], 'app_platform' => $settings['app_platform'], 'app_desc' => $settings['app_desc']]));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody()->buffer();
        if ($result) {
            throw new Exception(\html_entity_decode($result));
        }
        $request = new Request(self::MY_TELEGRAM_URL.'/apps');
        $request->setHeaders($this->getHeaders('refer'));
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        $result = yield $response->getBody()->buffer();
        $title = \explode('</title>', \explode('<title>', $result)[1])[0];
        if ($title === 'Create new application') {
            $this->creation_hash = \explode('"/>', \explode('<input type="hidden" name="hash" value="', $result)[1])[0];
            throw new \danog\MadelineProto\Exception('App creation failed');
        }
        $cose = \explode('<label for="app_id" class="col-md-4 text-right control-label">App api_id:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();"><strong>', $result);
        $asd = \explode('</strong></span>', $cose['1']);
        $api_id = $asd['0'];
        $cose = \explode('<label for="app_hash" class="col-md-4 text-right control-label">App api_hash:</label>
      <div class="col-md-7">
        <span class="form-control input-xlarge uneditable-input" onclick="this.select();">', $result);
        $asd = \explode('</span>', $cose['1']);
        $api_hash = $asd['0'];
        return ['api_id' => (int) $api_id, 'api_hash' => $api_hash];
    }
    /**
     * Function for generating curl request headers.
     *
     * @param string $httpType Origin
     *
     * @return array
     */
    private function getHeaders(string $httpType): array
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
            list($key, $value) = \explode(':', $header, 2);
            $final_headers[\trim($key)] = \trim($value);
        }
        return $final_headers;
    }
    /**
     * Enable or disable async.
     *
     * @param boolean $async Async
     *
     * @return void
     */
    public function async(bool $async): void
    {
        $this->async = $async;
    }
    /**
     * Run specified callable synchronously.
     *
     * @param callable $callable Callable
     *
     * @return mixed
     */
    public function loop(callable $callable)
    {
        return Tools::wait($callable());
    }
    /**
     * Call function.
     *
     * @param string $name      Function name
     * @param array  $arguments Arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $name .= '_async';
        $async = \is_array(\end($arguments)) && isset(\end($arguments)['async']) ? \end($arguments)['async'] : $this->async;
        if (!\method_exists($this, $name)) {
            throw new Exception("{$name} does not exist!");
        }
        return $async ? $this->{$name}(...$arguments) : Tools::wait($this->{$name}(...$arguments));
    }
}
