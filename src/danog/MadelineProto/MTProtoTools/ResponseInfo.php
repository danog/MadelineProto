<?php
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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Http\Status;

/**
 * Obtain response information for file to server.
 */
class ResponseInfo
{
    private const POWERED_BY = "<p><small>Powered by <a href='https://docs.madelineproto.xyz'>MadelineProto</a></small></p>";
    private const NO_CACHE = [
        'Cache-Control' => ['no-store, no-cache, must-revalidate, max-age=0', 'post-check=0, pre-check=0'],
        'Pragma' => 'no-cache'
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
    private int $code = Status::OK;
    /**
     * Header array.
     */
    private array $headers = [];
    /**
     * Parse headers.
     *
     * @param string $method       HTTP method
     * @param array  $headers      HTTP headers
     * @param array  $messageMedia Media info
     */
    private function __construct(string $method, array $headers, array $messageMedia)
    {
        if (isset($headers['range'])) {
            $range = \explode('=', $headers['range'], 2);
            if (\count($range) == 1) {
                $range[1] = '';
            }
            [$size_unit, $range_orig] = $range;
            if ($size_unit == 'bytes') {
                //multiple ranges could be specified at the same time, but for simplicity only serve the first range
                //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                $list = \explode(',', $range_orig, 2);
                if (\count($list) == 1) {
                    $list[1] = '';
                }
                [$range, $extra_ranges] = $list;
            } else {
                $this->serve = false;
                $this->code = Status::RANGE_NOT_SATISFIABLE;
                $this->headers = self::NO_CACHE;
                return;
            }
        } else {
            $range = '';
        }
        $listseek = \explode('-', $range, 2);
        if (\count($listseek) == 1) {
            $listseek[1] = '';
        }
        [$seek_start, $seek_end] = $listseek;

        $size = $messageMedia['size'] ?? 0;
        $seek_end = empty($seek_end) ? ($size - 1) : \min(\abs(\intval($seek_end)), $size - 1);

        if (!empty($seek_start) && $seek_end < \abs(\intval($seek_start))) {
            $this->serve = false;
            $this->code = Status::RANGE_NOT_SATISFIABLE;
            $this->headers = self::NO_CACHE;
            return;
        }
        $seek_start = empty($seek_start) ? 0 : \abs(\intval($seek_start));

        $this->serve = $method !== 'HEAD';
        if ($seek_start > 0 || $seek_end < $size - 1) {
            $this->code = Status::PARTIAL_CONTENT;
            $this->headers['Content-Range'] = "bytes ${seek_start}-${seek_end}/${size}";
            $this->headers['Content-Length'] = $seek_end - $seek_start + 1;
        } elseif ($size > 0) {
            $this->headers['Content-Length'] = $size;
        }
        $this->headers['Content-Type'] = $messageMedia['mime'];
        $this->headers['Cache-Control'] = 'max-age=31556926';
        $this->headers['Content-Transfer-Encoding'] = 'Binary';
        $this->headers['Accept-Ranges'] = 'bytes';

        if ($this->serve) {
            if ($seek_start === 0 && $seek_end === -1) {
                $this->serveRange = [0, -1];
            } else {
                $this->serveRange = [$seek_start, $seek_end + 1];
            }
        }
    }
    /**
     * Parse headers.
     *
     * @param string $method       HTTP method
     * @param array  $headers      HTTP headers
     * @param array  $messageMedia Media info
     *
     * @return self
     */
    public static function parseHeaders(string $method, array $headers, array $messageMedia): self
    {
        return new self($method, $headers, $messageMedia);
    }
    /**
     * Get explanation for HTTP code.
     *
     * @return string
     */
    public function getCodeExplanation(): string
    {
        $reason = Status::getReason($this->code);
        $body = "<html><body><h1>{$this->code} $reason</h1><br>";
        if ($this->code === Status::RANGE_NOT_SATISFIABLE) {
            $body .= "<p>Could not use selected range.</p>";
        }
        $body .= self::POWERED_BY;
        $body .= "</body></html>";
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
     * @return array Header array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
