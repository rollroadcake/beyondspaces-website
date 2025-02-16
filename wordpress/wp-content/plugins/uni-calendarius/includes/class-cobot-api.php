<?php
/*
*  Uni_Ec_Api_cobot class
*
*/

class Uni_Ec_Api_cobot {

    protected $id = 'cobot';
    protected $client;

	protected $sClientId;
	protected $sClientSecret;
    protected $sRedirectUri;
    protected $sAccessToken;
	protected $sAccessTokenType;
    protected $sUserName;
	protected $sUserPass;

    public $sAuthUriBase = 'https://www.cobot.me/oauth/authorize';
    public $sAccessTokenUriBase = 'https://www.cobot.me/oauth/access_token';
    public $iCalId;
    public $sSpaceSubdomain;
    public $aSpaceInfo = array();

    //
	function __construct( $iCalId, $sClientId = '', $sClientSecret = '' ) {

        $this->iCalId = $iCalId;

	    if ( !empty($sClientId) && !empty($sClientSecret) ) { // should be used if access token has not been set yet
		    $this->sClientId        = $sClientId;
		    $this->sClientSecret    = $sClientSecret;
        } else if ( isset($this->iCalId) && !empty($this->iCalId) ) { // access token has been set, so specify only cal ID
            $this->sClientId        = get_post_meta($this->iCalId, '_uni_ec_cobot_client_id', true);
		    $this->sClientSecret    = get_post_meta($this->iCalId, '_uni_ec_cobot_client_secret', true);
        }

        $this->sAccessToken     = get_post_meta($this->iCalId, '_uni_ec_cobot_access_token', true);
        $this->sAccessTokenType = get_post_meta($this->iCalId, '_uni_ec_cobot_access_token_type', true);
        $this->sSpaceSubdomain  = get_post_meta($this->iCalId, '_uni_ec_cobot_space_subdomain', true);
        $this->aSpaceInfo       = get_post_meta($this->iCalId, '_uni_ec_cobot_space_info', true);

        // TODO
        //$this->sRedirectUri    = add_query_arg( array( 'page' => 'uni_ec_auth', 'service' => $this->id ), admin_url('admin.php') );
	}

    //
    /*public function get_auth_uri() {
        if ( !empty($this->sClientId) ) {
            $sAuthUri = add_query_arg(
                array(
                    'response_type'     => 'code',
                    'client_id'         => $this->sClientId,
                    'redirect_uri'      => $this->sRedirectUri,
                    'state'             => 'auth-code',
                    'scope'             => $this->sScope
                ),
                $this->sAuthUriBase
            );
            return $sAuthUri;
        } else {
            return '';
        }
	}

    //
    public function get_auth_link() {
        return '<a class="uni-ec-service-auth-link" target="_blank" href="'.esc_url( $this->get_auth_uri() ).'">'.esc_html__('Authorize', 'uni-calendar').'</a>';
	}*/

    // app flow is used, user has to be admin of this calendar
	protected function auth_token_for_cal( $sUserName, $sUserPass, $sScope ){

        $aResult = $this->_r();

        $aBody = array(
                'scope'             => $sScope,
                'grant_type'        => 'password',
                'username'          => $sUserName,
                'password'          => $sUserPass,
                'client_id'         => $this->sClientId,
                'client_secret'     => $this->sClientSecret
        );

        $aResponse = wp_remote_request( $this->sAccessTokenUriBase,
                array(
	                'method' => 'POST',
	                'timeout' => 45,
	                'redirection' => 5,
	                'blocking' => true,
	                'body' => $aBody
                )
        );

    	if ( is_wp_error( $aResponse ) ) {
                $ErrorMsg = $aResponse->get_error_message();
                $aResult['status']      = 'error';
                $aResult['message']     = $ErrorMsg;
        } else {
                $sBodyResponse = wp_remote_retrieve_body($aResponse);
                $aBodyResponse = json_decode($sBodyResponse);
                if ( $aResponse['response']['code'] == '200' ) {
                    $aResult['status']      = 'success';
                    $aResult['response']     = $aBodyResponse;
                } else if ( $aResponse['response']['code'] == '400' ) {
                    $aResult['response']     = $aBodyResponse;
                }
        }

        return $aResult;

	}

    //
	public function set_auth_token_for_cal( $sUserName = '', $sUserPass = '', $sScope, $sTypeOfOperation = '' ){

        if ( $sTypeOfOperation === 'get' ) {

            if ( !empty($this->sClientId) && !empty($this->sClientSecret) && empty($this->sAccessToken) && empty($this->sAccessTokenType) ) {

        		$aResult = $this->auth_token_for_cal( $sUserName, $sUserPass, $sScope );

                if ( $aResult['status'] === 'success' && !empty($aResult['response']) ) {
                    update_post_meta($this->iCalId, '_uni_ec_cobot_access_token', $aResult['response']->access_token);
                    update_post_meta($this->iCalId, '_uni_ec_cobot_access_token_type', $aResult['response']->token_type);

                    $this->sAccessToken        = $aResult['response']->access_token;
    		        $this->sAccessTokenType    = $aResult['response']->token_type;

                    return true;

                } else {
                    return false;
                }

            } else {
                return false;
            }

        } else if ( $sTypeOfOperation === 'delete' ) {

            $sUri = 'https://www.cobot.me/api/access_tokens/'.$this->sAccessToken;

            $aResult = $this->protected_resource_query( 'DELETE', $sUri );

            if ( $aResult['status'] === 'success' ) {

                delete_post_meta($this->iCalId, '_uni_ec_cobot_access_token');
                unset($this->sAccessToken);
                delete_post_meta($this->iCalId, '_uni_ec_cobot_access_token_type');
                unset($this->sAccessTokenType);
                delete_post_meta($this->iCalId, '_uni_ec_cobot_space_info');
                unset($this->aSpaceInfo);

                return true;

            } else {
                return false;
            }

            return true;

        } else {
            return false;
        }

	}

