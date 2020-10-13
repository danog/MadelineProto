<?php

namespace danog\MadelineProto\PhpDoc;

use danog\MadelineProto\Logger;
use danog\MadelineProto\PhpDocBuilder;
use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class MethodDoc extends GenericDoc
{
    private Return_ $return;
    private string $psalmReturn;
    private array $params = [];
    private array $psalmParams = [];
    public function __construct(PhpDocBuilder $phpDocBuilder, ReflectionFunctionAbstract $method)
    {
        $this->builder = $phpDocBuilder;
        $this->name = $method->getName();
        $doc = $method->getDocComment();
        if (!$doc) {
            $this->ignore = true;
            if ($method instanceof ReflectionMethod) {
                Logger::log($method->getDeclaringClass()->getName().'::'.$method->getName().' has no PHPDOC!');
            } else {
                Logger::log($method->getName().' has no PHPDOC!');
            }
            return;
        }
        $doc = $this->builder->getFactory()->create($doc);

        parent::__construct($doc, $method instanceof ReflectionMethod ? $method->getDeclaringClass() : $method);

        foreach ($doc->getTags() as $tag) {
            if ($tag instanceof Param && !isset($this->params[$tag->getVariableName()])) {
                $this->params[$tag->getVariableName()] = [
                    $tag->getType(),
                    $tag->getDescription()
                ];
            } elseif ($tag instanceof Return_ && !isset($this->return)) {
                $this->return = $tag;
            } elseif ($tag instanceof Generic && $tag->getName() === 'psalm-return') {
                $this->psalmReturn = $tag;
            } elseif ($tag instanceof Generic && $tag->getName() === 'psalm-param') {
                [$type, $description] = \explode(" $", $tag->getDescription(), 2);
                $description .= ' ';
                [$varName, $description] = \explode(" ", $description, 2);
                if (!$description && isset($this->params[$varName])) {
                    $description = (string) $this->params[$varName][1];
                } else {
                    $description = new Description($description);
                }
                $this->psalmParams[$varName] = [
                    $type,
                    $description
                ];
            }
        }
    }

    public function getSignature(): string
    {
        $sig = $this->name;
        $sig .= "(";
        foreach ($this->params as $var => [$type, $description]) {
            $sig .= $type.' ';
            $sig .= "$".$var;
            $sig .= ', ';
        }
        $sig = \trim($sig, ', ');
        $sig .= ')';
        if (isset($this->return)) {
            $sig .= ': ';
            $sig .= $this->builder->resolveTypeAlias($this->className, $this->return);
        }
        return $sig;
    }
    public function format(): string
    {
        $sig = '### `'.$this->getSignature()."`";
        $sig .= "\n\n";
        $sig .= $this->title;
        $sig .= "\n";
        $sig .= $this->description;
        $sig .= "\n";
        if ($this->psalmParams || $this->params) {
            $sig .= "\nParameters:\n";
            foreach ($this->params as $name => [$type, $description]) {
                if ($type) {
                    $type = $this->builder->resolveTypeAlias($this->className, $type);
                }
                $sig .= "* `\$$name`: `$type` $description  \n";
                if (isset($this->psalmParams[$name])) {
                    [$psalmType] = $this->psalmParams[$name];
                    $psalmType = \trim(\str_replace("\n", "\n  ", $psalmType));

                    $sig .= "  Full type:\n";
                    $sig .= "  ```\n";
                    $sig .= "  $psalmType\n";
                    $sig .= "  ```\n";
                }
            }
            $sig .= "\n";
        }
        if (isset($this->return) && $this->return->getDescription() && $this->return->getDescription()->render()) {
            $sig .= "\nReturn value: ".$this->return->getDescription()."\n";
        }
        if (isset($this->psalmReturn)) {
            $sig .= "\nFully typed return value:\n```\n".$this->psalmReturn."\n```";
        }
        $sig .= $this->seeAlso();
        $sig .= "\n";
        return $sig;
    }
}
