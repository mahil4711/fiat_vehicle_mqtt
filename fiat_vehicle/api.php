<?php

require_once 'vendor/autoload.php';

use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use Psr\Http\Message\RequestInterface;
use Aws\CognitoIdentity\CognitoIdentityClient;
use GuzzleHttp\Exception\ClientException;

/**
 * apiFiat
 */
class apiFiat {

	private string			$fiatApiKey						= "3_mOx_J2dRgjXYCdyhchv3b5lhi54eBcdCTX4BI8MORqmZCoQWhA0mV2PTlptLGUQI";

	private string			$fiatWebSdkUrl					= "https://loginmyuconnect.fiat.com/accounts.webSdkBootstrap";

	private string			$fiatLoginUrl					= "https://loginmyuconnect.fiat.com/accounts.login";
	private int				$fiatLoginSessionExpiration;
	private string			$fiatLoginUser;
	private string			$fiatLoginPassword;
	private string			$fiatLoginPin;
	private string			$fiatLoginToken;
	private string			$fiatLoginUID;
	private					$fiatCookieJar;

	private string			$fiatJwtUrl						= "https://loginmyuconnect.fiat.com/accounts.getJWT";
	private string			$fiatJwtIdToken;

	private string			$awsAuthUrl						= "https://authz.sdpr-01.fcagcv.com/v2/cognito/identity/token";
	private string			$awsAuthXApiKey					= "qLYupk65UU1tw2Ih1cJhs4izijgRDbir2UFHA3Je";
	private string			$awsAuthClientRequestID;
	private string			$awsAuthIdentityID;
	private string			$awsAuthToken;

	private string			$awsCredentialsAccessKeyId;
	private string			$awsCredentialsExpiration;
	private string			$awsCredentialsSecretKey;
	private string			$awsCredentialsSessionToken		= "";
	private string			$awsCredentialsUserID;

	private string			$awsApiUrl  					= "https://channels.sdpr-01.fcagcv.com";
	private string			$awsService						= "execute-api";
	private string			$awsRegion						= "eu-west-1";

	private string			$pinAuthUrl						= "https://mfa.fcl-01.fcagcv.com";
	private string			$pinAuthApiKey					= "JWRYW7IYhW9v0RqDghQSx4UcRYRILNmc8zAuh5ys";
	private string			$pinAuthToken					= "";

	private array			$vehicles;
	private array			$vehicleStatus;

	private array			$logArray;



    public function __construct ( string $user, string $password, string $pin = "" ) {

        $this->fiatLoginUser 				= $user;
        $this->fiatLoginPassword 			= $password;
        $this->fiatLoginPin 				= $pin;

		$this->fiatLoginSessionExpiration	= 7776000;
		$this->awsAuthClientRequestID 		= $this->getRandomClientRequestID();

		$this->logArray						= array();
		$this->vehicles						= array();
		$this->vehicleStatus				= array();

		$this->fiatCookieJar				= tempnam('/tmp','cookieFiat');

		$this->appendToLogArray ( "Temporary cookie file created", $this->fiatCookieJar, 2 );

    }



