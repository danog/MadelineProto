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
use danog\MadelineProto\Lang;
use Stringable;
use Throwable;

/**
 * A template being rendered: `$this` inside a .phtml template.
 *
 * Templates can extend a layout and fill its sections:
 *
 * ```php
 * <?php $this->layout('layout', ['title' => $page->title]) ?>
 * <?php $this->start('head') ?><style>...</style><?php $this->stop() ?>
 * <p>The rest of the output becomes the "content" section of the layout.</p>
 * ```
 *
 * Use {@see self::e()} to output any value: strings are escaped, {@see SafeHtml} is output as-is.
 */
final class Template
{
    /** @var ?array{string, array<string, mixed>} */
    private ?array $layout = null;
    /** @var list<string> */
    private array $sectionStack = [];

    /**
     * @param array<string, string> $sections Sections filled by the templates extending this one
     *
     * @internal
     */
    public function __construct(
        private readonly TemplateEngine $engine,
        private readonly string $name,
        private array $sections = [],
    ) {
    }

    /**
     * Render the template.
     *
     * @param array<string, mixed> $data Variables passed to the template
     *
     * @internal
     */
    public function render(array $data): string
    {
        $file = $this->engine->resolve($this->name.'.phtml');
        $level = ob_get_level();
        ob_start();
        try {
            $this->includeTemplate($file, $data);
            if ($this->sectionStack) {
                throw new AssertionError("Unclosed section {$this->sectionStack[0]} in {$this->name}");
            }
            $content = ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        if ($this->layout === null) {
            return $content;
        }
        [$layout, $layoutData] = $this->layout;
        $this->sections['content'] ??= $content;
        return (new self($this->engine, $layout, $this->sections))->render($layoutData + $data);
    }

    /**
     * Get the template engine.
     */
    public function getEngine(): TemplateEngine
    {
        return $this->engine;
    }

    /**
     * Extend a layout: the output of this template becomes its `content` section.
     *
     * @param array<string, mixed> $data Additional variables for the layout
     */
    public function layout(string $name, array $data = []): void
    {
        $this->layout = [$name, $data];
    }

    /**
     * Start capturing a section.
     */
    public function start(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    /**
     * Stop capturing the current section.
     */
    public function stop(): void
    {
        $name = array_pop($this->sectionStack) ?? throw new AssertionError("No section was started in {$this->name}");
        // Sections filled by a child template take precedence over the defaults of the parent
        $content = ob_get_clean();
        $this->sections[$name] ??= $content;
    }

    /**
     * Output a section.
     *
     * @param string $default Default HTML if the section was not filled
     */
    public function section(string $name, string $default = ''): SafeHtml
    {
        return new SafeHtml($this->sections[$name] ?? $default);
    }

    /**
     * Check whether a section was filled.
     */
    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    /**
     * Render another template (partial) in place.
     *
     * @param array<string, mixed> $data Variables passed to the template
     */
    public function insert(string $name, array $data = []): SafeHtml
    {
        return new SafeHtml($this->engine->render($name, $data));
    }

    /**
     * Get the contents of an asset, to inline it in a `<style>` or `<script>` tag.
     */
    public function asset(string $file): SafeHtml
    {
        return new SafeHtml($this->engine->asset($file));
    }

    /**
     * Get the contents of a script asset, to inline it in a `<script>` tag.
     *
     * A `</script` in the script (in a string literal, or in a comment) would close the tag early, and a
     * `<!--` followed by `<script` would keep the real `</script>` from closing it: they're escaped to
     * `<\/script` and `<\!--`, which mean the same in JavaScript strings and comments.
     */
    public function script(string $file): SafeHtml
    {
        return new SafeHtml(self::escapeScript($this->engine->asset($file)));
    }

    /**
     * Escape a script to inline it in a `<script>` tag, see {@see self::script()}.
     */
    public static function escapeScript(string $script): string
    {
        return str_replace('<!--', '<\\!--', str_ireplace('</script', '<\\/script', $script));
    }

    /**
     * Escape a value for output in HTML text or in a quoted attribute.
     */
    public function e(mixed $value): string
    {
        if ($value instanceof SafeHtml) {
            return $value->html;
        }
        if ($value === null || $value === false) {
            return '';
        }
        if (\is_scalar($value) || $value instanceof Stringable) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        }
        throw new AssertionError('Cannot output a value of type '.get_debug_type($value));
    }

    /**
     * Build an HTML attribute list: `true` outputs just the name, `null` and `false` skip the attribute.
     *
     * @param array<string, scalar|Stringable|null> $attributes
     */
    public function attrs(array $attributes): SafeHtml
    {
        $result = '';
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $result .= ' '.$this->e($name);
            if ($value !== true) {
                $result .= '="'.$this->e($value).'"';
            }
        }
        return new SafeHtml($result);
    }

    /**
     * Translate a string, escaping it.
     *
     * @param string|int|float ...$args sprintf arguments
     */
    public function t(string $key, string|int|float ...$args): string
    {
        return $this->e(self::translate($key, ...$args));
    }

    /**
     * Translate a string that contains trusted HTML (like links), escaping only the arguments.
     *
     * @param string|int|float ...$args sprintf arguments
     */
    public function tHtml(string $key, string|int|float ...$args): SafeHtml
    {
        return new SafeHtml(self::translate(
            $key,
            ...array_map(fn (string|int|float $arg): string|int|float => \is_string($arg) ? $this->e($arg) : $arg, $args),
        ));
    }

    /**
     * Translate a string.
     *
     * @param string|int|float ...$args sprintf arguments
     */
    public static function translate(string $key, string|int|float ...$args): string
    {
        $string = Lang::$current_lang[$key] ?? Lang::$lang['en'][$key] ?? $key;
        return $args ? \sprintf($string, ...$args) : $string;
    }

    private function includeTemplate(string $__file, array $__data): void
    {
        extract($__data, EXTR_SKIP);
        include $__file;
    }
}
