<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto'    => true,
        '@Symfony' => true,

        'braces_position' => [
            'functions_opening_brace'           => 'next_line_unless_newline_at_signature_end',
            'classes_opening_brace'             => 'next_line_unless_newline_at_signature_end',
            'control_structures_opening_brace'  => 'same_line',
            'anonymous_functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'anonymous_classes_opening_brace'   => 'next_line_unless_newline_at_signature_end',
        ],

        'method_argument_space' => [
            'on_multiline'                     => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
            'after_heredoc'                    => true,
        ],

        'single_line_empty_body' => true,

        'method_chaining_indentation' => true,

        'binary_operator_spaces' => [
            'default'   => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],

        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],

        'operator_linebreak' => [
            'only_booleans' => false,
            'position'      => 'beginning',
        ],
    ])
    ->setFinder(
        (new Finder())
            ->in(__DIR__)
            ->exclude([
                'vendor',
                'var',
            ]),
    );
