<?php

namespace danog\MadelineProto\PhpDoc;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\See;

abstract class GenericDoc
{
    /**
     * Title.
     */
    private string $title;
    /**
     * Description.
     */
    private Description $description;
    /**
     * See also array.
     *
     * @var array<string, string>
     */
    private array $seeAlso = [];
    /**
     * Author.
     */
    private Author $author;
    /**
     * Ignore this class.
     */
    protected bool $ignore = false;
    public function __construct(DocBlock $doc)
    {
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
                $this->seeAlso[$tag->getReference()->__toString()] = $tag->render();
            }
        }
    }

    public function format(): string
    {
        return '';
    }

    public function shouldIgnore(): bool
    {
        return $this->ignore;
    }
}
