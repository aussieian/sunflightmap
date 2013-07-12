<?php

$config_name = str_replace("www.", "", $_SERVER["HTTP_HOST"]);
@include_once("lib/config-" . $config_name . ".php");
@include_once("../lib/config-" . $config_name . ".php");
@include_once("../../lib/config-" . $config_name . ".php");

?>
