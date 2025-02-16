<?php
/*
*   Class UniSortableUsersAjax
*
*/

if ( !class_exists( 'UniSortableUsersAjax' ) ) {

class UniSortableUsersAjax {

    protected $sNonceInputName      = 'uni_auth_nonce';
    protected $sNonce               = 'uni_authenticate_nonce';
    protected $sLangDomain          = 'uni-sortable-users';

	/**
	*  Construct
	*/
	public function __construct() {
        $this->_init();
	}

	/**
	*   Ajax
	*/
	protected function _init() {

        $aAjaxEvents = array(
                    'uni_sortable_users_save_order' => false

        );

		foreach ( $aAjaxEvents as $sAjaxEvent => $bPriv ) {
			add_action( 'wp_ajax_' . $sAjaxEvent, array(&$this, $sAjaxEvent) );

			if ( $bPriv ) {
				add_action( 'wp_ajax_nopriv_' . $sAjaxEvent, array(&$this, $sAjaxEvent) );
			}
		}

	}

	/**
	*   _r()
    */
    protected function _r() {
        $aResult = array(
		    'status' 	=> 'error',
			'message' 	=> __('Error!', $this->sLangDomain),
			'redirect'	=> ''
		);
        return $aResult;
    }

	/**
	*   _valid_email
    */
	protected function _valid_email( $email ) {
			$regex_pattern	= '/^[_a-zA-Z0-9-]+(\.[_A-Za-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/';
			$validation 	= preg_match($regex_pattern, $email);
			if ( ! empty( $email ) && $validation ) {
				return true;
			} else {
				return false;
			}
    }

	/**
	*   _auth
    */
	protected function _auth( $user_id, $cookie = true ) {
			wp_set_current_user($user_id);
			if ( $cookie ) {
				wp_set_auth_cookie($user_id, true);
			}
    }

    /*
    *  uni_sortable_users_save_order
    */
    function uni_sortable_users_save_order() {

        $aResult        = $this->_r();
        $iUserId        = ( isset($_POST['user_ID']) && !empty($_POST['user_ID']) ) ? intval( $_POST['user_ID'] ) : '';
        $iOrderValue    = ( isset($_POST['user_order_value']) && !empty($_POST['user_order_value']) ) ? intval( $_POST['user_order_value'] ) : '';

        if ( !empty($iUserId) && !empty($iOrderValue) ) {

            update_user_meta($iUserId, 'user_order', $iOrderValue);

        }

        wp_send_json( $aResult );
    }

}

}

?>