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
        if ($templates->hasLegacyHtmlTemplate()) {
            return self::renderLegacy($templates, $page);
        }
        $lang = self::getLangCode();
        $dir = \in_array($lang, self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr';
        return $templates->getEngine()->render($page->template, [
            'page' => $page,
            'lang' => $lang,
            'dir' => $dir,
            'state' => self::getState($page, $lang, $dir),
        ]);
    }

    /**
     * Get the initial state of the web app, as a JSON string safe to embed in a `<script>` tag.
     */
    public static function getState(Page $page, string $lang = 'en', string $dir = 'ltr'): string
    {
        $strings = [];
        foreach (self::APP_STRINGS as $key) {
            $strings[$key] = Template::translate($key);
        }
        $steps = [];
        foreach (Step::cases() as $step) {
            $steps[] = ['id' => $step->value, 'label' => $step->getLabel()];
        }
        return json_encode(
            ['page' => $page, 'steps' => $steps, 'strings' => $strings, 'lang' => $lang, 'dir' => $dir],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Render a page and write it to the browser.
     */
    public static function write(Templates $templates, Page $page): void
    {
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
        if (headers_sent() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_SERVER['REQUEST_URI'])) {
            return false;
        }
        $query = explode('?', $_SERVER['REQUEST_URI'], 2)[1] ?? null;
        header('Location: '.self::getSelfUrl().($query === null ? '' : "?$query"), true, 303);
        header('Cache-Control: no-store');
        return true;
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
     * Render a page with the legacy sprintf template.
     */
    private static function renderLegacy(Templates $templates, Page $page): string
    {
        // The parts are rendered with the builtin templates, never with custom directories meant for full pages
        $engine = new TemplateEngine;
        $data = ['page' => $page];
        $trailer = $engine->render('partials/legacy-actions', $data)
            .$engine->render('partials/qr', $data)
            .'<script>'.Template::escapeScript($engine->asset('assets/madeline.js')).'</script>';
        return sprintf(
            $templates->getHtmlTemplate(),
            $engine->render('partials/legacy-intro', $data),
            $page->submit === null ? '' : $engine->render('partials/fields', $data),
            htmlspecialchars($page->submit ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
            $trailer,
        );
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
