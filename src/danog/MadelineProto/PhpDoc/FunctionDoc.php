<?php

namespace danog\MadelineProto\PhpDoc;

use danog\MadelineProto\Logger;
use danog\MadelineProto\PhpDocBuilder;
use ReflectionFunction;

class FunctionDoc extends MethodDoc
{
    public function __construct(PhpDocBuilder $builder, ReflectionFunction $reflectionClass)
    {
        $this->builder = $builder;
        $this->nameGenericDoc = $reflectionClass->getName();
        $doc = $reflectionClass->getDocComment();
        if (!$doc) {
            Logger::log($reflectionClass->getName()." has no PHPDOC");
            $this->ignore = true;
            return;
        }
        $doc = $this->builder->getFactory()->create($doc);

        parent::__construct($builder, $reflectionClass);
    }
}
