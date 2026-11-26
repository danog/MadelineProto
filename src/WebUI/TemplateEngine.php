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

use AssertionError;
use danog\MadelineProto\Exception;

/**
 * Renders the native PHP templates of the web UI.
 *
 * A template name like `page` or `partials/field` is looked up as `page.phtml` or `partials/field.phtml`
 * in each of the configured directories, in order, and then in the builtin template directory: a custom
 * directory only needs to contain the templates it overrides.
 *
 * Inside a template, `$this` is a {@see Template}, and the variables passed to the template are in scope.
 */
final class TemplateEngine
{
    /**
     * Directory of the builtin templates.
     */
    public const BUILTIN_DIRECTORY = __DIR__.DIRECTORY_SEPARATOR.'templates';

    /** @var list<string> */
    private readonly array $directories;

    /**
     * @param list<string> $directories Template directories, searched in order before the builtin one.
     */
    public function __construct(array $directories = [])
    {
        $directories[] = self::BUILTIN_DIRECTORY;
        $this->directories = array_values(array_unique(array_map(
            static fn (string $dir): string => rtrim($dir, '/\\'),
            $directories,
        )));
    }

    /**
     * Get the template directories, in lookup order.
     *
     * @return list<string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Render a template.
     *
     * @param string               $name Template name, without the .phtml extension
     * @param array<string, mixed> $data Variables passed to the template
     */
    public function render(string $name, array $data = []): string
    {
        return (new Template($this, $name))->render($data);
    }

    /**
     * Check whether a template or asset exists.
     *
     * @param string $file File name relative to the template directories, including the extension
     *
     * @psalm-taint-specialize
     */
    public function exists(string $file): bool
    {
        return $this->find($file) !== null;
    }

    /**
     * Get the path of a template or asset.
     *
     * @param string $file File name relative to the template directories, including the extension
     *
     * @psalm-taint-specialize
     */
    public function resolve(string $file): string
    {
        return $this->find($file) ?? throw new Exception("Could not find $file in the template directories ".implode(', ', $this->directories));
    }

    /**
     * Resolve a user-requested asset path strictly inside the `assets/` subdirectory of the template directories.
     *
     * Enforces the `assets/` prefix, an explicit extension allowlist, traversal rejection, and symlink containment
     * via `realpath()` (for non-PHAR paths).
     *
     * @param list<string> $allowedExtensions Lowercase extensions allowed to be served
     *
     * @internal
     * @psalm-taint-escape file
     * @psalm-taint-escape ssrf
     * @psalm-taint-escape url_component
     * @psalm-taint-escape url_path
     */
    public function resolveAsset(string $file, array $allowedExtensions): ?string
    {
        $normalized = str_replace('\\', '/', $file);
        if (!str_starts_with($normalized, 'assets/')
            || preg_match('~(^|/)\.\.?(/|$)|[\0:]~', $normalized)
            || !\in_array(strtolower(pathinfo($normalized, PATHINFO_EXTENSION)), $allowedExtensions, true)
        ) {
            return null;
        }
        $relative = substr($normalized, \strlen('assets/'));
        foreach ($this->directories as $dir) {
            $assetsDir = $dir.DIRECTORY_SEPARATOR.'assets';
            $candidate = $assetsDir.DIRECTORY_SEPARATOR.$relative;
            if (!is_file($candidate)) {
                continue;
            }
            // The name can't leave the directories, but a symlink in them could; phars have no symlinks, and
            // realpath() doesn't support them
            if (str_starts_with($candidate, 'phar://')) {
                return $candidate;
            }
            $real = realpath($candidate);
            $realAssets = realpath($assetsDir);
            if ($real !== false && $realAssets !== false && str_starts_with($real, $realAssets.DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }
        return null;
    }

    /**
     * Get the contents of an asset (stylesheet, script, image...).
     *
     * @param string $file File name relative to the template directories, including the extension
     *
     * @psalm-taint-specialize
     */
    public function asset(string $file): string
    {
        $contents = file_get_contents($this->resolve($file));
        if ($contents === false) {
            throw new Exception("Could not read $file!");
        }
        return $contents;
    }

    /**
     * @psalm-taint-specialize
     */
    private function find(string $file): ?string
    {
        $file = str_replace('\\', '/', $file);
        if ($file === '' || $file[0] === '/' || preg_match('~(^|/)\.\.?(/|$)|[\0:]~', $file)) {
            throw new AssertionError("Invalid template name $file");
        }
        foreach ($this->directories as $dir) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }
}
