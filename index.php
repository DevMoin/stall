<?php
require_once 'fn.php';
$commander = new Commands();
$commandKeys = getCommandsKeys($commander);


call_user_func([$commander, 'help']);