	private function callApiFiatWebSDK () {

		$this->appendToLogArray ( "WebSDK", $this->fiatWebSdkUrl, 2, 1 );

		$curl = curl_init();

		curl_setopt_array($curl, array(

			CURLOPT_URL 			=> 	$this->fiatWebSdkUrl.'?apiKey='.$this->fiatApiKey,
			CURLOPT_RETURNTRANSFER 	=> 	true,
			CURLOPT_ENCODING 		=> 	'',
			CURLOPT_MAXREDIRS 		=> 	10,
			CURLOPT_TIMEOUT 		=> 	0,
			CURLOPT_FOLLOWLOCATION 	=> 	true,
			CURLOPT_HTTP_VERSION 	=> 	CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST 	=> 	'GET',
			CURLOPT_COOKIEJAR		=>	$this->fiatCookieJar,
			// CURLOPT_HTTPHEADER => array(
			//   'Cookie: ASP.NET_SessionId=til3lzkz3kdqas14lehwq0sm; gmid=gmid.ver4.AcbHk6LF-w.mSDIL-OQ0kWBFotQ2YipdQYB7E7-SJmCxvyOx17OTZbXPEMSsOhyWZ0kdDov9fOV.JA4W5L23nJwCCaRi9qTzVhDeJllPyrw5XNoMqGh51-vECLTZH_PTkjfG42orCYwsSXpUBCpC4mPQjfh1eVEHBg.sc3; hasGmid=ver4; ucid=vthVogPt1lCCjnSIR8M6nw'
			// ),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		$this->appendToLogArray ( "Response", $response, 2, 2 );

	}



	private function callApiFiatLogin () {

		$this->appendToLogArray ( "Fiat Login", $this->fiatLoginUrl, 2, 1 );

		$curl = curl_init();

		curl_setopt_array($curl, array(

			CURLOPT_URL 			=> 	$this->fiatLoginUrl,
			CURLOPT_RETURNTRANSFER 	=> 	true,
			CURLOPT_ENCODING 		=> 	'',
			CURLOPT_MAXREDIRS 		=> 	10,
			CURLOPT_TIMEOUT 		=> 	0,
			CURLOPT_FOLLOWLOCATION 	=> 	true,
			CURLOPT_HTTP_VERSION 	=> 	CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST 	=> 	'POST',
			CURLOPT_COOKIEFILE		=>	$this->fiatCookieJar,
			CURLOPT_POSTFIELDS 		=>	'loginID='.$this->fiatLoginUser.'&'.
										'password='.$this->fiatLoginPassword.'&'.
										'sessionExpiration='.$this->fiatLoginSessionExpiration.'&'.
										'APIKey='.$this->fiatApiKey.'&'.
										'pageURL=https%3A%2F%2Fmyuconnect.fiat.com%2Fde%2Fde%2Flogin&'.
										'sdk=js_latest&'.
										'sdkBuild=12234&'.
										'format=json&'.
										'targetEnv=jssdk&'.
										'include=profile%2Cdata%2Cemails%2Csubscriptions%2Cpreferences&'.
										'includeUserInfo=true&'.
										'loginMode=standard&'.
										'lang=de0de&'.
										'source=showScreenSet&'.
										'authMode=cookie',
			CURLOPT_HTTPHEADER 		=> 	array(

				'Content-Type: application/x-www-form-urlencoded',
				//'Cookie: ASP.NET_SessionId=til3lzkz3kdqas14lehwq0sm; gmid=gmid.ver4.AcbHk6LF-w.mSDIL-OQ0kWBFotQ2YipdQYB7E7-SJmCxvyOx17OTZbXPEMSsOhyWZ0kdDov9fOV.JA4W5L23nJwCCaRi9qTzVhDeJllPyrw5XNoMqGh51-vECLTZH_PTkjfG42orCYwsSXpUBCpC4mPQjfh1eVEHBg.sc3; hasGmid=ver4; ucid=vthVogPt1lCCjnSIR8M6nw'
			  
			
			),
		));
		
		$response = curl_exec($curl);
		
		curl_close($curl);
		$this->appendToLogArray ( "Response", $response, 2, 2 );
		
		$responseRaw = json_decode($response, true);

		if ( array_key_exists('sessionInfo', $responseRaw) ) {

		$this->fiatLoginToken = $responseRaw['sessionInfo']['login_token'];
		$this->fiatLoginUID = $responseRaw['UID'];
		
		$_SESSION['fiatLoginUID'] = $this->fiatLoginUID;

		$this->appendToLogArray ( "fiatLoginToken", $this->fiatLoginToken, 3, 2 );
		$this->appendToLogArray ( "fiatLoginUID", $this->fiatLoginUID, 3, 2 );
			
		}
	}



	private function callApiFiatJWT () {

		$this->appendToLogArray ( "Fiat JWT", $this->fiatJwtUrl, 2, 1 );

		$curl = curl_init();

		curl_setopt_array($curl, array(

			CURLOPT_URL 			=> 	$this->fiatJwtUrl.'?'.
										'APIKey='.$this->fiatApiKey.'&'.
										'pageURL=https://myuconnect.fiat.com/de/de/dashboard&'.
										'sdk=js_latest&'.
										'sdkBuild=12234&'.
										'format=json&'.
										'login_token='.$this->fiatLoginToken.'&'.
										'authMode=cookie&fields=profile.firstName,profile.lastName,profile.email,country,locale,data.disclaimerCodeGSDP',
			CURLOPT_RETURNTRANSFER 	=> 	true,
			CURLOPT_ENCODING 		=> 	'',
			CURLOPT_MAXREDIRS 		=> 	10,
			CURLOPT_TIMEOUT 		=> 	0,
			CURLOPT_FOLLOWLOCATION 	=> 	true,
			CURLOPT_HTTP_VERSION 	=> 	CURL_HTTP_VERSION_1_1,
			CURLOPT_COOKIEFILE		=>	$this->fiatCookieJar,
			CURLOPT_CUSTOMREQUEST 	=> 	'GET',
			//CURLOPT_HTTPHEADER => array(
			//  'Cookie: ASP.NET_SessionId=til3lzkz3kdqas14lehwq0sm; gmid=gmid.ver4.AcbHk6LF-w.mSDIL-OQ0kWBFotQ2YipdQYB7E7-SJmCxvyOx17OTZbXPEMSsOhyWZ0kdDov9fOV.JA4W5L23nJwCCaRi9qTzVhDeJllPyrw5XNoMqGh51-vECLTZH_PTkjfG42orCYwsSXpUBCpC4mPQjfh1eVEHBg.sc3; hasGmid=ver4; ucid=vthVogPt1lCCjnSIR8M6nw'
			//),
		  ));
		
		$response = curl_exec($curl);

		curl_close($curl);
		$this->appendToLogArray ( "Response", $response, 2, 2 );

		$responseRaw = json_decode($response, true);
		if ( $responseRaw['id_token'] ) {
		
			$this->fiatJwtIdToken = $responseRaw['id_token'];

			$this->appendToLogArray ( "fiatJwtIdToken", $this->fiatJwtIdToken, 3, 2 );

		}

	}




	private function callApiAmazonCognito () {

		if (	$this->fiatJwtIdToken &&
				$this->awsAuthClientRequestID &&
				$this->awsAuthXApiKey ) {

			$this->appendToLogArray ( "awsAuthUrl", $this->awsAuthUrl, 2, 1 );

			$curl = curl_init();

			curl_setopt_array($curl, array(

				CURLOPT_URL 			=> 	$this->awsAuthUrl,
				CURLOPT_RETURNTRANSFER 	=> 	true,
				CURLOPT_ENCODING 		=> 	'',
				CURLOPT_MAXREDIRS 		=> 	10,
				CURLOPT_TIMEOUT 		=> 	0,
				CURLOPT_FOLLOWLOCATION 	=> 	true,
				CURLOPT_HTTP_VERSION 	=> 	CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST 	=> 	'POST',
				CURLOPT_POSTFIELDS 		=>	'{

					"gigya_token": "'.$this->fiatJwtIdToken.'"

				}',
				CURLOPT_HTTPHEADER 		=> 	array(

					'x-clientapp-version: 1.0',
					'clientrequestid: '.$this->awsAuthClientRequestID,
					'X-Api-Key: '.$this->awsAuthXApiKey,
					'x-originator-type: web',
					'x-clientapp-name: CWP',
					'Content-Type: application/json'

				),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			$this->appendToLogArray ( "Response", $response, 2, 2 );

			$responseRaw = json_decode($response, true);

			if ( array_key_exists('IdentityId', $responseRaw)) {

				$this->awsAuthIdentityID = $responseRaw['IdentityId'];
				$this->awsAuthToken = $responseRaw['Token'];

				$this->appendToLogArray ( "awsAuthIdentityID", $this->awsAuthIdentityID, 3, 2 );
				$this->appendToLogArray ( "awsAuthToken", $this->awsAuthToken, 3, 2 );

			}
		}
	}




	private function callApiAmazonGetCredentials () {

		$this->appendToLogArray ( "cognitoidentity.GetCredentialsForIdentityOutput", $this->awsAuthUrl, 2, 1 );
		
		// The AWS client is relying on these being retrieved via `getenv` and DotEnv no longer sets via `putenv`
		putenv("AWS_ACCESS_KEY_ID=ABC");
		putenv("AWS_SECRET_ACCESS_KEY=DEF");
		
		$client = CognitoIdentityClient::factory(array(

			'region'  => $this->awsRegion,
			'version' => '2014-06-30'

		));
		
		if ( 	isset ( $this->awsAuthIdentityID ) &&
				isset ( $this->awsAuthToken ) ) {
		
			$response = $client->getCredentialsForIdentity(array(

				// IdentityId is required
				'IdentityId' => $this->awsAuthIdentityID,
				'Logins' => array(

					// Associative array of custom 'IdentityProviderName' key names
					'cognito-identity.amazonaws.com' => $this->awsAuthToken

				),
			));
			
			$this->appendToLogArray ( "Response", $response, 2, 2 );
			
			$posJSON = strpos ( $response, "{" );
			$responseRaw = json_decode( substr ($response, $posJSON), true);
			
			$Credentials = $responseRaw['Credentials'];
			
			$this->awsCredentialsAccessKeyId = $Credentials['AccessKeyId'];
			$this->awsCredentialsExpiration = $Credentials['Expiration'];
			$this->awsCredentialsSecretKey = $Credentials['SecretKey'];
			$this->awsCredentialsSessionToken = $Credentials['SessionToken'];

			$_SESSION['awsCredentialsAccessKeyId'] = $Credentials['AccessKeyId'];
			$_SESSION['awsCredentialsExpiration'] = $Credentials['Expiration'];
			$_SESSION['awsCredentialsSecretKey'] = $Credentials['SecretKey'];
			$_SESSION['awsCredentialsSessionToken'] = $Credentials['SessionToken'];
			
			$this->appendToLogArray ( "awsCredentialsAccessKeyId", $this->awsCredentialsAccessKeyId, 3, 2 );
			$this->appendToLogArray ( "awsCredentialsExpiration", $this->awsCredentialsExpiration, 3, 2 );
			$this->appendToLogArray ( "awsCredentialsSecretKey", $this->awsCredentialsSecretKey, 3, 2 );
			$this->appendToLogArray ( "awsCredentialsSessionToken", $this->awsCredentialsSessionToken, 3, 2 );

		}
	}



	public function checkValidityAmazonGetCredentials () {

		if ( strtotime($_SESSION['awsCredentialsExpiration']) < time () ) {

			return false;

		}
		else {

			return true;

		}
		
	}



	public function renewAmazonGetCredentialsIfNecessary () {

		if ( !isset($_SESSION) or !array_key_exists('awsCredentialsExpiration', $_SESSION)) {

			$_SESSION['awsCredentialsExpiration'] = "2000-01-01 00:00:00";

		}

		if (	strtotime($_SESSION['awsCredentialsExpiration']) < time () ||
				!isset ($_SESSION['fiatLoginUID']) ) {

			$this->refreshLogin();

		}
		else {
			
			$this->awsCredentialsAccessKeyId = $_SESSION['awsCredentialsAccessKeyId'];
			$this->awsCredentialsExpiration = $_SESSION['awsCredentialsExpiration'];
			$this->awsCredentialsSecretKey = $_SESSION['awsCredentialsSecretKey'];
			$this->awsCredentialsSessionToken = $_SESSION['awsCredentialsSessionToken'];
			$this->fiatLoginUID = $_SESSION['fiatLoginUID'];
			
		}
		
	}
	
	
	
	public function apiRequestVehicles () {

		$this->renewAmazonGetCredentialsIfNecessary();

		$this->appendToLogArray ( "Vehicles", $this->awsApiUrl."/v4/accounts/".$this->fiatLoginUID."/vehicles?stage=ALL", 3, 1 );

		if ( $this->awsCredentialsSessionToken ) {

			$request = new GuzzleHttp\Psr7\Request (
				'GET',
				$this->awsApiUrl."/v4/accounts/".$this->fiatLoginUID."/vehicles?stage=ALL",
				[  'Content-Type' => 'application/json',
				'x-clientapp-version' => '1.0',
				'clientrequestid' => '1592674815357357',
				'X-Api-Key' => $this->awsAuthXApiKey,
				'x-originator-type' => 'web',
				'locale' => 'de_de',
				'X-Amz-Security-Token' => $this->awsCredentialsSessionToken
			]
			);
			$signed_request = $this->sign($request, $this->awsCredentialsAccessKeyId, $this->awsCredentialsSecretKey);
		
			$client = new \GuzzleHttp\Client();
			$response = $client->send($signed_request);

			$this->appendToLogArray ( "Response", ($response->getBody()), 2, 2 );

			$responseRaw = json_decode( ($response->getBody()), true );

			$this->vehicles = $responseRaw['vehicles'];
			$this->awsCredentialsUserID = $responseRaw['userid'];

			// $this->appendToLogArray ( "Vehicles", implode(" ", $this->vehicles), 2, 2 );

		}
		else {

			$this->appendToLogArray ( "Failure", "awsCredentialsSessionToken not set", 5, 2 );

		}

	}



	public function apiRequestVehicleStatus ( $vin ) {

		$this->renewAmazonGetCredentialsIfNecessary();

		$this->appendToLogArray ( "Vehicle ".$vin, $this->awsApiUrl."/v2/accounts/".$this->fiatLoginUID."/vehicles/".$vin."/status", 3, 1 );

		
		$request = new GuzzleHttp\Psr7\Request(
			'GET',
			$this->awsApiUrl."/v2/accounts/".$this->fiatLoginUID."/vehicles/".$vin."/status",
			[  'Content-Type' => 'application/json',
			   'x-clientapp-version' => '1.0',
			   'clientrequestid' => '1592674815357357',
			   'X-Api-Key' => $this->awsAuthXApiKey,
			   'x-originator-type' => 'web',
			   'locale' => 'de_de',
			   'X-Amz-Security-Token' => $this->awsCredentialsSessionToken
		   ]
		);
		$signed_request = $this->sign($request, $this->awsCredentialsAccessKeyId, $this->awsCredentialsSecretKey);
   
		$client = new \GuzzleHttp\Client();
		$response = $client->send($signed_request);

		$this->appendToLogArray ( "StatusCode", ($response->getStatusCode()), 2, 2 );
		$this->appendToLogArray ( "ReasonPhrase", ($response->getReasonPhrase()), 2, 2 );
		$this->appendToLogArray ( "Response", ($response->getBody()), 2, 2 );

		$responseRaw = json_decode( ($response->getBody()), true );

		$this->vehicleStatus[$vin]['status'] = $responseRaw;
		
		$this->appendToLogArray ( "odometer", 			$this->vehicleStatus[$vin]['status']['vehicleInfo']['odometer']['odometer']['value']." ".$this->vehicleStatus[$vin]['status']['vehicleInfo']['odometer']['odometer']['unit'], 3, 2 );
		$this->appendToLogArray ( "daysToService", 		$this->vehicleStatus[$vin]['status']['vehicleInfo']['daysToService'], 3, 2 );
		$this->appendToLogArray ( "distanceToService",	$this->vehicleStatus[$vin]['status']['vehicleInfo']['distanceToService']['distanceToService']['value']." ".$this->vehicleStatus[$vin]['status']['vehicleInfo']['distanceToService']['distanceToService']['unit'], 3, 2 );
		
		$this->appendToLogArray ( "ignitionStatus", 	$this->vehicleStatus[$vin]['status']['evInfo']['ignitionStatus'], 3, 2 );
		$this->appendToLogArray ( "stateOfCharge", 		$this->vehicleStatus[$vin]['status']['evInfo']['battery']['stateOfCharge']." %", 3, 2 );
		$this->appendToLogArray ( "plugInStatus", 		$this->vehicleStatus[$vin]['status']['evInfo']['battery']['plugInStatus'], 3, 2 );
		$this->appendToLogArray ( "chargingStatus", 	$this->vehicleStatus[$vin]['status']['evInfo']['battery']['chargingStatus'], 3, 2 );
		$this->appendToLogArray ( "totalRange", 		$this->vehicleStatus[$vin]['status']['evInfo']['battery']['totalRange']." km", 3, 2 );
		$this->appendToLogArray ( "timestamp", 			date("d.m.Y H:i:s", $this->vehicleStatus[$vin]['status']['evInfo']['timestamp']/1000), 3, 2 );

	}



	public function apiRequestVehicleLocation ( $vin ) {

		$this->renewAmazonGetCredentialsIfNecessary();

		$this->appendToLogArray ( "Location ".$vin, $this->awsApiUrl."/v1/accounts/".$this->fiatLoginUID."/vehicles/".$vin."/location/lastknown", 3, 1 );

		$request = new GuzzleHttp\Psr7\Request(
			'GET',
			$this->awsApiUrl."/v1/accounts/".$this->fiatLoginUID."/vehicles/".$vin."/location/lastknown",
			[  'Content-Type' => 'application/json',
			   'x-clientapp-version' => '1.0',
			   'clientrequestid' => '1592674815357357',
			   'X-Api-Key' => $this->awsAuthXApiKey,
			   'x-originator-type' => 'web',
			   'locale' => 'de_de',
			   'X-Amz-Security-Token' => $this->awsCredentialsSessionToken
		   ]
		);
		$signed_request = $this->sign($request, $this->awsCredentialsAccessKeyId, $this->awsCredentialsSecretKey);
   
		$client = new \GuzzleHttp\Client();
		$response = $client->send($signed_request);

		$this->appendToLogArray ( "Response", ($response->getBody()), 2, 2 );

		$responseRaw = json_decode( ($response->getBody()), true );

		$this->vehicleStatus[$vin]['location'] = $responseRaw;

		$this->appendToLogArray ( "longitude", $this->vehicleStatus[$vin]['location']['longitude'], 3, 2 );
		$this->appendToLogArray ( "latitude", $this->vehicleStatus[$vin]['location']['latitude'], 3, 2 );
		$this->appendToLogArray ( "altitude", $this->vehicleStatus[$vin]['location']['altitude'], 3, 2 );
		$this->appendToLogArray ( "bearing", $this->vehicleStatus[$vin]['location']['bearing'], 3, 2 );
		$this->appendToLogArray ( "isLocationApprox", $this->vehicleStatus[$vin]['location']['isLocationApprox'], 3, 2 );
		$this->appendToLogArray ( "timestamp", date("d.m.Y H:i:s", $this->vehicleStatus[$vin]['location']['timeStamp']/1000), 3, 2 );

	}

	private function callApiPIN () {

		if ( $this->fiatLoginPin ) {

			$this->renewAmazonGetCredentialsIfNecessary();

			$this->appendToLogArray ( "PIN", $this->pinAuthUrl."/v1/accounts/".$this->fiatLoginUID."/ignite/pin/authenticate", 3, 1 );
			
			$jsonPIN = json_encode ( array ( "pin" => base64_encode($this->fiatLoginPin)));
			$this->appendToLogArray ( "jsonPIN", $jsonPIN, 3, 2 );

			$request = new GuzzleHttp\Psr7\Request(
				'POST',
				$this->pinAuthUrl."/v1/accounts/".$this->fiatLoginUID."/ignite/pin/authenticate",
				[  'Content-Type' => 'application/json',
				'x-clientapp-version' => '1.0',
				'clientrequestid' => '1592674815357357',
				'X-Api-Key' => $this->pinAuthApiKey,
				'x-originator-type' => 'web',
				'locale' => 'de_de',
				'X-Amz-Security-Token' => $this->awsCredentialsSessionToken
				],
				$jsonPIN
			);
			$signed_request = $this->sign($request, $this->awsCredentialsAccessKeyId, $this->awsCredentialsSecretKey);
	
			$client = new \GuzzleHttp\Client();
			$response = $client->send($signed_request);

			$this->appendToLogArray ( "StatusCode", ($response->getStatusCode()), 2, 2 );
			$this->appendToLogArray ( "ReasonPhrase", ($response->getReasonPhrase()), 2, 2 );
			$this->appendToLogArray ( "Response", ($response->getBody()), 2, 2 );

			$responseRaw = json_decode( ($response->getBody()), true );
			$this->pinAuthToken = $responseRaw['token'];
			$this->appendToLogArray ( "pinAuthToken", $this->pinAuthToken, 2, 2 );
		}
		else {
                  print "no PIN assigned\n";

	}
	}


	public function apiCommand ( $vin, $command ) {

		$actionURL = array (

			"VF"			=>	"location",			// UpdateLocation (updates gps location of the car)
			"DEEPREFRESH"	=>	"ev",				// DeepRefresh (same as "RefreshBatteryStatus")
			"HBLF"			=>	"remote",			// Blink (blink lights)
			"CNOW"			=>	"ev/chargenow",		// ChargeNOW (starts charging)
			"ROTRUNKUNLOCK"	=>	"remote",			// Unlock trunk
			"ROTRUNKLOCK"	=>	"remote",			// Lock trunk
			"RDU"			=>	"remote",			// Unlock doors
			"RDL"			=>	"remote",			// Lock doors
			"ROPRECOND"		=>	"remote"			// Turn on/off HVAC
			
		);

		if ( array_key_exists ($command, $actionURL)) {

			$this->renewAmazonGetCredentialsIfNecessary();
			$this->callApiPIN();

			$this->appendToLogArray ( "API Command", $this->awsApiUrl."/v1/accounts/".$this->fiatLoginUID."/vehicles/".$vin."/".$actionURL[$command], 3, 1 );

			$json = json_encode ( array ( "command" => $command, "pinAuth" => $this->pinAuthToken));
			$this->appendToLogArray ( "json", $json, 3, 2 );
			
			try {

				$request = new GuzzleHttp\Psr7\Request(
					'POST',
					$this->awsApiUrl."/v1/accounts/".$this->fiatLoginUID."/vehicles/".$vin."/".$actionURL[$command],
					[  'Content-Type' => 'application/json',
						'x-clientapp-version' => '1.0',
						'clientrequestid' => '1592674815357357',
						'X-Api-Key' => $this->awsAuthXApiKey,
						'x-originator-type' => 'web',
						'locale' => 'de_de',
						'X-Amz-Security-Token' => $this->awsCredentialsSessionToken
					],
					$json
				);
				$signed_request = $this->sign($request, $this->awsCredentialsAccessKeyId, $this->awsCredentialsSecretKey);
		
				$client = new \GuzzleHttp\Client();
				$response = $client->send($signed_request);

				$this->appendToLogArray ( "StatusCode", ($response->getStatusCode()), 2, 2 );
				$this->appendToLogArray ( "ReasonPhrase", ($response->getReasonPhrase()), 2, 2 );
				$this->appendToLogArray ( "Response", ($response->getBody()), 2, 2 );

				$responseRaw = json_decode( ($response->getBody()), true );
				return $responseRaw;
			} catch (ClientException  $e) {
				$responseBody = json_decode($e->getResponse()->getBody(true));
				#print "got exception:\n";
				#print_r($responseBody);
				#print_r($e);
				$this->appendToLogArray ( "Failure", $responseBody, 3, 2 );
				#return $responseBody;
			}
		}
		else {

			$this->appendToLogArray ( "API Command", "No valid command given", 5, 2 );

		}
	}


	private function isValidVin ( $vin ) {

		if ( 	is_string ( $vin ) && 
				strlen ( $vin ) == 17 ) {

			return true;

		}
		else {

			return false;

		}
	}



	public function apiRequestAll () {

		$this->apiRequestVehicles ();

		if ( is_array($this->vehicles) ) {

			foreach ($this->vehicles as $vehicle) {
			
				if ( $this->isValidVin ( $vehicle['vin'] ) ) {

					$this->apiRequestVehicleStatus ( $vehicle['vin'] );
					$this->apiRequestVehicleLocation ( $vehicle['vin'] );
                    $dr_age_sec = floor(time() - $this->vehicleStatus[$vehicle['vin']]['status']['evInfo']['timestamp']/1000);
                      
					if ( 	$this->vehicleStatus[$vehicle['vin']]['status']['evInfo']['battery']['chargingStatus'] == "CHARGING" &&
							$dr_age_sec > 270 ) {   //EHorvat modified to > 270 (was > 5*60)

						$this->appendToLogArray ( "Deep Refresh", "each 5 minutes", 3, 1 );                       
                        fiat_log("Deep Refresh now .... each 5 minutes, last Deep Refresh done " .$dr_age_sec. " seconds ago");  //EHorvat added this
						$this->apiCommand ( $vehicle['vin'], "DEEPREFRESH" );

					}
					else if ( 	$this->vehicleStatus[$vehicle['vin']]['status']['evInfo']['battery']['chargingStatus'] == "CHARGING" ) {

						$this->appendToLogArray ( "Deep Refresh", "No Deep Refresh (only once each 5 minutes)", 3, 1 );
                        fiat_log("Charging active, but no Deep Refresh (only once each 5 minutes), last Deep Refresh done " .$dr_age_sec. " seconds ago");  //EHorvat added this

					}
					else {
						
						$this->appendToLogArray ( "Deep Refresh", "No deep refresh, no charging process ongoing", 3, 1 );
                        fiat_log("No deep refresh, no charging process ongoing, last Deep Refresh done " .$dr_age_sec. " seconds ago");  //EHorvat added this
					}
					
					if ( 	$this->vehicleStatus[$vehicle['vin']]['status']['evInfo']['ignitionStatus'] == "ON" &&
							time() - $this->vehicleStatus[$vehicle['vin']]['location']['timeStamp']/1000 > 270 ) {        //EHorvat modified to  > 270 (was > 5*60)

						$this->appendToLogArray ( "Location Update", "", 3, 1 );
                        fiat_log("Location Update..."); //EHorvat added this
						$this->apiCommand ( $vehicle['vin'], "VF" );

					}
					else {
						
						$this->appendToLogArray ( "Location Update", "No location update, last update must be older than 5 minutes and vehicle must be moving", 3, 1 );
                        fiat_log("No location update, last update must be older than 5 minutes and vehicle must be moving");   //EHorvat added this
					}					
				}
			}
		}
	}



	public function exportInformation () {

		$exportArray = array (

			"vehicles"	=>	$this->vehicles,
			"vehicle"	=>	$this->vehicleStatus

		);

		return json_encode ( $exportArray );

	}



	public function getVehicleStatusData ( $vin, $level1, $level2 = "", $level3 = "", $level4 = "", $level5 = "" ) {

		// first draft, has to be validated with real data

		if ( $level1 != "" && $level2 != "" && $level3 != "" && $level4 != "" && $level5 ) {

			$return = $this->vehicleStatus[$vin][$level1][$level2][$level3][$level4][$level5];

		}
		elseif ( $level1 != "" && $level2 != "" && $level3 != "" && $level4 != "" ) {

			$return = $this->vehicleStatus[$vin][$level1][$level2][$level3][$level4];

		}
		elseif ( $level1 != "" && $level2 != "" && $level3 != "" ) {

			$return = $this->vehicleStatus[$vin][$level1][$level2][$level3];

		}
		elseif ( $level1 != "" && $level2 != "" ) {

			$return = $this->vehicleStatus[$vin][$level1][$level2];

		}
		elseif ( $level1 != "" ) {

			$return = $this->vehicleStatus[$vin][$level1];

		}

		if ( is_array ( $return )) {

			return json_encode ( $return );

		}
		else {

			return $return;

		}

	}



	public function refreshLogin () {

		$this->callApiFiatWebSDK();
		$this->callApiFiatLogin();
		$this->callApiFiatJWT();
		$this->callApiAmazonCognito();
		$this->callApiAmazonGetCredentials();

	}



	public function getLog () {

		return $this->log;

	}



	public function getLogArray ( $asJSON = true ) {

		if ( $asJSON ) {
		
			return json_encode ($this->logArray);

		}
		else {

			return $this->logArray;

		}
	}



	public function sign (
		RequestInterface $request,
		string $accessKeyId,
		string $secretAccessKey,
		string $data = null
	): RequestInterface {

		$signature = new SignatureV4 ( $this->awsService, $this->awsRegion );
		$credentials = new Credentials ( $accessKeyId, $secretAccessKey );
	
		return $signature->signRequest( $request, $credentials );

	}



	private function getRandomClientRequestID ( $length = 16 ) {

		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;

	}



	public function appendToLogArray ( $topic, $message, $level = 1, $hierarchie = 1 ) {

		array_push ( $this->logArray, array (

			'timestamp'		=>	time(),
			'hierarchie'	=>	$hierarchie,
			'topic'			=>	$topic,
			//'message'		=>	wordwrap($message, 90, '<br>', true),
			'message'		=>	$message,
			'level'			=>	$level

		));

	}
  
}
