<?php

require("config.php");

$cmd = $cfg["SFCALC_CMD"] . " 25.252778,55.364444 51.4775,-0.461389 450 2013-07-13 02:05:00 4";

$output = (exec($cmd));

print("Output from cmd: " . $cmd . "<br>");
print($output);


?>