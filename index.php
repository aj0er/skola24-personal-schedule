<?php

namespace PersonalSchedule;

require_once "vendor/autoload.php";

use Framework\Main;

$main = new Main(new App(), "/schema2");
$main->handle();