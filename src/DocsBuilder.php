<?php

declare(strict_types=1);

/**
 * DocsBuilder module.
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

namespace danog\MadelineProto;

use danog\MadelineProto\DocsBuilder\Constructors;
use danog\MadelineProto\DocsBuilder\Methods;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;

// This code was written a few years ago: it is garbage, and has to be rewritten
final class DocsBuilder
{
    const DEFAULT_TEMPLATES = [
        'User' => ['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputDialogPeer', 'DialogPeer', 'InputPeer', 'NotifyPeer', 'InputNotifyPeer'],
        'ReplyMarkup' => ['ReplyMarkup'],
        'InputFile' => ['InputFile', 'InputEncryptedFile'],
        'InputEncryptedChat' => ['InputEncryptedChat'],
        'PhoneCall' => ['PhoneCall'],
        'InputPhoto' => ['InputPhoto'],
        'InputDocument' => ['InputDocument'],
        'InputMedia' => ['InputMedia'],
        'InputMessage' => ['InputMessage'],
        'KeyboardButton' => ['KeyboardButton'],
        'InputCheckPasswordSRP' => ['InputCheckPasswordSRP'],
        'Updates' => ['Updates'],
    ];
    use Methods;
    use Constructors;
    public $td = false;
    protected array $settings;
    protected string $index;
    protected Logger $logger;
    protected TL $TL;
    protected array $tdDescriptions;
    public function __construct(Logger $logger, array $settings)
    {
        $this->logger = $logger;
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        /** @psalm-suppress InvalidArgument */
        $this->TL = new TL(null);
        $new = new TLSchema;
        $new->mergeArray($settings);
        $this->TL->init($new);
        if (isset($settings['tl_schema']['td']) && !isset($settings['tl_schema']['telegram'])) {
            $this->td = true;
        }
        $this->settings = $settings;
        if (!\file_exists($this->settings['output_dir'])) {
            \mkdir($this->settings['output_dir']);
        }
        \chdir($this->settings['output_dir']);
        $this->index = $settings['readme'] ? 'README.md' : 'index.md';

        foreach (\glob($this->settings['template'].'/*') as $template) {
            $this->templates[\basename($template, '.md')] = \file_get_contents($template);
        }
    }
    /**
     * Documentation templates.
     *
     */
    protected array $templates = [];

    protected static function markdownEscape(string $s): string
    {
        return \str_replace('_', '\\_', $s);
    }
    public $types = [];
    public $any = '*';
    public function mkDocs(): void
    {
        Logger::log('Generating documentation index...', Logger::NOTICE);
        \file_put_contents($this->index, $this->template('index', $this->settings['description']));

        $this->mkMethods();
        $this->mkConstructors();
        foreach (\glob('types/*') as $unlink) {
            \unlink($unlink);
        }
        if (\file_exists('types')) {
            \rmdir('types');
        }
        \mkdir('types');
        \ksort($this->types);
        $index = '';
        Logger::log('Generating types documentation...', Logger::NOTICE);
        foreach ($this->types as $otype => $keys) {
            $type = StrTools::typeEscape($otype);
            $index .= '['.self::markdownEscape($type).'](/API_docs/types/'.$type.'.md)<a name="'.$type.'"></a>  

';
            $constructors = '';
            foreach ($keys['constructors'] as $data) {
                $predicate = $data['predicate'].(isset($data['layer']) && $data['layer'] !== '' ? '_'.$data['layer'] : '');
                $md_predicate =  self::markdownEscape($predicate);
                $constructors .= "[$md_predicate](/API_docs/constructors/$predicate.md)  \n\n";
            }
            $methods = '';
            foreach ($keys['methods'] as $data) {
                $name = $data['method'];
                $md_name = \str_replace(['.', '_'], ['->', '\\_'], $name);
                $methods .= "[\$MadelineProto->$md_name](/API_docs/methods/$name.md)  \n\n";
            }
            $symFile = \str_replace('.', '_', $type);
            $redir = $symFile !== $type ? "\nredirect_from: /API_docs/types/{$symFile}.html" : '';
            $header = '';
            if (!isset($this->settings['td'])) {
                foreach (self::DEFAULT_TEMPLATES as $template => $types) {
                    if (\in_array($type, $types, true)) {
                        $header .= $this->template($template, $type);
                    }
                }
            }
            if (isset($this->tdDescriptions['types'][$otype])) {
                $header = "{$this->tdDescriptions['types'][$otype]}\n\n$header";
            }
            $header = \sprintf(
                $this->templates['Type'],
                $type,
                $redir,
                self::markdownEscape($type),
                $header,
                $constructors,
                $methods,
            );
            \file_put_contents('types/'.$type.'.md', $header);
        }
        Logger::log('Generating types index...', Logger::NOTICE);
        \file_put_contents('types/'.$this->index, $this->templates['types-index'].$index);

        Logger::log('Generating additional types...', Logger::NOTICE);
        foreach (['waveform', 'string', 'bytes', 'int', 'int53', 'long', 'int128', 'int256', 'int512', 'double', 'Bool', 'DataJSON', '!X'] as $type) {
            \file_put_contents("types/$type.md", $this->templates[$type]);
        }
        foreach (['boolFalse', 'boolTrue', 'null', 'photoStrippedSize'] as $constructor) {
            \file_put_contents("constructors/$constructor.md", $this->templates[$constructor]);
        }
        Logger::log('Done!', Logger::NOTICE);
    }
    public static function addToLang(string $key, string $value = '', bool $force = false): void
    {
        if (!isset(Lang::$lang['en'][$key]) || $force) {
            Lang::$lang['en'][$key] = $value;
        }
    }
    /**
     * Get formatted template string.
     *
     * @param string   $name      Template name
     * @param string   ...$params Params
     */
    protected function template(string $name, string ...$params): string
    {
        return \sprintf($this->templates[$name], ...$params);
    }
}
