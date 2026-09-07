<?php

declare(strict_types=1);

/**
 * MCP session manager.
 *
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

namespace danog\MadelineProto\Mcp;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger as MadelineLogger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;

use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\listFiles;

/**
 * @internal
 */
final class SessionManager
{
    /** @var array<string, API> */
    private array $sessions = [];
    private ?string $current = null;
    private readonly ?string $fixedSession;

    public function __construct(?string $fixedSession)
    {
        $this->fixedSession = $fixedSession === null ? null : Tools::absolute($fixedSession);
        $this->current = $this->fixedSession;
    }

    public function get(?string $session): API
    {
        $session = $this->resolveSession($session);
        return $this->sessions[$session] ?? $this->open($session);
    }

    public function resolve(?string $session): string
    {
        return $this->resolveSession($session);
    }

    public function open(string $session, ?int $apiId = null, ?string $apiHash = null, ?string $botToken = null): API
    {
        $session = $this->resolveSession($session);
        if (isset($this->sessions[$session])) {
            $this->current = $session;
            return $this->sessions[$session];
        }

        $settings = new Settings;
        $settings->getLogger()
            ->setType(MadelineLogger::FILE_LOGGER)
            ->setExtra(Magic::getcwd().'/MadelineProtoMcp.log')
            ->setLevel(MadelineLogger::NOTICE);
        $settings->getAppInfo()->setShowPrompt(false);
        if ($apiId !== null || $apiHash !== null) {
            if ($apiId === null || $apiHash === null) {
                throw new \InvalidArgumentException('Both apiId and apiHash must be provided together.');
            }
            $settings->getAppInfo()->setApiId($apiId);
            $settings->getAppInfo()->setApiHash($apiHash);
        }

        $api = new API($session, $settings);
        if ($botToken !== null) {
            $api->botLogin($botToken);
        }

        $this->sessions[$session] = $api;
        $this->current = $session;
        return $api;
    }

    /** @return list<array{name: string, path: string, current: bool, opened: bool, exists: bool}> */
    public function list(): array
    {
        if ($this->fixedSession !== null) {
            return [$this->sessionInfo($this->fixedSession)];
        }

        $sessions = [];
        $cwd = Magic::getcwd();
        foreach (listFiles($cwd) as $name) {
            $path = $cwd.DIRECTORY_SEPARATOR.$name;
            if (isDirectory($path) && (isFile($path.'/safe.php') || isFile($path.'/lightState.php'))) {
                $sessions[Tools::absolute($path)] = true;
            } elseif (isFile($path) && str_ends_with($name, '.safe.php')) {
                $sessions[Tools::absolute(substr($path, 0, -9))] = true;
            }
        }
        foreach ($this->sessions as $session => $_) {
            $sessions[$session] = true;
        }

        $result = [];
        foreach (array_keys($sessions) as $session) {
            $result[] = $this->sessionInfo($session);
        }
        usort($result, static fn (array $a, array $b): int => $a['path'] <=> $b['path']);
        return $result;
    }

    private function resolveSession(?string $session): string
    {
        if ($this->fixedSession !== null) {
            if ($session !== null && Tools::absolute($session) !== $this->fixedSession) {
                throw new \InvalidArgumentException('This MCP server is restricted to a single session.');
            }
            return $this->fixedSession;
        }
        if ($session === null) {
            if ($this->current === null) {
                throw new \InvalidArgumentException('No session is open, call openSession first or pass a session argument.');
            }
            return $this->current;
        }
        return Tools::absolute($session);
    }

    /** @return array{name: string, path: string, current: bool, opened: bool, exists: bool} */
    private function sessionInfo(string $session): array
    {
        $name = basename($session);
        return [
            'name' => $name,
            'path' => $session,
            'current' => $this->current === $session,
            'opened' => isset($this->sessions[$session]),
            'exists' => isFile($session.'/safe.php') || isFile($session.'.safe.php'),
        ];
    }
}
