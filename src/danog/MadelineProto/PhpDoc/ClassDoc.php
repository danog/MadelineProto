<?php

namespace danog\MadelineProto\PhpDoc;

use danog\MadelineProto\Logger;
use danog\MadelineProto\PhpDocBuilder;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;

class ClassDoc extends GenericDoc
{
    /**
     * Properties.
     *
     * @var array<string, PropertyDoc>
     */
    private array $properties = [];
    /**
     * Methods.
     *
     * @var array<string, MethodDoc>
     */
    private array $methods = [];
    public function __construct(ReflectionClass $reflectionClass)
    {
        $doc = $reflectionClass->getDocComment();
        if (!$doc) {
            Logger::log($reflectionClass->getName()." has no PHPDOC");
            $this->ignore = true;
            return;
        }
        $doc = PhpDocBuilder::$factory->create($doc);

        parent::__construct($doc);

        $tags = $doc->getTags();
        foreach ($tags as $tag) {
            if ($tag instanceof Property) {
                $this->properties[$tag->getVariableName()] = new PropertyDoc(
                    $tag->getName(),
                    $tag->getType(),
                    $tag->getDescription()
                );
            }
            if ($tag instanceof InvalidTag && $tag->getName() === 'property') {
                [$type, $description] = \explode(" $", $tag->render(), 2);
                $description .= ' ';
                [$varName, $description] = \explode(" ", $description, 2);
                $type = \str_replace('@property ', '', $type);
                $description ??= '';
                $this->properties[$varName] = new PropertyDoc(
                    $varName,
                    $type,
                    $description
                );
            }
        }
        $constants = [];
        foreach ($reflectionClass->getConstants() as $key => $value) {
            $refl = new ReflectionClassConstant($reflectionClass->getName(), $key);
            if (!$refl->isPublic()) {
                continue;
            }
            $description = '';
            if ($refl->getDocComment()) {
                $docConst = PhpDocBuilder::$factory->create($refl->getDocComment());
                if (\in_array($refl->getDeclaringClass()->getName(), PhpDocBuilder::DISALLOW)) {
                    continue;
                }
                $description .= $docConst->getSummary();
                if ($docConst->getDescription()) {
                    $description .= "\n\n";
                    $description .= $docConst->getDescription();
                }
                if ($docConst->getTagsByName('internal')) {
                    continue;
                }
            }
            $description = \trim($description);
            $constants[$key] = [
                $value,
                $description
            ];
        }


        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->getName(), '__') && $method !== '__construct') continue;
            $this->methods[$method->getName()] = new MethodDoc($method);
        }
    }

    public function format(): string
    {
    }
}
