<?php
/**
 * PhpDocBuilder module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\Async\AsyncConstruct;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Files\Server;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\GarbageCollector;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PasswordCalculator;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use danog\MadelineProto\PhpDoc\ClassDoc;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\TL\TLConstructors;
use danog\MadelineProto\TL\TLMethods;
use danog\MadelineProto\TON\ADNLConnection;
use danog\MadelineProto\TON\APIFactory as TAPIFactory;
use danog\MadelineProto\TON\InternalDoc as TInternalDoc;
use danog\MadelineProto\TON\Lite;
use HaydenPierce\ClassFinder\ClassFinder;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionMethod;

class PhpDocBuilder
{
    const DISALLOW = [
        AnnotationsBuilder::class,
        APIFactory::class,
        APIWrapper::class,
        AbstractAPIFactory::class,
        Bug74586Exception::class,
        Connection::class,
        ContextConnector::class,
        DataCenter::class,
        DataCenterConnection::class,
        DoHConnector::class,
        DocsBuilder::class,
        InternalDoc::class,
        Lang::class,
        LightState::class,
        Magic::class,
        PhpDocBuilder::class,
        RSA::class,
        Serialization::class,
        SessionPaths::class,
        SettingsEmpty::class,
        SettingsAbstract::class,
        Snitch::class,
        AsyncConstruct::class,
        Server::class, // Remove when done
        VoIP::class,

        Crypt::class,
        NothingInTheSocketException::class,

        GarbageCollector::class,
        MinDatabase::class,
        PasswordCalculator::class,
        ReferenceDatabase::class,
        UpdatesState::class,

        TL::class,
        TLConstructors::class,
        TLMethods::class,
        TLCallback::class,

        ADNLConnection::class,
        TAPIFactory::class,
        TInternalDoc::class,
        Lite::class,

        \ArrayIterator::class,
    ];
    public static DocBlockFactory $factory;
    private string $output;
    public function __construct(string $output)
    {
        self::$factory = DocBlockFactory::createInstance();
        $this->output = $output;
    }
    public function run()
    {
        $classes = ClassFinder::getClassesInNamespace('danog\\MadelineProto', ClassFinder::RECURSIVE_MODE);
        foreach ($classes as $class) {
            if (\in_array($class, self::DISALLOW) || str_starts_with($class, 'danog\\MadelineProto\\Ipc')
            || str_starts_with($class, 'danog\\MadelineProto\\Loop\\Update')
            || str_starts_with($class, 'danog\\MadelineProto\\Loop\\Connection')
            || str_starts_with($class, 'danog\\MadelineProto\\MTProto\\')
            || str_starts_with($class, 'danog\\MadelineProto\\MTProtoSession\\')
            || str_starts_with($class, 'danog\\MadelineProto\\PhpDoc\\')
            || str_starts_with($class, 'danog\\MadelineProto\\Db\\NullCache')) {
                continue;
            }
            $class = new ReflectionClass($class);
            if ($class->isTrait()) {
                continue;
            }
            $this->generate($class);
        }
        $this->generate(new ReflectionClass(DbPropertiesTrait::class));
    }

    /**
     * Create directory recursively.
     *
     * @param string $file
     * @return string
     */
    private static function createDir(string $file): string
    {
        $dir = \dirname($file);
        if (!\file_exists($dir)) {
            self::createDir($dir);
            \mkdir($dir);
        }
        return $file;
    }

    private function generate(ReflectionClass $class): void
    {
        $name = $class->getName();
        $fName = $this->output;
        $fName .= \str_replace(['\\', 'danog\\MadelineProto'], ['/', ''], $name);
        $fName .= '.md';
        $handle = \fopen(self::createDir($fName), 'w+');

        $class = new ClassDoc($class);
        /*
        \fwrite($handle, "---\n");
        \fwrite($handle, "title: $name: $title\n");
        \fwrite($handle, "description: $description\n");
        \fwrite($handle, "image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png\n");
        \fwrite($handle, "---\n");
        \fwrite($handle, "# $name: $title\n");
        \fwrite($handle, "[Back to API index](index.md)\n\n");

        \fwrite($handle, "> Author: $author  \n");
*/
    }
}
