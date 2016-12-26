<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../vendor/yiisoft/yii/framework/yiit.php';

Yii::setPathOfAlias('qsextra', __DIR__ . '/../lib');

Yii::createConsoleApplication([
    'name' => 'Qs Extra tests',
    'basePath' => __DIR__ . '/_output',
]);
