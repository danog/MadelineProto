<?php

namespace danog\MadelineProto\PhpDoc;

use danog\MadelineProto\PhpDocBuilder;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Reference\Fqsen;
use phpDocumentor\Reflection\DocBlock\Tags\Reference\Url;
use phpDocumentor\Reflection\DocBlock\Tags\See;

abstract class GenericDoc
{
    /**
     * Builder instance.
     */
    protected PhpDocBuilder $builder;
    /**
     * Name.
     */
    protected string $name;
    /**
     * Title.
     */
    protected string $title;
    /**
     * Description.
     */
    protected Description $description;
    /**
     * See also array.
     *
     * @var array<string, See>
     */
    protected array $seeAlso = [];
    /**
     * Author.
     */
    protected Author $author;
    /**
     * Ignore this class.
     */
    protected bool $ignore = false;
    /**
     * Class name.
     */
    protected string $className;
    /**
     * Class namespace.
     */
    protected string $namespace;
    public function __construct(DocBlock $doc, $reflectionClass)
    {
        $this->className = $reflectionClass->getName();
        $this->namespace = \str_replace('/', '\\', \dirname(\str_replace('\\', '/', $this->className)));
        $this->title = $doc->getSummary();
        $this->description = $doc->getDescription();
        $tags = $doc->getTags();

        $this->author = new Author("Daniil Gentili", "daniil@daniil.it");
        foreach ($tags as $tag) {
            if ($tag instanceof Author) {
                $this->author = $tag;
            }
            if ($tag instanceof Deprecated) {
                $this->ignore = true;
                break;
            }
            if ($tag instanceof Generic && $tag->getName() === 'internal') {
                $this->ignore = true;
                break;
            }
            if ($tag instanceof See) {
                $this->seeAlso[$tag->getReference()->__toString()] = $tag;
            }
        }
    }

    public function seeAlso(): string
    {
        $seeAlso = '';
        foreach ($this->seeAlso as $see) {
            $ref = $see->getReference();
            if ($ref instanceof Fqsen) {
                $ref = (string) $ref;
                $ref = $this->builder->resolveTypeAlias($this->className, $ref);
                $refExpl = \explode("\\", $ref);
                $name = \array_pop($refExpl);
                $namespace = \explode('/', $this->namespace);
                $count = \count($refExpl);
                foreach ($namespace as $k => $name) {
                    if (isset($refExpl[$k]) && $refExpl[$k] === $name) {
                        $count--;
                    } else {
                        break;
                    }
                }

                $ref = \str_repeat('../', $count)
                    .\implode('/', \array_slice($refExpl, $count))
                    ."/$name.md";

                $desc = $see->getDescription() ?: $ref;
                $seeAlso .= "* [$desc]($ref)\n";
            }
            if ($ref instanceof Url) {
                $desc = $see->getDescription() ?: $ref;
                $seeAlso .= "* [$desc]($ref)\n";
            }
        }
        if ($seeAlso) {
            $seeAlso = "\n#### See also: \n$seeAlso\n\n";
        }
        return $seeAlso;
    }
    public function format(): string
    {
        $seeAlso = $this->seeAlso();
        return <<<EOF
        ---
        title: $this->name: $this->title
        description: $this->description
        image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
        ---
        # `$this->name`: $this->title
        [Back to API index](index.md)

        > Author: $this->author  

        $this->description
        $seeAlso
        EOF;
    }

    public function shouldIgnore(): bool
    {
        return $this->ignore;
    }
}
