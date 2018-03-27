<?php
/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Wrapper for my.telegram.org.
 */
class MyTelegramOrgWrapper
{
    private $logged = false;

    public function __construct($number)
    {
        if (!extension_loaded('curl')) {
            throw new Exception(['extension', 'curl']);
        }
        $this->number = $number;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://my.telegram.org/auth/send_password');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['phone' => $number]));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = [];
        $headers[] = 'Origin: https://my.telegram.org';
        $headers[] = 'Accept-Encoding: gzip, deflate, br';
        $headers[] = 'Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
        $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';
        $headers[] = 'Referer: https://my.telegram.org/auth';
        $headers[] = 'X-Requested-With: XMLHttpRequest';
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Dnt: 1';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: '.curl_error($ch));
        }
        curl_close($ch);
        $resulta = json_decode($result, true);
        if (!isset($resulta['random_hash'])) {
            throw new Exception($result);
        }
        $this->hash = $resulta['random_hash'];
    }

    public function complete_login($password)
    {
        if ($this->logged) {
            throw new Exception('Already logged in!');
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://my.telegram.org/auth/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['phone' => $this->number, 'random_hash' => $this->hash, 'password' => $password]));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = [];
        $headers[] = 'Origin: https://my.telegram.org';
        $headers[] = 'Accept-Encoding: gzip, deflate, br';
        $headers[] = 'Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
        $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';
        $headers[] = 'Referer: https://my.telegram.org/auth';
        $headers[] = 'X-Requested-With: XMLHttpRequest';
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Dnt: 1';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: '.curl_error($ch));
        }
        curl_close($ch);

        list($response_headers, $response_content) = preg_split('/(\r\n){2}/', $result, 2);
        switch ($response_content) {
            case 'true':
                //Logger::log(['Login OK'], Logger::VERBOSE);
                break;
            default:
                throw new Exception($response_content);
        }
        $this->token = explode(';', explode('stel_token=', $response_headers)[1])[0];

        return $this->logged = true;
    }

    public function logged_in()
    {
        return $this->logged;
    }

    public function has_app()
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://my.telegram.org/apps');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        $headers = [];
        $headers[] = 'Dnt: 1';
        $headers[] = 'Accept-Encoding: gzip, deflate, sdch, br';
        $headers[] = 'Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $headers[] = 'Referer: https://my.telegram.org/';
        $headers[] = 'Cookie: stel_token='.$this->token;
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Cache-Control: max-age=0';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: '.curl_error($ch));
        }
        curl_close($ch);
        $title = explode('</title>', explode('<title>', $result)[1])[0];
        switch ($title) {
            case 'App configuration': return true;
            case 'Create new application': $this->creation_hash = explode('"/>', explode('<input type="hidden" name="hash" value="', $result)[1])[0];

return false;
        }

        throw new Exception($title);
    }

    public function get_app()
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://my.telegram.org/apps');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        $headers = [];
        $headers[] = 'Dnt: 1';
        $headers[] = 'Accept-Encoding: gzip, deflate, sdch, br';
        $headers[] = 'Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $headers[] = 'Referer: https://my.telegram.org/';
        $headers[] = 'Cookie: stel_token='.$this->token;
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Cache-Control: max-age=0';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: '.curl_error($ch));
        }
        curl_close($ch);

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

    public function create_app($settings)
    {
        if (!$this->logged) {
            throw new Exception('Not logged in!');
        }
        if ($this->has_app()) {
            throw new Exception('The app was already created!');
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://my.telegram.org/apps/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['hash' => $this->creation_hash, 'app_title' => $settings['app_title'], 'app_shortname' => $settings['app_shortname'], 'app_url' => $settings['app_url'], 'app_platform' => $settings['app_platform'], 'app_desc' => $settings['app_desc']]));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = [];
        $headers[] = 'Cookie: stel_token='.$this->token;
        $headers[] = 'Origin: https://my.telegram.org';
        $headers[] = 'Accept-Encoding: gzip, deflate, br';
        $headers[] = 'Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
        $headers[] = 'Accept: */*';
        $headers[] = 'Referer: https://my.telegram.org/apps';
        $headers[] = 'X-Requested-With: XMLHttpRequest';
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Dnt: 1';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error:'.curl_error($ch));
        }
        curl_close($ch);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://my.telegram.org/apps');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = [];
        $headers[] = 'Dnt: 1';
        $headers[] = 'Accept-Encoding: gzip, deflate, sdch, br';
        $headers[] = 'Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $headers[] = 'Referer: https://my.telegram.org/';
        $headers[] = 'Cookie: stel_token='.$this->token;
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Cache-Control: max-age=0';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error:'.curl_error($ch));
        }
        curl_close($ch);

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
}
