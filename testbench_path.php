<?php
require __DIR__.'/vendor/autoload.php';

// This will give you the real Application instance Testbench uses
$app = require __DIR__.'/vendor/orchestra/testbench-core/laravel/bootstrap/app.php';

// Now resolve the @testbench base path
echo $app->basePath('@testbench'), PHP_EOL;