    //
    public function public_resource_query( $sMethod = 'GET', $sUri ){

        $aResult = $this->_r();

        $aResponse = wp_remote_request( $sUri,
                array(
	                'method' => $sMethod,
	                'timeout' => 45,
	                'redirection' => 5,
	                'blocking' => true
                )
        );

        if ( is_wp_error( $aResponse ) ) {
                $ErrorMsg = $aResponse->get_error_message();
                $aResult['status']      = 'error';
                $aResult['message']     = $ErrorMsg;
        } else {
                $sBodyResponse = wp_remote_retrieve_body($aResponse);
                $aBodyResponse = json_decode($sBodyResponse);
                if ( $aResponse['response']['code'] == '200' ) {
                    $aResult['status']      = 'success';
                    $aResult['response']     = $aBodyResponse;
                } else if ( $aResponse['response']['code'] == '400' ) {
                    $aResult['response']     = $aBodyResponse;
                }
        }

        return $aResult;

	}

    //
    public function protected_resource_query( $sMethod = 'GET', $sUri ){

        $aResult = $this->_r();

        $aResponse = wp_remote_request( $sUri,
                array(
	                'method' => $sMethod,
	                'timeout' => 45,
	                'redirection' => 5,
	                'blocking' => true,
                    'body' => array('access_token' => $this->sAccessToken)
                )
        );

        if ( is_wp_error( $aResponse ) ) {
                $ErrorMsg = $aResponse->get_error_message();
                $aResult['status']      = 'error';
                $aResult['message']     = $ErrorMsg;
        } else {
                $sBodyResponse = wp_remote_retrieve_body($aResponse);
                $aBodyResponse = json_decode($sBodyResponse);

                if ( $aResponse['response']['code'] == '200' || $aResponse['response']['code'] == '204' ) {
                    $aResult['status']      = 'success';
                    $aResult['response']     = $aBodyResponse;
                } else if ( $aResponse['response']['code'] != '200' ) {
                    $aResult['response']     = $aBodyResponse;
                }
        }

        return $aResult;

	}

    //
    public function get_bookings( $aArgs ){

        $aResult = $this->_r();

        $bResult = $this->set_space_info();

        if ( $bResult ) {
            $sUri = 'https://'.$this->sSpaceSubdomain.'.cobot.me/api/bookings?from='.$aArgs[0].'&amp;to='.$aArgs[1];
            return $this->protected_resource_query( $sMethod = 'GET', $sUri );
        } else {
            return $aResult;
        }

	}

    //
    public function set_space_info(){

        if ( empty($this->aSpaceInfo) ) {

            // 1. get and set space general info
            $aResult = $this->get_space();

            if ( $aResult['status'] === 'success' ) {
                $oSpaceInfo = $aResult['response'];

                foreach ( $oSpaceInfo as $sKey => $Value ) {
                    $this->aSpaceInfo[$sKey] = $Value;
                }

                // 2. get and set plans info
                $aResult = $this->get_plans();
                if ( $aResult['status'] === 'success' ) {
                    $aSpacePlans = $aResult['response'];

                    $this->aSpaceInfo['plans'] = $aSpacePlans;

                }

                // 2. get and set resources info
                $aResult = $this->get_resources();
                if ( $aResult['status'] === 'success' ) {
                    $aSpaceResources = $aResult['response'];

                    $this->aSpaceInfo['resources'] = $aSpaceResources;

                }

                // 3. save this info
                update_post_meta($this->iCalId, '_uni_ec_cobot_space_info', $this->aSpaceInfo);

                return true;

            } else {
                return false;
            }

        } else {
            return true;
        }
	}

    //
    public function get_resources(){

        $sUri = 'https://'.$this->sSpaceSubdomain.'.cobot.me/api/resources';
        return $this->protected_resource_query( $sMethod = 'GET', $sUri );

	}

    //
    public function get_plans( $sSpaceSubdomain = '' ){

        $aResult = $this->_r();

        if ( isset($sSpaceSubdomain) && !empty($sSpaceSubdomain) ) {
            $this->sSpaceSubdomain = $sSpaceSubdomain;
        }

        if ( empty($this->sSpaceSubdomain) ) {
            return $aResult;
        }

        $sUri = 'https://'.$this->sSpaceSubdomain.'.cobot.me/api/plans';

        return $this->public_resource_query( $sMethod = 'GET', $sUri );

	}

    //
    public function get_space( $sSpaceSubdomain = '' ){

        $aResult = $this->_r();

        if ( isset($sSpaceSubdomain) && !empty($sSpaceSubdomain) ) {
            $this->sSpaceSubdomain = $sSpaceSubdomain;
        }

        if ( empty($this->sSpaceSubdomain) ) {
            return $aResult;
        }

        $sUri = 'https://www.cobot.me/api/spaces/'.$this->sSpaceSubdomain;

        return $this->public_resource_query( $sMethod = 'GET', $sUri );

	}


    //
    public function get_token_info(){

        $sUri = 'https://www.cobot.me/api/access_tokens/'.$this->sAccessToken;
        return $this->protected_resource_query( $sMethod = 'GET', $sUri );

	}

    //
    protected function _r() {
        $aResult = array(
		    'status' 	=> 'error',
			'message' 	=> esc_html__('Error!', 'uni-calendar'),
            'response'	=> '',
			'redirect'	=> ''
		);
        return $aResult;
    }

}
?>