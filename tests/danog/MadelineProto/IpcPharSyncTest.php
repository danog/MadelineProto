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

use Amp\SignalException;
use danog\MadelineProto\API;
use danog\MadelineProto\GarbageCollector;
use danog\MadelineProto\Magic;
use PHPUnit\Framework\TestCase;

/**
 * The phars of IPC clients and of their worker are brought to the newest of their releases when a client
 * connects.
 *
 * Short-lived clients (like dl.php, run by the web server from the session directory) exit before completing the
 * update check, and their madeline.php loads the phar of their own directory: the stories downloader bot's
 * dl.php stayed on 8.4.6 for over a year, until a PHP update made it print deprecation notices instead of the
 * session ID, while its worker ran 8.11.
 *
 * @internal
 */
final class IpcPharSyncTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/madeline-phar-sync-'.bin2hex(random_bytes(8));
        mkdir("$this->dir/server", 0o777, true);
        mkdir("$this->dir/client");
    }

    public function tearDown(): void
    {
        foreach (['server', 'client'] as $sub) {
            array_map(unlink(...), glob("$this->dir/$sub/*"));
            rmdir("$this->dir/$sub");
        }
        rmdir($this->dir);
    }

    private function install(string $sub, string $release, string $contents): string
    {
        file_put_contents("$this->dir/$sub/madeline-$release.phar", $contents);
        file_put_contents("$this->dir/$sub/madeline.version", $release);
        return "$this->dir/$sub/madeline-$release.phar";
    }

    public function testSync(): void
    {
        $server = $this->install('server', API::RELEASE, 'server phar');
        $serverVersion = "$this->dir/server/madeline.version";
        $serverLoader = "$this->dir/server/madeline.php";
        $clientVersion = "$this->dir/client/madeline.version";
        $clientLoader = "$this->dir/client/madeline.php";
        file_put_contents($serverLoader, '<?php // server loader');
        file_put_contents($clientLoader, '<?php // old client loader');
        $sync = static fn (string $release, string $phar) => GarbageCollector::syncIpcClientPhar($release, $phar, $clientVersion, $clientLoader, $server, $serverVersion, $serverLoader);

        // An older client gets our phar and loader
        $client = $this->install('client', '8.0.0', 'old client phar');
        $sync('8.0.0', $client);
        self::assertSame(API::RELEASE, file_get_contents($clientVersion));
        self::assertSame('server phar', file_get_contents("$this->dir/client/madeline-".API::RELEASE.'.phar'));
        self::assertSame('<?php // server loader', file_get_contents($clientLoader));

        // The same release changes nothing
        file_put_contents($clientLoader, '<?php // old client loader');
        $sync(API::RELEASE, $client);
        self::assertSame(API::RELEASE, file_get_contents($clientVersion));
        self::assertSame('<?php // old client loader', file_get_contents($clientLoader));

        // A madeline.version already pointing to a newer phar isn't downgraded, nor is its loader
        file_put_contents($clientVersion, '99.1.0');
        $sync('8.0.0', $client);
        self::assertSame('99.1.0', file_get_contents($clientVersion));
        self::assertSame('<?php // old client loader', file_get_contents($clientLoader));

        // Installed with composer, we have no phar to give
        file_put_contents($clientVersion, '8.0.0');
        GarbageCollector::syncIpcClientPhar('8.0.0', $client, $clientVersion, $clientLoader, null, null, null);
        self::assertSame('8.0.0', file_get_contents($clientVersion));
        self::assertSame('<?php // old client loader', file_get_contents($clientLoader));

        // A newer client gives us its phar and loader, without stopping a full instance
        $client = $this->install('client', '99.0.0', 'new client phar');
        file_put_contents($clientLoader, '<?php // new client loader');
        $sync('99.0.0', $client);
        self::assertSame('99.0.0', file_get_contents($serverVersion));
        self::assertSame('new client phar', file_get_contents("$this->dir/server/madeline-99.0.0.phar"));
        self::assertSame('<?php // new client loader', file_get_contents($serverLoader));
        self::assertSame([], glob("$this->dir/*/*.temp*"));

        // A worker shuts down to be restarted with it
        $wasWorker = Magic::$isIpcWorker;
        Magic::$isIpcWorker = true;
        try {
            $this->expectException(SignalException::class);
            $sync('99.0.0', $client);
        } finally {
            Magic::$isIpcWorker = $wasWorker;
        }
    }
}
