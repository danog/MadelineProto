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
 * @copyright 2016-2026 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProtoTools\TransferProgress;
use PHPUnit\Framework\TestCase;

/**
 * Progress callbacks of uploads and downloads get the percentage, the speed in mbps and the seconds elapsed (#1553).
 */
final class TransferProgressTest extends TestCase
{
    public function testEachPartReportsTheSpeedAndTimeSoFar(): void
    {
        $calls = [];
        $progress = new TransferProgress(static function (float $percent, float $speed, float $time) use (&$calls): void {
            $calls[] = [$percent, $speed, $time];
        }, 4);

        // Parts that were already uploaded before a resume don't count towards the speed.
        $progress->partDone(0);
        usleep(50_000);
        $progress->partDone(1_000_000);
        usleep(50_000);
        $progress->partDone(1_000_000);

        self::assertCount(3, $calls);
        self::assertSame([25.0, 50.0, 75.0], array_column($calls, 0));

        self::assertSame(0.0, $calls[0][1]);

        [, $speed, $time] = $calls[1];
        self::assertGreaterThanOrEqual(0.05, $time);
        // 1 MB in $time seconds, in mbps.
        self::assertEqualsWithDelta(8 / $time, $speed, 8 / $time * 0.01);

        [, $speed, $time2] = $calls[2];
        self::assertGreaterThanOrEqual($time + 0.05, $time2);
        self::assertEqualsWithDelta(16 / $time2, $speed, 16 / $time2 * 0.01);
    }
}
