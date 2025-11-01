<?php

return array(
    'utf_mode' =>
        array(
            'value' => true,
            'readonly' => true,
        ),
    'cache_flags' =>
        array(
            'value' =>
                array(
                    'config_options' => 3600,
                    'site_domain' => 3600,
                ),
            'readonly' => false,
        ),
    'cookies' =>
        array(
            'value' =>
                array(
                    'secure' => false,
                    'http_only' => true,
                ),
            'readonly' => false,
        ),
    'exception_handling' =>
        array(
            'value' =>
                array(
                    'debug' => true,
                    'handled_errors_types' => 4437,
                    'exception_errors_types' => 4437,
                    'ignore_silence' => false,
                    'assertion_throws_exception' => true,
                    'assertion_error_type' => 256,
                    'log' => NULL,
                ),
            'readonly' => false,
        ),
    'connections' =>
        array(
            'value' =>
                array(
                    'default' =>
                        array(
                            'className' => '\\Bitrix\\Main\\DB\\MysqliConnection',
                            'host' => 'localhost',
                            'database' => 'ailyavo_common',
                            'login' => 'ailyavo_common',
                            'password' => '5tnaraG',
                            'options' => 2,
                        ),
                ),
            'readonly' => true,
        ),
    'crypto' =>
        array(
            'value' =>
                array(
                    'crypto_key' => 'd00e563ce9d0e3d1bb128ee415012ca0',
                ),
            'readonly' => true,
        ),
);
