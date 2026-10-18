<?php declare(strict_types=1);

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

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;
use danog\MadelineProto\Tools;
use danog\MadelineProto\WebUI\TemplateEngine;

/**
 * Web template settings for the login and status pages.
 *
 * The pages are rendered by native PHP templates (see {@see TemplateEngine}): to customize them, copy the
 * templates you want to change from the builtin template directory ({@see TemplateEngine::BUILTIN_DIRECTORY})
 * to a directory of yours, and add it with {@see self::addDirectory()}.
 *
 * @psalm-external-mutation-free
 */
final class Templates extends SettingsAbstract
{
    /**
     * Default value of the legacy sprintf template.
     */
    public const LEGACY_HTML_TEMPLATE = '<!DOCTYPE html><html><head><title>MadelineProto</title></head><body><h1>MadelineProto</h1><p>%s</p><form method="POST">%s<button type="submit"/>%s</button></form>%s</body></html>';

    /**
     * Legacy sprintf web template.
     */
    protected string $htmlTemplate = self::LEGACY_HTML_TEMPLATE;

    /**
     * Template directories, searched in order before the builtin one.
     *
     * @var list<string>
     */
    protected array $directories = [];

    private ?TemplateEngine $engine = null;

    /**
     * Get the template directories, searched in order before the builtin one.
     *
     * @return list<string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Set the template directories, searched in order before the builtin one.
     *
     * A directory only needs to contain the templates it overrides.
     *
     * @param list<string> $directories Template directories
     */
    public function setDirectories(array $directories): self
    {
        $this->directories = array_values(array_map(Tools::absolute(...), $directories));
        $this->engine = null;

        return $this;
    }

    /**
     * Add a template directory, searched before the ones that were already added and the builtin one.
     *
     * A directory only needs to contain the templates it overrides.
     *
     * @param string $directory Template directory
     */
    public function addDirectory(string $directory): self
    {
        return $this->setDirectories([$directory, ...$this->directories]);
    }

    /**
     * Get the template engine.
     *
     * @internal
     */
    public function getEngine(): TemplateEngine
    {
        return $this->engine ??= new TemplateEngine($this->directories);
    }

    /**
     * Get the legacy sprintf web template.
     *
     * @deprecated Use template directories instead, see {@see self::addDirectory()}.
     */
    public function getHtmlTemplate(): string
    {
        return $this->htmlTemplate;
    }

    /**
     * Set a legacy sprintf web template, used instead of the template directories.
     *
     * The template gets four strings: the title and messages, the form fields, the label of the submit button,
     * and the trailing HTML (scripts and the QR code login panel).
     *
     * @deprecated Use template directories instead, see {@see self::addDirectory()}.
     *
     * @param string $htmlTemplate Legacy sprintf web template.
     */
    public function setHtmlTemplate(string $htmlTemplate): self
    {
        $this->htmlTemplate = $htmlTemplate;

        return $this;
    }

    /**
     * Whether a legacy sprintf web template was set.
     *
     * @internal
     */
    public function hasLegacyHtmlTemplate(): bool
    {
        return $this->htmlTemplate !== self::LEGACY_HTML_TEMPLATE;
    }
}
