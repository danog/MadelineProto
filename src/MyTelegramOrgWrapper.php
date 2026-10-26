<?php

declare(strict_types=1);

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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Http\Client\Cookie\LocalCookieJar;
use Amp\Http\Client\Request;

/**
 * Wrapper for my.telegram.org.
 */
final class MyTelegramOrgWrapper
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
     */
    private string $number = '';
    /**
     * Creation hash.
     */
    private string $creation_hash = '';
    /**
     * Settings.
     *
     */
    private Settings $settings;
    /**
     * Datacenter instance.
     */
    private DoHWrapper $datacenter;
    /**
     * Cooke jar.
     *
     */
    private ?LocalCookieJar $jar = null;
    /**
     * Endpoint.
     */
    private const MY_TELEGRAM_URL = 'https://my.telegram.org';
    /**
     * Sleep function.
     *
     * @psalm-pure
     */
    public function __sleep(): array
    {
        return ['logged', 'hash', 'number', 'creation_hash', 'settings', 'jar'];
    }
    /**
     * Constructor.
     */
    public function __construct(SettingsAbstract $settings)
    {
        if (!$settings instanceof Settings) {
            $newSettings = new Settings;
            $newSettings->merge($settings);
            $settings = $newSettings;
        }
        $this->settings = $settings;
        $this->__wakeup();
    }
    /**
     * Wakeup function.
     */
    public function __wakeup(): void
    {
        $this->jar ??= new LocalCookieJar();
        $this->datacenter = new DoHWrapper(
            new class($this->settings) implements
                SettingsGetter,
                LoggerGetter {
                /**
                 * @psalm-mutation-free
                 */
                public function __construct(
                    private readonly Settings $settings
                ) {
                }
                #[\Override]
                public function getSettings(): Settings
                {
                    return $this->settings;
                }
                #[\Override]
                public function getLogger(): Logger
                {
                    return Logger::$default;
                }
            },
            $this->jar
        );
    }
    /**
     * Get the phone number used to log in.
     */
    public function getPhoneNumber(): string
    {
        return $this->number;
    }
    /**
     * Login: my.telegram.org sends a login code to the Telegram app of the account.
     *
     * @param string $number Phone number
     */
    public function login(string $number): void
    {
        $this->logged = false;
        $this->number = $number;
        $result = $this->request('/auth/send_password', 'origin', ['phone' => $number]);
        $resulta = json_decode($result, true);
        if (!\is_array($resulta) || !isset($resulta['random_hash'])) {
            throw new Exception(self::getError($result));
        }
        $this->hash = $resulta['random_hash'];
    }
    /**
     * Complete login.
     *
     * @param string $password The login code sent to the Telegram app
     */
    public function completeLogin(#[\SensitiveParameter] string $password): bool
    {
        if ($this->logged) {
            throw new Exception('Already logged in!');
        }
        if ($this->hash === '') {
            throw new Exception('Call login() first!');
        }
        $result = $this->request('/auth/login', 'origin', ['phone' => $this->number, 'random_hash' => $this->hash, 'password' => $password]);
        if ($result !== 'true') {
            throw new Exception(self::getError($result));
        }
        return $this->logged = true;
    }
    /**
     * Whether we are logged in.
     */
    public function loggedIn(): bool
    {
        return $this->logged;
    }
    /**
     * Check if an app was already created.
     */
    public function hasApp(): bool
    {
        return $this->parseApps($this->getAppsPage()) !== null;
    }
    /**
     * Get the currently created app.
     *
     * @return array{api_id: int, api_hash: string}
     */
    public function getApp(): array
    {
        return $this->parseApps($this->getAppsPage()) ?? throw new Exception('No app was created yet!');
    }
    /**
     * Create an app.
     *
     * Telegram does not allow creating apps from VPS, VPN and other datacenter IP ranges: the request is made
     * from the IP of the machine running MadelineProto.
     *
     * @param array{app_title: string, app_shortname: string, app_url?: string, app_platform?: string, app_desc?: string} $settings App parameters
     *
     * @return array{api_id: int, api_hash: string}
     */
    public function createApp(array $settings): array
    {
        if ($this->parseApps($this->getAppsPage()) !== null) {
            throw new Exception('The app was already created!');
        }
        $result = $this->request('/apps/create', 'app', [
            'hash' => $this->creation_hash,
            'app_title' => $settings['app_title'],
            'app_shortname' => $settings['app_shortname'],
            'app_url' => $settings['app_url'] ?? '',
            'app_platform' => $settings['app_platform'] ?? 'other',
            'app_desc' => $settings['app_desc'] ?? '',
        ]);
        if (trim($result) !== '') {
            throw new Exception(self::getError($result));
        }
        return $this->parseApps($this->getAppsPage()) ?? throw new Exception('App creation failed');
    }
    /**
     * Get the apps page.
     */
    private function getAppsPage(): string
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        return $this->request('/apps', 'refer');
    }
    /**
     * Parse the apps page: get the app, or store the creation hash if there is no app yet.
     *
     * @return ?array{api_id: int, api_hash: string}
     */
    private function parseApps(string $page): ?array
    {
        $title = preg_match('~<title>(.*?)</title>~s', $page, $matches) ? trim($matches[1]) : '';
        if ($title === 'Create new application') {
            if (!preg_match('~<input type="hidden" name="hash" value="([^"]+)"~', $page, $matches)) {
                throw new Exception('Could not find the app creation hash!');
            }
            $this->creation_hash = $matches[1];
            return null;
        }
        // The first number after the label of the API ID, and the first 32 hex digits after the label of the hash:
        // the markup changes (a <strong> in a form, then a <span class="vv"> with the hash masked, its value in a
        // data-value attribute)
        if ($title === 'App configuration'
            && preg_match('~App api_id\b.*?\b(\d{3,})\b~s', $page, $id)
            && preg_match('~App api_hash\b.*?\b([a-f0-9]{32})\b~s', $page, $hash)
        ) {
            return ['api_id' => (int) $id[1], 'api_hash' => $hash[1]];
        }
        if ($title === 'App configuration') {
            // Logged in, with an app: its page changed again
            throw new Exception('Could not find the API ID and hash in the page of the app on my.telegram.org');
        }
        // Most likely, the login expired
        $this->logged = false;
        throw new Exception($title === '' ? 'Unexpected response from my.telegram.org' : $title);
    }
    /**
     * Make a request to my.telegram.org.
     *
     * @param array<string, string> $body POST parameters, or empty for a GET request
     */
    private function request(string $path, string $httpType, array $body = []): string
    {
        $request = new Request(self::MY_TELEGRAM_URL.$path, $body ? 'POST' : 'GET');
        $request->setHeaders($this->getHeaders($httpType));
        if ($body) {
            $request->setBody(http_build_query($body));
        }
        return $this->datacenter->HTTPClient->request($request)->getBody()->buffer();
    }
    /**
     * Get a readable error from a response of my.telegram.org.
     */
    private static function getError(string $result): string
    {
        // Errors of the login endpoints: {"text": "...", "field": ...}
        $json = json_decode($result, true);
        if (\is_array($json) && isset($json['text']) && \is_string($json['text'])) {
            $result = $json['text'];
        }
        $result = trim(html_entity_decode(strip_tags($result), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $result === '' ? 'Empty response from my.telegram.org' : $result;
    }
    /**
     * Function for generating curl request headers.
     *
     * @param string $httpType Origin
     *
     * @psalm-pure
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
            [$key, $value] = explode(':', $header, 2);
            $final_headers[trim($key)] = trim($value);
        }
        return $final_headers;
    }
}
