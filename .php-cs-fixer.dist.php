<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->name('*.php')
    ->ignoreUnreadableDirs();

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // Conjunto PSR-12 correcto
        '@PSR12' => true,
        'psr_autoloading' => true,
        // Reglas adicionales para consistencia
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_import_per_statement' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => false,
        'concat_space' => ['spacing' => 'one'],
        'binary_operator_spaces' => [ 'default' => 'align_single_space_minimal' ],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_order' => true,
        'return_type_declaration' => ['space_before' => 'one'],
        'single_blank_line_at_eof' => true,
        'no_trailing_whitespace' => true,
        'no_extra_blank_lines' => ['tokens' => ['extra']],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    ])
    ->setFinder($finder);
