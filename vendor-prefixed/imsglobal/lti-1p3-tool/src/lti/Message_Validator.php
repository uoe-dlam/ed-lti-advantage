<?php
/**
 * @license proprietary?
 *
 * Modified by DLAM Applications Development Team on 27-July-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */
namespace UoEDLAM\Vendor\IMSGlobal\LTI;

interface Message_Validator {
    public function validate($jwt_body);
    public function can_validate($jwt_body);
}
?>