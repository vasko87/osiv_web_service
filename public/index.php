<?php

use DbService\Request;
use DbService\ControllerManager;

$config = [];
require_once '../bootstrap.php';

(new ControllerManager($config))->execute(new Request())->flush();
