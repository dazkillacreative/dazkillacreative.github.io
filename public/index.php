<?php

defined('YII_DEBUG') or define('YII_DEBUG', getenv('APP_ENV') !== 'production');
defined('YII_ENV') or define('YII_ENV', in_array(getenv('APP_ENV'), ['dev','development']) ? 'dev' : 'prod');


require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../vendor/yiisoft/yii2/Yii.php';

$app = new yii\web\Application([
    'id' => 'app',
    'basePath' => __DIR__.'/..',
    'controllerNamespace' => 'app\\src',
    'bootstrap' => ['log','debug'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'request' => [
            // 'baseUrl' => 'https://crimson-resonance-1684.fly.dev',
            'cookieValidationKey' => '12345678',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'invite' => 'site/invite',
            ]
        ],
    ],

    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            'allowedIPs' => ['*'],
            'historySize' => 200,
            // 'checkAccessCallback' => fn() => false,
            'panels' => [
                'httpc' => 'yii\httpclient\debug\HttpClientPanel',
                'user' => [
                    'class' => 'yii\debug\panels\UserPanel',
                    'ruleUserSwitch' => [
                        'allow' => true,
                        'matchCallback' => function() {
                            return true;
                        },
                    ],
                ],
            ],  
        ]
    ]   
]);
$app->run();

