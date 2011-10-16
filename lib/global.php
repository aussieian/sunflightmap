<?php

include_once("config.php");
include_once("airport_codes.php"); // todo: could move this to load on demand since its a large file

// global methods
function do_post_request($url, $data, $optional_headers = null)
{
  $params = array('http' => array(
              'method' => 'POST',
              'content' => $data
            ));
  if ($optional_headers !== null) {
    $params['http']['header'] = $optional_headers;
  }
  $ctx = stream_context_create($params);
  $fp = @fopen($url, 'rb', false, $ctx);
  if (!$fp) {
    throw new Exception("Problem with $url, $php_errormsg");
  }
  $response = @stream_get_contents($fp);
  if ($response === false) {
    throw new Exception("Problem reading data from $url, $php_errormsg");
  }
  return $response;
}

function getAiport($airport_code)
{
	global $openflights_airports;
	
	if (array_key_exists($airport_code, $openflights_airports)) {
		return $openflights_airports[$airport_code];
	}

	// not found
	return null;
}

?>