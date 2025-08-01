<?php

return [
    'key' => 'q',
    'case_sensitive' => false,
    'fulltext' => true,
    'min_search_length' => 2,
    'operator' => 'LIKE',
    'operator_map' => [
        '%' => 'LIKE',
        '=' => '=',
        '!' => '!=',
        '>' => '>',
        '<' => '<',
        '^' => '^', // 'STARTS_WITH',
        '$' => '$', // 'ENDS_WITH',
        '@' => '@', // 'JSON_CONTAINS',
        '~' => '~', // 'FULLTEXT',
    ],
];
