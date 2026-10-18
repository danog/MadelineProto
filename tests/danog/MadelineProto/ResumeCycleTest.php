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

/**
 * Interrupted calls and download streams, resumed after a crash.
 *
 * @see ResumeCycleTestCase
 *
 * @group session
 * @internal
 */
final class ResumeCycleTest extends ResumeCycleTestCase
{
    /* -------------------------------------------------------------------- *
     *  Interrupted calls, resumed when the session starts.
     * -------------------------------------------------------------------- */

    /**
     * @dataProvider sources
     */
    public function testASendIsResumedAfterACrash(string $source, bool $ranges): void
    {
        $caption = "Send from $source after a crash ".bin2hex(random_bytes(8));
        $result = self::fromSource($source, $ranges, ['kind' => 'send', 'caption' => $caption, 'fileName' => 'resumed.bin']);

        self::assertUploadResumed($result['log'], 'resumed.bin');
        self::assertSentOnce($result['log'], 'sendMedia', $caption);
    }

    public function testARawMethodCallIsResumedAfterACrash(): void
    {
        $caption = 'Raw method call after a crash '.bin2hex(random_bytes(8));
        $result = self::fromSource('local', false, ['kind' => 'method', 'caption' => $caption, 'fileName' => 'method.bin']);

        // Uploaded with the name of the file.
        self::assertUploadResumed($result['log'], 'file.bin');
        self::assertSentOnce($result['log'], 'messages.sendMedia', $caption);
    }

    /* -------------------------------------------------------------------- *
     *  Download streams.
     * -------------------------------------------------------------------- */

    public function testADownloadStreamIsResumedAfterACrash(): void
    {
        self::child(['phase' => 'interrupt', 'kind' => 'stream', 'source' => 'stream']);
        $result = self::child(['phase' => 'resume', 'kind' => 'stream', 'source' => 'stream']);

        self::assertSame(hash('sha256', self::$data), $result['sha256']);
    }
}
