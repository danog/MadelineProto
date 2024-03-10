<?php

$config = new class extends Amp\CodeStyle\Config {
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'void_return' => true,
            'array_indentation' => true,
            'ternary_to_null_coalescing' => true,
            'assign_null_coalescing_to_coalesce_equal' => true,
            '@PHP82Migration' => true,
            '@PHP81Migration' => true,
            '@PHP80Migration' => true,
            '@PHP80Migration:risky' => true,
            'static_lambda' => true,
            'strict_param' => true,
            "native_function_invocation" => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
        ]);
    }
};

$config->getFinder()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/examples')
    ->in(__DIR__ . '/tools')
    ->notName('TLParser.php')
    ->notName('SecretTLParser.php');

$cacheDir = getenv('TRAVIS') ? getenv('HOME') . '/.php-cs-fixer' : __DIR__;

$config->setCacheFile($cacheDir . '/.php_cs.cache');

return $config;
