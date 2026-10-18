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

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\Exception;
use PHPUnit\Framework\TestCase;

/**
 * A result with a constructor missing from the schema (Telegram serving a newer schema than ours) fails its
 * request, and the connection keeps working: the read loop used to exit, the request was resent after reconnecting,
 * got the same result, and never returned.
 */
final class UndeserializableResultTest extends TestCase
{
    /** How long a request may take, at most: less than the timeout of requests (60 seconds). */
    private const MAX_DURATION = 10.0;

    private string $dir;
    private ?API $API = null;

    public function setUp(): void
    {
        foreach (['API_ID', 'API_HASH'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        $this->dir = sys_get_temp_dir().'/madeline-undeserializable-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
        // Before removing its session: it is serialized when destroyed.
        $this->API = null;
        while (gc_collect_cycles());
        API::finalize();
        if (isset($this->dir)) {
            exec('rm -rf '.escapeshellarg($this->dir));
        }
        Magic::$isIpcWorker = false;
    }

    public function testAnUndeserializableResultFailsItsRequest(): void
    {
        // The schema, without the result of help.getNearestDc.
        $schema = (new Settings)->getSchema()->getAPISchema();
        $schema = preg_replace('/^nearestDc#.*$/m', '', file_get_contents($schema), count: $count);
        self::assertSame(1, $count);
        file_put_contents("{$this->dir}/schema.tl", $schema);

        // In this process, to get the exception of the worker.
        Magic::start(light: false);
        Magic::$isIpcWorker = true;
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash(getenv('API_HASH'));
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra(sys_get_temp_dir().'/madeline-undeserializable.log')->setLevel(Logger::VERBOSE);
        $settings->getSchema()->setAPISchema("{$this->dir}/schema.tl");
        // No need to log in.
        $API = $this->API = new API("{$this->dir}/session.madeline", $settings);
        $API->help->getConfig();

        for ($i = 0; $i < 2; $i++) {
            $start = microtime(true);
            try {
                $API->help->getNearestDc();
                self::fail('The result was deserialized');
            } catch (Exception $e) {
                self::assertStringContainsString('8e1a1775', $e->getMessage());
            }
            self::assertLessThan(self::MAX_DURATION, microtime(true) - $start, 'The request failed right away');

            $start = microtime(true);
            self::assertSame('config', $API->help->getConfig()['_']);
            self::assertLessThan(self::MAX_DURATION, microtime(true) - $start, 'The connection still works');
        }
    }
}
