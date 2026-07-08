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

use Packback\Lti1p3;
use Packback\Lti1p3\Interfaces\ILtiRegistration;
use Packback\Lti1p3\Interfaces\ILtiDeployment;

class Ed_Example_Database implements Lti1p3\Interfaces\IDatabase
{
    private $wpdb;
    private $client_id;

    public function __construct($client_id)
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        $this->client_id = $client_id;
    }

    public function findRegistrationByIssuer($iss): ?ILtiRegistration
    {
        $platform = $this->get_platform($iss);

        if (empty($platform)) {
            wp_die('The platform that you linked from does not seem to be on out list of approved platforms. Please contact the site administrator.');
        }

        return Lti1p3\LtiRegistration::new()
                                   ->setAuthLoginUrl($platform->auth_login_url)
                                   ->setAuthTokenUrl($platform->auth_token_url)
                                   ->setClientId($this->client_id)
                                   ->setKeySetUrl($platform->key_set_url)
                                   ->getIssuer($iss);
    }

    /**
     * Get platform info
     *
     * @return mixed
     */
    private function get_platform(string $iss)
    {
        $query = "SELECT * FROM {$this->wpdb->base_prefix}lti_platforms WHERE issuer = %s AND client_id = %s AND enabled = 1";

        return $this->wpdb->get_row($this->wpdb->prepare($query, [ $iss, $this->client_id ]));
    }

    public function findDeployment($iss, $deployment_id): ?ILtiDeployment
    {
        $platform = $this->get_platform($iss);

        if (empty($platform)) {
            wp_die('The platform that you linked from does not seem to be on out list of approved platforms. Please contact the site administrator.');
        }

        return Lti1p3\LtiDeployment::new()
                                 ->setDeploymentId($platform->deployment_id);
    }
}
