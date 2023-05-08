<?php

defined('YII_DEBUG') or define('YII_DEBUG', getenv('APP_ENV') !== 'production');
defined('YII_ENV') or define('YII_ENV', in_array(getenv('APP_ENV'), ['dev','development']) ? 'dev' : 'prod');

$ini = is_file(__DIR__.'/../fly.toml') ? file_get_contents(__DIR__.'/../fly.toml') :null;
$ini = $ini ? parse_ini_string(substr($ini, $start = strpos($ini, '[env]'), strpos($ini, '[experimental]') - $start)) : [];
$ini['GH_URL'] = 'https://api.github.com/repos';

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
    'params' => [
        'gh_token' => getenv('GH_TOKEN') ?: $ini['GH_TOKEN'] ?? '',
        'gh_url_invites' => $ini['GH_URL'] . (getenv('GH_URL_INVITES') ?: $ini['GH_URL_INVITES'] ?? ''),
        'gh_url_comments' => $ini['GH_URL'] . (getenv('GH_URL_COMMENTS') ?: $ini['GH_URL_COMMENTS'] ?? '') . '/comments',
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

