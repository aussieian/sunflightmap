
<a href="http://ondemandtestharness.oag.com/CBWSTestHarnessPublic/#flightLookupRequest">oag test page</a><br>
<form method="POST" action="http://ondemandtestharness.oag.com/CBWSTestHarnessPublic//FlightLookupRequestAction.do?">
	actionForm <input type='textfield' name='actionForm' value='FlightLookupRequestForm'><br>
	inputPrefix <input type='textfield' name='inputPrefix' value='f_'><br>
	f_username <input type='textfield' name='f_username' value='THACK'><br>
	f_password <input type='textfield' name='f_password' value='THACK'><br>
	f_carrierCode <input type='textfield' name='f_carrierCode' value='JQ'><br>
	f_serviceNumber <input type='textfield' name='f_serviceNumber' value='7'><br>
	f_requestDate <input type='textfield' name='f_requestDate' value='2011-10-16'><br>
	f_requestTime <input type='textfield' name='f_requestTime' value='12:00:00'><br>
	<input type='submit' value="test OAG"><br>
</form>

<hr>

<form method="GET" action="/ajax/ajax-flight-route.php">
	carrier_code <input type='textfield' name='carrier_code' value='JQ'><br>
	service_number <input type='textfield' name='service_number' value='7'><br>
	request_date <input type='textfield' name='request_date' value='2011-10-14'><br>
	<input type='submit' value="test ajax"><br>
</form>

