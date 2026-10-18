<?php

declare(strict_types=1);

/**
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

namespace danog\MadelineProto\WebUI;

use AssertionError;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Settings\Templates;

use function Amp\ByteStream\getOutputBufferStream;

/**
 * Renders and outputs the pages of the web UI.
 *
 * @internal
 */
final class WebUI
{
    private const RTL_LANGUAGES = ['ar', 'ckb', 'fa', 'he', 'ur'];
    /**
     * Query parameter of the asset route.
     */
    public const ASSET_PARAMETER = 'madelineAsset';
    private const ASSET_TYPES = [
        'js' => 'text/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'wasm' => 'application/wasm',
    ];
    /**
     * Strings used by the web app itself (the pages carry their own).
     */
    private const APP_STRINGS = [
        'webDocs',
        'webProgress',
        'webOptional',
        'webShowPassword',
        'webHidePassword',
        'webFieldRequired',
        'webPhoneInvalid',
        'webQrTitle',
        'webQrWaiting',
        'loginQrCodeSuccessful',
        'loginWebQr1',
        'loginWebQr2',
        'loginWebQr3',
    ];

    /**
     * Render a page.
     */
    public static function render(Templates $templates, Page $page): string
    {
        $lang = self::getLangCode();
        $dir = \in_array($lang, self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr';
        return $templates->getEngine()->render($page->template, [
            'page' => $page,
            'lang' => $lang,
            'dir' => $dir,
            'state' => self::getState($page, $lang, $dir),
            'assetUrl' => self::getAssetUrl(),
        ]);
    }

    /**
     * Get the state of the web app.
     *
     * @return array<string, mixed>
     */
    public static function getStateArray(Page $page, string $lang = 'en', string $dir = 'ltr'): array
    {
        $strings = [];
        foreach (self::APP_STRINGS as $key) {
            $strings[$key] = Template::translate($key);
        }
        // The strings of the web app (webui/src/lang.ts, tweb's keys), in the language of the session: the app
        // has them in English, translated in langs/*.json as tweb.<key>
        foreach (Lang::$current_lang as $key => $string) {
            if (str_starts_with($key, 'tweb.')) {
                $strings[substr($key, 5)] = $string;
            }
        }
        $steps = [];
        foreach (Step::cases() as $step) {
            $steps[] = ['id' => $step->value, 'label' => $step->getLabel()];
        }
        return [
            'page' => $page,
            'steps' => $steps,
            'strings' => $strings,
            'lang' => $lang,
            'dir' => $dir,
            'assetUrl' => self::getAssetUrl(),
        ];
    }

    /**
     * Get the initial state of the web app, as a JSON string safe to embed in a `<script>` tag.
     */
    public static function getState(Page $page, string $lang = 'en', string $dir = 'ltr'): string
    {
        return json_encode(
            self::getStateArray($page, $lang, $dir),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Whether the web app asked for a JSON answer (it submits its forms with fetch).
     */
    public static function isJsonRequest(): bool
    {
        return ($_SERVER['HTTP_X_MADELINEPROTO_WEBUI'] ?? '') === 'json';
    }

    /**
     * Render a page and write it to the browser: as HTML, or as the state of the web app if it asked for JSON.
     */
    public static function write(Templates $templates, Page $page): void
    {
        if (self::isJsonRequest()) {
            $lang = self::getLangCode();
            self::writeJson(['state' => self::getStateArray($page, $lang, \in_array($lang, self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr')]);
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            header('Referrer-Policy: no-referrer');
        }
        getOutputBufferStream()->write(self::render($templates, $page));
    }

    /**
     * Redirect the browser to the current URL with a GET request, after a POST that moved to the next step:
     * reloading the page then shows the step again, instead of submitting the previous one again.
     *
     * Only for intermediate steps: after the last one, the script continues, and must run once.
     *
     * @return bool Whether the redirect was sent: if not, the caller must show the page
     */
    public static function redirectToSelf(): bool
    {
        // The web app gets the next page in the answer
        if (self::isJsonRequest() || headers_sent() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_SERVER['REQUEST_URI'])) {
            return false;
        }
        $query = explode('?', $_SERVER['REQUEST_URI'], 2)[1] ?? null;
        header('Location: '.self::getSelfUrl().($query === null ? '' : "?$query"), true, 303);
        header('Cache-Control: no-store');
        return true;
    }

    /**
     * After the last step of the login, tell the web app to reload the page, if it asked for JSON.
     *
     * The request must then end without running the rest of the script: it runs in the GET of the reload, once.
     *
     * @return bool Whether the answer was sent: if so, the caller must end the request
     */
    public static function writeLoggedIn(): bool
    {
        if (!self::isJsonRequest()) {
            return false;
        }
        self::writeJson(['loggedIn' => true]);
        return true;
    }

    /**
     * Get the prefix of the URLs of the assets: append the path of an asset, like `assets/app.js`.
     */
    public static function getAssetUrl(): string
    {
        return self::getSelfUrl().'?'.self::ASSET_PARAMETER.'=';
    }

    /**
     * Serve an asset of the template directories, if the request is for one (`?madelineAsset=assets/...`).
     *
     * @return bool Whether the request was for an asset: if so, the caller must end the request
     */
    public static function serveAsset(Templates $templates): bool
    {
        if (!isset($_GET[self::ASSET_PARAMETER]) || !\is_string($_GET[self::ASSET_PARAMETER])) {
            return false;
        }
        $path = $_GET[self::ASSET_PARAMETER];
        $type = self::ASSET_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        try {
            // Only assets: never the templates, nor anything outside the template directories
            $file = str_starts_with($path, 'assets/') && $type !== null && $templates->getEngine()->exists($path)
                ? $templates->getEngine()->resolve($path)
                : null;
        } catch (AssertionError) {
            $file = null;
        }
        // The name can't leave the directories (see TemplateEngine), but a symlink in them could; phars have no
        // symlinks, and realpath() doesn't support them
        if ($file !== null && !str_starts_with($file, 'phar://')) {
            $real = realpath($file);
            $file = null;
            foreach ($templates->getEngine()->getDirectories() as $dir) {
                $assets = realpath($dir.DIRECTORY_SEPARATOR.'assets');
                if ($real !== false && $assets !== false && str_starts_with($real, $assets.DIRECTORY_SEPARATOR)) {
                    $file = $real;
                    break;
                }
            }
        }
        if ($file === null) {
            if (!headers_sent()) {
                http_response_code(404);
                header('Content-Type: text/plain; charset=utf-8');
            }
            getOutputBufferStream()->write('Not found');
            return true;
        }
        $contents = file_get_contents($file);
        \assert($contents !== false);
        $etag = '"'.hash('xxh128', $contents).'"';
        if (!headers_sent()) {
            header("Content-Type: $type");
            header('Cache-Control: no-cache');
            header("ETag: $etag");
            header('X-Content-Type-Options: nosniff');
            if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
                http_response_code(304);
                return true;
            }
        }
        getOutputBufferStream()->write($contents);
        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function writeJson(array $data): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('Cache-Control: no-store');
        }
        getOutputBufferStream()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
    }

    /**
     * Get the path of the current URL, without the query string.
     *
     * Always same-origin: a request for `//evil.example/` (which a front controller may route to the script)
     * would otherwise give a protocol-relative URL, pointing redirects and the QR code requests to another
     * host.
     */
    public static function getSelfUrl(): string
    {
        $path = explode('?', $_SERVER['REQUEST_URI'] ?? '', 2)[0];
        return '/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Get the code of the current language.
     */
    private static function getLangCode(): string
    {
        // Untranslated languages are copies of English: check it first
        if (Lang::$current_lang === Lang::$lang['en']) {
            return 'en';
        }
        foreach (Lang::$lang as $code => $strings) {
            if ($strings === Lang::$current_lang) {
                return $code;
            }
        }
        return 'en';
    }
}
