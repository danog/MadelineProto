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

namespace danog\MadelineProto\Test;

use Amp\ByteStream\ReadableIterableStream;
use Amp\DeferredFuture;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Psr\Log\NullLogger;

/**
 * Files to upload in resume tests.
 */
trait ServesFiles
{
    /**
     * Change the first byte of each part of a file, keeping its size.
     */
    private static function change(string $data): string
    {
        for ($offset = 0; $offset < \strlen($data); $offset += 512 * 1024) {
            $data[$offset] = \chr(\ord($data[$offset]) ^ 0xff);
        }
        return $data;
    }

    /**
     * Serve a file over HTTP from this process.
     *
     * The contents can be changed between requests, keeping the same ETag (as a server with a weak validator would).
     *
     * @param list<?string>       $requests    The Range header of each request
     * @param ?DeferredFuture     $release     If set, full responses stall after $stallAt bytes until it completes
     *
     * @return array{SocketHttpServer, string} The server and the URL of the file
     */
    private static function serve(string &$content, bool $ranges = false, bool $honorRanges = true, array &$requests = [], ?DeferredFuture $release = null, int $stallAt = 0): array
    {
        $server = SocketHttpServer::createForDirectAccess(new NullLogger);
        $server->expose('127.0.0.1:0');
        $server->start(new ClosureRequestHandler(static function (Request $request) use (&$content, $ranges, $honorRanges, &$requests, $release, $stallAt): Response {
            $data = $content;
            $range = $request->getHeader('range');
            $requests[] = $range;
            $headers = [
                'content-type' => 'application/octet-stream',
                'etag' => '"resumable-test"',
            ];
            if ($ranges) {
                $headers['accept-ranges'] = 'bytes';
            }
            $status = HttpStatus::OK;
            $offset = 0;
            if ($ranges && $honorRanges && $range !== null && preg_match('/^bytes=(\d+)-$/', $range, $matches)) {
                $status = HttpStatus::PARTIAL_CONTENT;
                $offset = (int) $matches[1];
                $headers['content-range'] = "bytes $offset-".(\strlen($data) - 1).'/'.\strlen($data);
            }
            $headers['content-length'] = (string) (\strlen($data) - $offset);
            $body = (static function () use ($data, $offset, $release, $stallAt): \Generator {
                if ($release !== null && !$release->isComplete() && $offset < $stallAt) {
                    yield substr($data, $offset, $stallAt - $offset);
                    $release->getFuture()->await();
                    $offset = $stallAt;
                }
                if ($offset < \strlen($data)) {
                    yield substr($data, $offset);
                }
            })();
            return new Response($status, $headers, new ReadableIterableStream($body));
        }), new DefaultErrorHandler);
        return [$server, 'http://'.$server->getServers()[0]->getAddress()->toString().'/file.bin'];
    }
}
