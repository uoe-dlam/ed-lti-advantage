<?php
namespace EdLTIAdvantage\classes;

/**
 * Handles LTI user roles, providing useful wrapper functions to determine if a user is of a particular type.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti-advantage
 */
class User_LTI_Roles {

	protected $roles = array();

	public function __construct( array $roles ) {
		$this->roles = $this->simplify_roles( $roles );
	}

	private function simplify_roles( array $roles) {
		$simplified_roles = [];

		foreach ($roles as $role) {
			$simplified_roles[] = substr($role, strrpos($role,'#') + 1);
		}

		return $simplified_roles;
	}

	/**
	 * Check if the LTI user is an administrator
	 *
	 * @return bool
	 */
	public function is_admin() {
		return $this->has_role( 'Administrator' );
	}

	/**
	 * Check if the LTI user is a member of staff
	 *
	 * @return bool
	 */
	public function is_staff() {
		return $this->has_role( 'Instructor' ) || $this->has_role( 'ContentDeveloper' ) ||
			$this->has_role( 'TeachingAssistant' );
	}

	/**
	 * Check if the LTI user is a learner
	 *
	 * @return bool
	 */
	public function is_learner() {
		return $this->has_role( 'Learner' );
	}

	/**
	 * Check if the LTI user is an instructor
	 *
	 * @return bool
	 */
	public function is_instructor() {
		return $this->has_role( 'Instructor' );
	}

	/**
	 * Check if the LTI user is a teaching assistant
	 *
	 * @return bool
	 */
	public function is_teaching_assistant() {
		return $this->has_role( 'TeachingAssistant' );
	}

	/**
	 * Check if the LTI user is a content developer
	 *
	 * @return bool
	 */
	public function is_content_developer() {
		return $this->has_role( 'ContentDeveloper' );
	}

	/**
	 * Check if the user has a given role
	 *
	 * @param string $role
	 *
	 * @return bool
	 */
	private function has_role( $role ) {
		return in_array( $role, $this->roles, true );
	}
}
