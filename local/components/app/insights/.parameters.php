<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    'SEF_MODE' => [
        'list'  => [
            'NAME'      => 'Списки',
            'DEFAULT'   => '',
            'VARIABLES' => [],
        ],
        'group' => [
            'NAME'      => 'Группа',
            'DEFAULT'   => 'group/#GROUP_ID#/',
            'VARIABLES' => ['GROUP_ID'],
        ],
    ],
    'CACHE_TIME' => ['DEFAULT' => 0],
];
