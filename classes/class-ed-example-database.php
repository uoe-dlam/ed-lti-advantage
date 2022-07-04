<?php

namespace EdLTIAdvantage\classes;

/**
 * Get Platform information from db
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti-advantage
 */

use IMSGlobal\LTI;

class Ed_Example_Database implements LTI\Database {

	private $wpdb;
	private $client_id;

	public function __construct( $client_id ) {
		global $wpdb;

		$this->wpdb = $wpdb;

		$this->client_id = $client_id;
	}

	public function find_registration_by_issuer( $iss ): LTI\LTI_Registration {
		$platform = $this->get_platform( $iss );

		if ( empty( $platform ) ) {
			wp_die( 'The platform that you linked from does not seem to be on out list of approved platforms. Please contact the site administrator.' );
		}

		return LTI\LTI_Registration::new()
		                           ->set_auth_login_url( $platform->auth_login_url )
		                           ->set_auth_token_url( $platform->auth_token_url )
		                           ->set_client_id( $this->client_id )
		                           ->set_key_set_url( $platform->key_set_url )
		                           ->set_issuer( $iss );
	}

	/**
	 * Get platform info
	 *
	 * @return mixed
	 */
	private function get_platform( string $iss ) {
		$query = "SELECT * FROM {$this->wpdb->base_prefix}lti_platforms WHERE issuer = %s AND client_id = %s AND enabled = 1";

		return $this->wpdb->get_row( $this->wpdb->prepare( $query, [ $iss, $this->client_id ] ) );
	}

	public function find_deployment( $iss, $deployment_id ): LTI\LTI_Deployment {
		$platform = $this->get_platform( $iss );

		if ( empty( $platform ) ) {
			wp_die( 'The platform that you linked from does not seem to be on out list of approved platforms. Please contact the site administrator.' );
		}

		return LTI\LTI_Deployment::new()
		                         ->set_deployment_id( $platform->deployment_id );
	}
}
