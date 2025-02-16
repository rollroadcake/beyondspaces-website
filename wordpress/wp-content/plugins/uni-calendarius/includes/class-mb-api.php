<?php
/*
*  Uni_Ec_Api_mb class
*
*/

class Uni_Ec_Api_mb {

    protected $id = 'mb';
	protected $client;

	protected $appointmentServiceWSDL   = "https://api.mindbodyonline.com/0_5/AppointmentService.asmx?WSDL";
	protected $classServiceWSDL         = "https://api.mindbodyonline.com/0_5/ClassService.asmx?WSDL";
	protected $clientServiceWSDL        = "https://api.mindbodyonline.com/0_5/ClientService.asmx?WSDL";
	protected $dataServiceWSDL          = "https://api.mindbodyonline.com/0_5/DataService.asmx?WSDL";
	protected $finderServiceWSDL        = "https://api.mindbodyonline.com/0_5/FinderService.asmx?WSDL";
	protected $saleServiceWSDL          = "https://api.mindbodyonline.com/0_5/SaleService.asmx?WSDL";
	protected $siteServiceWSDL          = "https://api.mindbodyonline.com/0_5/SiteService.asmx?WSDL";
	protected $staffServiceWSDL         = "https://api.mindbodyonline.com/0_5/StaffService.asmx?WSDL";

	protected $apiMethods   = array();
	protected $apiServices  = array();

	public $soapOptions     = array( 'soap_version' => SOAP_1_1, 'trace' => true );
	public $debugSoapErrors = true;

    /*
	*
	*/
	public function __construct( $aSourceCreds = array() ) {

		//
		$this->apiServices = array(
			'AppointmentService'    => $this->appointmentServiceWSDL,
			'ClassService'          => $this->classServiceWSDL,
			'ClientService'         => $this->clientServiceWSDL,
			'DataService'           => $this->dataServiceWSDL,
			'FinderService'         => $this->finderServiceWSDL,
			'SaleService'           => $this->saleServiceWSDL,
			'SiteService'           => $this->siteServiceWSDL,
			'StaffService'          => $this->staffServiceWSDL
		);

		//
		foreach( $this->apiServices as $serviceName => $serviceWSDL ) {
			$this->client = new SoapClient( $serviceWSDL, $this->soapOptions );
			$this->apiMethods = array_merge( $this->apiMethods, array( $serviceName => array_map('uni_ec_mb_services_iteration', $this->client->__getFunctions()) ) );
		}

		//
		if( !empty($aSourceCreds) ) {
			if( !empty($aSourceCreds['SourceName']) ) {
				$this->sourceCredentials['SourceName'] = $aSourceCreds['SourceName'];
			}
			if( !empty($aSourceCreds['Password']) ) {
				$this->sourceCredentials['Password'] = $aSourceCreds['Password'];
			}
			if( !empty($aSourceCreds['SiteIDs']) ) {
				if( is_array($aSourceCreds['SiteIDs']) ) {
					$this->sourceCredentials['SiteIDs'] = $aSourceCreds['SiteIDs'];
				} else if( is_numeric($aSourceCreds['SiteIDs']) ) {
					$this->sourceCredentials['SiteIDs'] = array($aSourceCreds['SiteIDs']);
				}
			}
		}

	}

	/*
	*
	*/
	public function __call( $sName, $aArguments ) {
		//
		$sSoapService = false;
		foreach( $this->apiMethods as $sApiServiceName => $aApiMethods ) {
			if( in_array( $sName, $aApiMethods ) ) {
				$sSoapService = $sApiServiceName;
			}
		}
		if( !empty( $sSoapService ) ) {
			if( empty( $aArguments ) ) {
				return $this->call_mindbody_service( $sSoapService, $sName );
			} else {
				switch( count($aArguments) ) {
					case 1:
						return $this->call_mindbody_service( $sSoapService, $sName, $aArguments[0] );
					case 2:
						return $this->call_mindbody_service( $sSoapService, $sName, $aArguments[0], $aArguments[1] );
				}
			}
		} else {
			esc_html_e('called unknown method '.$sName.'<br />', 'uni-calendar');
			return 'NO_SOAP_SERVICE';
		}
	}

	/*
	*
	*/
	protected function call_mindbody_service( $sServiceName, $sMethodName, $aRequestData = array(), $bReturnObject = false, $bDebugErrors = false ) {

		$aRequest = array_merge( array( "SourceCredentials"=>$this->sourceCredentials ), $aRequestData );
		if( !empty( $this->userCredentials ) ) {
			$aRequest = array_merge( array( "UserCredentials"=>$this->userCredentials ), $aRequest );
		}
		$this->client = new SoapClient( $this->apiServices[$sServiceName], $this->soapOptions );
		try {
			$aResult = $this->client->$sMethodName( array( "Request" => $aRequest ) );
			if( $bReturnObject ) {
				return $aResult;
			} else {
				return json_decode( json_encode( $aResult ), 1 );
			}
		} catch (SoapFault $s) {
			if( $this->debugSoapErrors && $bDebugErrors ) {
				echo 'ERROR: [' . $s->faultcode . '] ' . $s->faultstring;
				$this->debug();
				return false;
			}
		} catch (Exception $e) {
		    if( $this->debugSoapErrors && $bDebugErrors ) {
	    	    echo 'ERROR: ' . $e->getMessage();
	    	    return false;
	        }
		}
	}

	public function getXMLRequest() {
		return $this->client->__getLastRequest();
	}

	public function getXMLResponse() {
		return $this->client->__getLastResponse();
	}

	public function debug() {

		echo "<pre>".print_r( $this->getXMLRequest(), 1 )."</pre>";
		echo "<pre>".print_r( $this->getXMLResponse(), 1 )."</pre>";

	}

}
?>