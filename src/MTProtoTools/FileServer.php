<?php

declare(strict_types=1);

/**
 * Files module.
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
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\LocalMutex;
use AssertionError;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Tools;
use Exception;
use Revolt\EventLoop;
use Throwable;

use function Amp\File\exists;
use function Amp\File\read;
use function Amp\File\write;

/**
 * @internal
 *
 * @method Settings getSettings()
 */
trait FileServer
{
    /**
     * Downloads a file to the browser using the specified session file.
     */
    public static function downloadServer(string $session): void
    {
        if (isset($_GET['login'])) {
            $API = new API($session);
            $API->start();
            $id = (string) $API->getSelf()['id'];
            header("Content-length: ".\strlen($id));
            echo $id;
            die;
        }
        if (!isset($_GET['f'])) {
            new AppInfo;
            die(Lang::$current_lang["dl.php_powered_by_madelineproto"]);
        }

        try {
            $API = new API($session);
        } catch (Throwable $e) {
            Logger::log("An error occurred while instantiating the session: $e");
            $result = ResponseInfo::error(HttpStatus::BAD_GATEWAY);
            $result->writeHeaders();
            if (!\in_array($result->getCode(), [HttpStatus::OK, HttpStatus::PARTIAL_CONTENT], true)) {
                Tools::echo($result->getCodeExplanation());
            }
            die;
        }

        $API->downloadToBrowser(
            messageMedia: $_GET['f'],
            size: (int) $_GET['s'],
            name: $_GET['n'],
            mime: $_GET['m']
        );
    }

    /**
     * Internal endpoint used by the download server.
     */
    public static function processDownloadServerPing(string $path, string $payload): void
    {
        self::$checkedScripts[$path] = $payload;
    }

    /**
     * Get download link of media file.
     */
    public function getDownloadLink(array|string|Message|Media $media, ?string $scriptUrl = null, ?int $size = null, ?string $name = null, ?string $mime = null): string
    {
        $scriptUrl ??= $this->getSettings()->getFiles()->getDownloadLink();
        if ($scriptUrl === null) {
            try {
                $scriptUrl = $this->getDefaultDownloadScript();
            } catch (Throwable $e) {
                $sessionPath = var_export($this->getSessionName(), true);
                throw new Exception(sprintf(
                    Lang::$current_lang['need_dl.php'],
                    $e->getMessage(),
                    "<?php require 'vendor/autoload.php'; \\danog\\MadelineProto\\API::downloadServer($sessionPath); ?>",
                ));
            }
        } else {
            $this->checkDownloadScript($scriptUrl);
        }
        if ($media instanceof Message) {
            $media = $media->media;
        }
        if ($media instanceof Media) {
            $f = $media->botApiFileId;
            $name = $media->fileName;
            $mime = $media->mimeType;
            $size = $media->size;
        } else {
            if (\is_string($media) && ($size === null || $mime === null || $name === null)) {
                throw new Exception('downloadToBrowser only supports bot file IDs if the file size, file name and MIME type are also specified in the third, fourth and fifth parameters of the method.');
            }

            $messageMedia = $this->getDownloadInfo($media);
            $size ??= $messageMedia['size'];
            $mime ??= $messageMedia['mime'];
            if (\is_string($media)) {
                $f = $media;
            } else {
                $f = $this->extractBotAPIFile($this->MTProtoToBotAPI($media))['file_id'];
                $name ??= $messageMedia['name'].$messageMedia['ext'];
            }
        }

        return $scriptUrl."?".http_build_query([
            'f' => $f,
            's' => $size,
            'n' => $name,
            'm' => $mime,
        ]);
    }

    private ?LocalMutex $scriptMutex = null;
    private static array $checkedAutoload = [];
    private function getDefaultDownloadScript(): string
    {
        $isCli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        if ($isCli && (!Magic::$isIpcWorker || !($_ENV['absoluteRootDir'] ?? null))) {
            throw new AssertionError(Lang::$current_lang["cli_need_dl.php_link"]);
        }
        $s = $this->getSessionName();
        if (\defined('MADELINE_PHP')) {
            $autoloadPath = \MADELINE_PHP;
        } elseif (\defined('MADELINE_PHAR')) {
            $autoloadPath = \MADELINE_PHAR;
        } else {
            $paths = [
                \dirname(__DIR__, 4).'/autoload.php',
                \dirname(__DIR__, 2).'/vendor/autoload.php',
                \dirname(__DIR__, 6).'/autoload.php',
                \dirname(__DIR__, 4).'/vendor/autoload.php',
            ];

            foreach ($paths as $path) {
                if (exists($path)) {
                    $autoloadPath = $path;
                    break;
                }
            }

            if (!isset($autoloadPath)) {
                throw new AssertionError('Could not locate autoload.php in any of the following files: '.implode(', ', $paths));
            }
        }

        if (isset(self::$checkedAutoload[$autoloadPath])) {
            return self::$checkedAutoload[$autoloadPath];
        }
        $this->scriptMutex ??= new LocalMutex;
        $lock = $this->scriptMutex->acquire();
        try {
            $downloadScript = sprintf('<?php require %s; \danog\MadelineProto\API::downloadServer(__DIR__);', var_export($autoloadPath, true));
            $f = $s.DIRECTORY_SEPARATOR.'dl.php';
            $recreate = true;
            if (exists($f)) {
                $recreate = read($f) !== $downloadScript;
            }
            if ($recreate) {
                $temp = "$f.temp.php";
                write($temp, $downloadScript);
                rename($temp, $f);
            }

            $f = realpath($f);
            $absoluteRootDir = $isCli
                ? $_ENV['absoluteRootDir']
                : WebRunner::getAbsoluteRootDir();

            if (substr($f, 0, \strlen($absoluteRootDir)) !== $absoluteRootDir) {
                throw new AssertionError("Process runner $f is not within readable document root $absoluteRootDir!");
            }
            $f = substr($f, \strlen($absoluteRootDir)-1);
            $f = str_replace(DIRECTORY_SEPARATOR, '/', $f);
            $f = str_replace('//', '/', $f);
            $f = 'https://'.($isCli ? $_ENV['serverName'] : $_SERVER['SERVER_NAME']).$f;
            $this->checkDownloadScript($f);
            return self::$checkedAutoload[$autoloadPath] = $f;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    private static ?LocalKeyedMutex $checkMutex = null;
    private static array $checkedScripts = [];
    private function checkDownloadScript(string $scriptUrl): void
    {
        if (!str_starts_with($scriptUrl, 'http://') && !str_starts_with($scriptUrl, 'https://')) {
            throw new AssertionError("The download script must be an HTTP (preferrably HTTPS) URL!");
        }
        if (isset(self::$checkedScripts[$scriptUrl])) {
            return;
        }
        self::$checkMutex ??= new LocalKeyedMutex;
        $lock = self::$checkMutex->acquire($scriptUrl);
        try {
            /** @psalm-suppress ParadoxicalCondition */
            if (isset(self::$checkedScripts[$scriptUrl])) {
                return;
            }
            $scriptUrlNew = $scriptUrl.'?'.http_build_query(['login' => 1]);
            $this->logger->logger("Checking $scriptUrlNew...");
            $result = $this->fileGetContents($scriptUrlNew);
            $expected = (string) $this->getSelf()['id'];
            if ($result !== $expected) {
                throw new AssertionError(sprintf(
                    Lang::$current_lang['invalid_dl.php_session'],
                    $scriptUrl,
                    $expected,
                    $result,
                ));
            }

            self::$checkedScripts[$scriptUrl] = true;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
}
