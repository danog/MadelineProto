<?php

if (!\class_exists('ReflectionGenerator')) {
    class ReflectionGenerator
    {
        private $generator;
        public function __construct(Generator $generator)
        {
            $this->generator = $generator;
        }
        public function getExecutingFile(): string
        {
            return '';
        }
        public function getExecutingGenerator(): Generator
        {
            return $this->generator;
        }
        public function getExecutingLine(): int
        {
            return 0;
        }
        public function getFunction(): ReflectionFunctionAbstract
        {
            return new ReflectionFunction(function () {});
        }
        public function getThis(): object
        {
            return $this;
        }
        public function getTrace(int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT): array
        {
            return [];
        }
    }
}
