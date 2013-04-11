<a href="http://ondemandtestharness.oag.com/CBWSTestHarnessPublic/#flightLookupRequest">oag test page</a><br>
<form method="POST" action="http://ondemandtestharness.oag.com/CBWSTestHarnessPublic//FlightLookupRequestAction.do?">
	actionForm <input type='textfield' name='actionForm' value='FlightLookupRequestForm'><br>
	inputPrefix <input type='textfield' name='inputPrefix' value='f_'><br>
	f_username <input type='textfield' name='f_username' value='GetFlight'><br>
	f_password <input type='textfield' name='f_password' value='tdrtkp2'><br>
	f_carrierCode <input type='textfield' name='f_carrierCode' value='QF'><br>
	f_serviceNumber <input type='textfield' name='f_serviceNumber' value='1'><br>
	f_requestDate <input type='textfield' name='f_requestDate' value='2013-06-14'><br>
	f_requestTime <input type='textfield' name='f_requestTime' value='12:00:00'><br>
	<input type='submit' value="test OAG"><br>
</form>

<hr>

<form method="GET" action="/ajax/ajax-flight-route.php">
	carrier_code <input type='textfield' name='carrier_code' value='QF'><br>
	service_number <input type='textfield' name='service_number' value='1'><br>
	request_date <input type='textfield' name='request_date' value='2013-06-14'><br>
	<input type='submit' value="test ajax"><br>
</form>

