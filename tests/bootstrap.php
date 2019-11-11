<?php
declare(strict_types=1);


use Tracy\Debugger;


require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

@mkdir(__DIR__ . '/log');
Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/log');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/temp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));

@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);
ini_set('session.save_path', TEMP_DIR);
