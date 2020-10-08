<?php

namespace danog\MadelineProto\PhpDoc;

use danog\MadelineProto\Logger;
use danog\MadelineProto\PhpDocBuilder;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use ReflectionMethod;

class MethodDoc extends GenericDoc
{
    private $return;
    private array $params = [];
    public function __construct(ReflectionMethod $method)
    {
        $doc = $method->getDocComment();
        if (!$doc) {
            $this->ignore = true;
            Logger::log($method->getDeclaringClass()->getName().'::'.$method->getName().' has no PHPDOC!');
            return;
        }
        $doc = PhpDocBuilder::$factory->create($doc);

        parent::__construct($doc);

        foreach ($doc->getTags() as $tag) {
            if ($tag instanceof Param && !isset($this->params[$tag->getVariableName()])) {
                $this->params[$tag->getVariableName()] = $tag;
            } elseif ($tag instanceof Return_ && !$this->return) {
                $this->return = $tag;
            } elseif ($tag instanceof Generic && $tag->getName() === 'psalm-return') {
                $this->return = $tag;
            } elseif ($tag instanceof Generic && $tag->getName() === 'psalm-param') {
                [$type, $description] = \explode(" $", $tag->getDescription(), 2);
                $description .= ' ';
                [$varName, $description] = \explode(" ", $description, 2);
                if (!$description && isset($this->params[$varName])) {
                    $description = $this->params[$varName]->getDescription();
                } else {
                    $description = new Description($description);
                }
                $this->params[$varName] = [
                    $type,
                    $description
                ];
            }
        }
    }

    public function render(): string
    {
        return '';
    }
}
