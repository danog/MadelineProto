<?php

declare(strict_types=1);

/**
 * Response information module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Http\HttpStatus;
use danog\MadelineProto\Lang;

/**
 * Obtain response information for file to server.
 *
 * @internal
 */
final class ResponseInfo
{
    private const NO_CACHE = [
        'Cache-Control' => ['no-store, no-cache, must-revalidate, max-age=0', 'post-check=0, pre-check=0'],
        'Pragma' => 'no-cache',
    ];

    /**
     * Whether to serve file.
     */
    private bool $serve = false;
    /**
     * Serving range.
     */
    private array $serveRange = [];
    /**
     * HTTP response code.
     */
    private int $code = HttpStatus::OK;
    /**
     * Header array.
     *
     * @var array<non-empty-string, string|list<string>>
     */
    private array $headers = [];
    /**
     * Parse headers.
     *
     * @param string    $method       HTTP method
     * @param array     $headers      HTTP headers
     * @param array|int $messageMedia Media info
     */
    private function __construct(string $method, array $headers, array|int $messageMedia)
    {
        if (\is_int($messageMedia)) {
            $this->code = $messageMedia;
            $this->serve = false;
            $this->headers = self::NO_CACHE;
            return;
        }
        if (isset($headers['range'])) {
            $range = explode('=', $headers['range'], 2);
            if (\count($range) == 1) {
                $range[1] = '';
            }
            [$size_unit, $range_orig] = $range;
            if ($size_unit == 'bytes') {
                //multiple ranges could be specified at the same time, but for simplicity only serve the first range
                //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                $list = explode(',', $range_orig, 2);
                if (\count($list) == 1) {
                    $list[1] = '';
                }
                [$range, $extra_ranges] = $list;
            } else {
                $this->serve = false;
                $this->code = HttpStatus::RANGE_NOT_SATISFIABLE;
                $this->headers = self::NO_CACHE;
                return;
            }
        } else {
            $range = '';
        }
        $listseek = explode('-', $range, 2);
        if (\count($listseek) == 1) {
            $listseek[1] = '';
        }
        [$seek_start, $seek_end] = $listseek;

        $size = $messageMedia['size'] ?? 0;
        $seek_end = empty($seek_end) ? ($size - 1) : min(abs(\intval($seek_end)), $size - 1);

        if (!empty($seek_start) && $seek_end < abs(\intval($seek_start))) {
            $this->serve = false;
            $this->code = HttpStatus::RANGE_NOT_SATISFIABLE;
            $this->headers = self::NO_CACHE;
            return;
        }
        $seek_start = empty($seek_start) ? 0 : abs(\intval($seek_start));

        $isSafari = !empty($headers['user-agent']) && preg_match('/^((?!chrome|android).)*safari/i', $headers['user-agent']);
        if ($range !== '' && $isSafari) {
            //Safari video streaming fix
            $length = ($seek_end - $seek_start + 1);
            $maxChunkSize = 10 * 1024 ** 2;
            if ($length > $maxChunkSize) {
                $seek_end = $seek_start + $maxChunkSize - 1;
            }
        }

        $this->serve = $method !== 'HEAD';
        if ($seek_start > 0 || $seek_end < $size - 1) {
            $this->code = HttpStatus::PARTIAL_CONTENT;
            $this->headers['Content-Range'] = "bytes $seek_start-$seek_end/$size";
            $this->headers['Content-Length'] = (string) ($seek_end - $seek_start + 1);
        } elseif ($size > 0) {
            $this->headers['Content-Length'] = (string) $size;
        }
        $this->headers['Content-Type'] = (string) $messageMedia['mime'];
        $this->headers['Cache-Control'] = 'max-age=31556926';
        $this->headers['Content-Transfer-Encoding'] = 'Binary';
        $this->headers['Accept-Ranges'] = 'bytes';

        if ($this->serve) {
            if ($seek_start === 0 && $seek_end === -1) {
                $this->serveRange = [0, -1];
            } else {
                $this->serveRange = [$seek_start, $seek_end + 1];
            }

            if (!empty($messageMedia['name']) && !empty($messageMedia['ext'])) {
                $this->headers["Content-Disposition"] = "inline; filename=\"{$messageMedia['name']}{$messageMedia['ext']}\"";
            }
        }
    }
    /**
     * Parse headers.
     *
     * @param string $method       HTTP method
     * @param array  $headers      HTTP headers
     * @param array  $messageMedia Media info
     */
    public static function parseHeaders(string $method, array $headers, array $messageMedia): self
    {
        return new self($method, $headers, $messageMedia);
    }
    public static function error(int $code): self
    {
        return new self('', [], $code);
    }
    /**
     * Get explanation for HTTP code.
     */
    public function getCodeExplanation(): string
    {
        $reason = HttpStatus::getReason($this->code);
        $body = "<html lang='en'><body><h1>{$this->code} $reason</h1>";
        if ($this->code === HttpStatus::RANGE_NOT_SATISFIABLE) {
            $body .= '<p>Could not use selected range.</p>';
        }
        if ($this->code === HttpStatus::BAD_GATEWAY) {
            $body .= "<h2 style='color:red;'>".Lang::$current_lang["dl.php_check_logs_make_sure_session_running"].'</h2>';
        }
        $body .= '<small>'.Lang::$current_lang["dl.php_powered_by_madelineproto"].'</small>';
        $body .= '</body></html>';
        return $body;
    }

    /**
     * Whether to serve file.
     *
     * @return bool Whether to serve file
     */
    public function shouldServe(): bool
    {
        return $this->serve;
    }

    /**
     * Get serving range.
     *
     * @return array HTTP serving range
     */
    public function getServeRange(): array
    {
        return $this->serveRange;
    }

    /**
     * Get HTTP response code.
     *
     * @return int HTTP response code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get header array.
     *
     * @return array<non-empty-string, string|list<string>> Header array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Write headers.
     */
    public function writeHeaders(): void
    {
        http_response_code($this->getCode());
        foreach ($this->getHeaders() as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $subValue) {
                    header("$key: $subValue", false);
                }
            } else {
                header("$key: $value");
            }
        }
    }
}
