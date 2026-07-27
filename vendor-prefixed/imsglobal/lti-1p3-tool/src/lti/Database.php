<?php
/**
 * @license proprietary?
 *
 * Modified by DLAM Applications Development Team on 27-July-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */
namespace UoEDLAM\Vendor\IMSGlobal\LTI;

interface Database {
    public function find_registration_by_issuer($iss);
    public function find_deployment($iss, $deployment_id);
}

?>