<?php

require_once('OnDemandSoap.php') ; 

 $s = new OnDemandSoap();
 
$s->wsdl = 'http://ondemand.oag.com/CBWebServicePublic/CBWSPublicPort?wsdl'; 
$s->username = 'user-name' ; 
$s->password = 'password' ; 
$s->destinationCriteria = 'JFK'; 
$s->destinationCriteriaLocationType = 'A';
$s->originCriteria = 'LHR';
$s->originCriteriaLocationType = 'A' ; 
$s->requestDate = '2010-12-01';
$s->requestTime = '00:00:00' ; 
$s->requestDateEffectiveFrom = '2010-12-01' ; 
$s->requestDateEffectiveTo = '2011-01-31' ; 

 $s->showXML = false; 
 
 if(preg_match('/your-domain/i',$_SERVER['SERVER_NAME']) != 0 ){
			$config  = array(
			'trace'=>1,
			'CURLOPT_HEADER'=>1,'CURLOPT_SSL_VERIFYPEER'=>0,'
			CURLOPT_PROXY'=>'your-proxy:80','CURLOPT_HTTPPROXYTUNNEL'=>1,
			'proxy_host'=> 'your-proxy','proxy_port'=> 80); 
			
		$s->soapConfig  = 	$config ; 
	}
 
 try{
 
			 $s->getOnDemand();
			 
			 echo '<pre>'.$s->rawReturnResult.'</pre>';
			 
			 //echo 'Service Returned '.$s ; 
			 
 
 }
 catch(Exception $e){
		echo $e->getMessage();
 }
  //var_dump($s->rtnResult);
 
?>


OnDemandSoap.php:

<?php
class OnDemandSoap{

private $wsdl; 
private $username; 
private $password; 
private $soapConfig = array();
private $showXML = false ; 
private $rtnResult  = array();

private $destinationCriteriaLocationType  ;
private $destinationCriteria ; 
private $originCriteria ; 
private $originCriteriaLocationType ; 
private $requestDate;
private $requestTime;
private $rawReturnResult   ; 
private $requestDateEffectiveFrom;

function __construct(){}


public function getOnDemand(){

		$client = new SoapClient($this->wsdl,$this->soapConfig);
		
		$arg = array('arg0'=>array(
			'destinationCriteria'=>$this->destinationCriteria, 
			'destinationCriteriaLocationType'=>$this->destinationCriteriaLocationType,
			'originCriteria'=>$this->originCriteria, 
			'originCriteriaLocationType'=>$this->originCriteriaLocationType,
			'requestDate'=>$this->requestDate,
			'requestTime'=>$this->requestTime,
			'requestDateEffectiveFrom'=>$this->requestDateEffectiveFrom,
			'requestDateEffectiveTo'=>$this->requestDateEffectiveTo,
			'password'=>$this->password, 
			'username'=> $this->username)
			);
		

		//var_dump($arg);
		
		try{
		
			$result = $client->getDirectFlights($arg);
			
			$this->rawReturnResult = (string) $client->__last_response;
			
			
			
		}
		catch(Exception $e){
		
			throw $e; 
		}
}	

public function __set($key, $val){
	
	$this->$key = $val ; 

}

public function __get($key)
{
	return $this->$key;
}


}
	
?>

Notes: You will need to change the username / password variables to the ones you have for accessing the webservice in onDemandTestWrapper.php .

$s->username = 'user-name' ;
$s->password = 'password' ; 
