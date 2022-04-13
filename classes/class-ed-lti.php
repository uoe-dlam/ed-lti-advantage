<?php

namespace EdLTI\classes;

use Exception;
use InvalidArgumentException;
use PDO;

/**
 * Class for coordinating main LTI functions.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti-advantage
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

use IMSGlobal\LTI;

class Ed_LTI {

	private const COURSE_SITE_CATEGORY_ID = 2;

	private $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		add_action( 'parse_request', array( $this, 'do_login' ) );
		add_action( 'parse_request', array( $this, 'do_launch' ) );
		add_action( 'parse_request', array( $this, 'add_staff_to_student_blog' ) );

		new Settings();
		new Config();
	}

	/**
	 * Activate the plugin
	 *
	 * @return void
	 */
	public static function activate(): void {
		$data = new Data();
		$data->maybe_create_db();
		$data->maybe_create_site_blogs_meta_table();
	}

	// TODO Look at handling blog category.

	/**
	 * Get a DB connector for the LTI connection package
	 *
	 * @return DataConnector
	 */
	private function get_db_connector(): DataConnector {
		return DataConnector::getDataConnector(
			$this->wpdb->base_prefix,
			new PDO( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD )
		);
	}

	/**
	 * Do lti 1.3 Login
	 *
	 * @return void
	 */
	public function do_login(): void {
		if ( isset( $_REQUEST['lti-login'] ) ) {
			if ( session_status() === PHP_SESSION_NONE ) {
				session_start();
			}

			$_SESSION['client_id'] = $_REQUEST['client_id'];

			LTI\LTI_OIDC_Login::new( new Ed_Example_Database( $_SESSION['client_id'] ) )
			                  ->do_oidc_login_redirect( get_site_url() . '?lti-blog=true' )
			                  ->do_redirect();
		}
	}

	/**
	 * Create blog for user or display blog if one has already been created
	 *
	 * @return void
	 */
	public function do_launch(): void {
		if ( $this->is_basic_lti_request() ) {
			$launch = LTI\LTI_Message_Launch::new( new ED_Example_Database( $_SESSION['client_id'] ) )->validate();
			$data   = $launch->get_launch_data();
			$this->destroy_session();

			$blog_type  = $data['https://purl.imsglobal.org/spec/lti/claim/custom']['blog_type'] ?? '';
			$user_roles = new User_LTI_Roles( $data["https://purl.imsglobal.org/spec/lti/claim/roles"] );

			if ( $this->is_student_blog_and_non_student( $blog_type, $user_roles ) ) {
				$course_id        = $data['https://purl.imsglobal.org/spec/lti/claim/context']['label'];
				$resource_link_id = $data['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id'];

				$this->show_staff_student_blogs_for_course( $course_id, $resource_link_id, $data, $user_roles );

				return;
			}

			$user = $this->first_or_create_user( $this->get_user_data( $data ) );
			$this->set_user_name_temporarily_to_vle_name( $user, $this->get_user_data( $data ) );

			$blog_handler = Blog_Handler_Factory::instance( $blog_type );
			$blog_handler->init( $this->get_site_data( $data ), $user );

			$make_private = get_site_option( 'lti_make_sites_private' ) ? true : false;
			$blog_id      = $blog_handler->first_or_create_blog( $make_private );

			$blog_handler->add_user_to_blog( $user, $blog_id, $user_roles );
			$blog_handler->add_user_to_top_level_blog( $user );
			$blog_handler->set_additional_blog_options( $data );

			$this->signin_user( $user, $blog_id );
		}
	}

	/**
	 * Check that the LTI request being received is a basic LTI request
	 *
	 * @return bool
	 */
	private function is_basic_lti_request(): bool {
		return isset( $_REQUEST['lti-blog'] );
	}

	/**
	 * Destroy the LTI session
	 *
	 * @return void
	 */
	private function destroy_session(): void {
		wp_logout();
		wp_set_current_user( 0 );

		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		$_SESSION = array();

		session_destroy();
		session_start();
	}

	/**
	 * Determine if a non-student user is accessing a student blog
	 *
	 * @param string $blog_type
	 * @param User_Lti_Roles $user_roles
	 *
	 * @return bool
	 */
	private function is_student_blog_and_non_student( string $blog_type, User_Lti_Roles $user_roles ): bool {
		return ( 'student' === $blog_type && ! $user_roles->is_learner() );
	}

	/**
	 * Get the user data passed via LTI
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function get_user_data( array $data ): array {
		return array(
			'username'  => $data['https://purl.imsglobal.org/spec/lti/claim/ext']['user_username'],
			'email'     => $data['email'],
			'firstname' => $data['given_name'],
			'lastname'  => $data['family_name'],
			'password'  => $this->random_string( 20, '0123456789ABCDEFGHIJKLMNOPQRSTUVWZYZabcdefghijklmnopqrstuvwxyz' ),
		);
	}

	/**
	 * Get site information for the LTI provider
	 *
	 * @return array
	 */
	private function get_site_data( array $data ): array {
		$site_category = $data['https://purl.imsglobal.org/spec/lti/claim/custom']['site_category'] ?? self::COURSE_SITE_CATEGORY_ID;

		return [
			'course_id'        => $data['https://purl.imsglobal.org/spec/lti/claim/context']['label'],
			'course_title'     => $data['https://purl.imsglobal.org/spec/lti/claim/context']['title'],
			'domain'           => get_current_site()->domain,
			'resource_link_id' => $data['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id'],
			'username'         => $data['https://purl.imsglobal.org/spec/lti/claim/ext']['user_username'],
			'site_category'    => $site_category,
			'source_id'        => get_site_option( 'default_site_template_id' ),
		];
	}

	/**
	 * Create a WordPress user or return the logged in user
	 *
	 * @param array $data
	 *
	 * @return WP_User
	 */
	private function first_or_create_user( array $data ): WP_User {
		$user = get_user_by( 'login', $data['username'] );

		if ( ! $user ) {
			$user_id = wpmu_create_user( $data['username'], $data['password'], $data['email'] );

			if ( ! $user_id ) {
				$error_message = 'This Email address is already being used by another user.' . $this->get_helpline_message();
				// phpcs:disable
				wp_die( $error_message, 200 );
				// phpcs:enable
			}

			$user = get_userdata( $user_id );

			$user->first_name = $data['firstname'];
			$user->last_name  = $data['lastname'];

			wp_update_user( $user );

			// set current user to null so that no administrator is added to a newly created blog.
			wp_set_current_user( null );
		}

		return $user;
	}

	/**
	 * Set user first and last name to info supplied by vle. We do not want to save this permanently, however, as that could undo changes the user made on the WordPress end.
	 *
	 * @param WP_User $user
	 * @param array $data
	 *
	 * @return void
	 */
	private function set_user_name_temporarily_to_vle_name( WP_User $user, array $data ): void {
		if ( '' !== $data['firstname'] || '' !== $data['lastname'] ) {
			$user->first_name = $data['firstname'];
			$user->last_name  = $data['lastname'];
		}
	}

	/**
	 * Create a login session for a user that has visited the blog via an LTI connection
	 *
	 * @param WP_User $user
	 * @param int $blog_id
	 *
	 * @return void
	 */
	private function signin_user( WP_User $user, int $blog_id ): void {
		switch_to_blog( $blog_id );

		clean_user_cache( $user->ID );
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true, true );


		update_user_caches( $user );

		if ( is_user_logged_in() ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Create a list of student blogs for a given course for a member of staff
	 *
	 * @param string $course_id
	 * @param string $resource_link_id
	 * @param array $data
	 * @param User_LTI_Roles $user_roles
	 *
	 * @return void
	 */
	private function show_staff_student_blogs_for_course( string $course_id, string $resource_link_id, array $data, User_LTI_Roles $user_roles ): void {
		$this->add_staff_info_to_session(
			$this->get_user_data( $data ),
			$user_roles,
			$course_id,
			$resource_link_id
		);

		$this->render_student_blogs_list_view( $course_id, $resource_link_id );
	}

	/**
	 * Add staff details to a current LTI session
	 *
	 * @param array $user_data
	 * @param User_LTI_Roles $user_roles
	 * @param string $course_id
	 * @param string $resource_link_id
	 *
	 * @return void
	 */
	private function add_staff_info_to_session(
		array $user_data,
		User_LTI_Roles $user_roles,
		string $course_id,
		string $resource_link_id
	): void {
		$_SESSION['lti_staff']            = true;
		$_SESSION['lti_user_roles']       = $user_roles;
		$_SESSION['lti_staff_user_data']  = $user_data;
		$_SESSION['lti_staff_course_id']  = $course_id;
		$_SESSION['lti_resource_link_id'] = $resource_link_id;
	}

	/**
	 * Render a list of student blogs
	 *
	 * @param string $course_id
	 * @param string $resource_link_id
	 *
	 * @return void
	 */
	private function render_student_blogs_list_view( string $course_id, string $resource_link_id ): void {
		$blog_type = 'student';

		$query = "SELECT * FROM {$this->wpdb->base_prefix}blogs_meta "
		         . "INNER JOIN {$this->wpdb->base_prefix}blogs "
		         . "ON {$this->wpdb->base_prefix}blogs.blog_id = {$this->wpdb->base_prefix}blogs_meta.blog_id "
		         . 'WHERE course_id = %s '
		         . 'AND resource_link_id = %s '
		         . 'AND blog_type = %s';

		$blogs = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$query,
				$course_id,
				$resource_link_id,
				$blog_type
			)
		);

		// Cache the response for 30 minutes
		header( 'Cache-Control: private, max-age: 1800' );

		get_template_part( 'header' );

		echo '<div style="width:80%; margin: 0 auto">';

		if ( empty( $blogs ) ) {
			echo '<p>No Student Blogs have been created for this course.</p>';
		} else {
			echo '<h2>Student Blogs For Course</h2>';
			echo '<ul>';

			foreach ( $blogs as $blog ) {
				$blog_details = get_blog_details( $blog->blog_id );
				$blog_name    = $blog_details->blogname;

				echo '<li><a href="index.php?lti_staff_view_blog=true&blog_id=' . esc_attr( $blog->blog_id ) . '">' .
				     esc_html( $blog_name ) . '</a></li>';
			}
		}

		echo '<br><br>';
		echo '</div>';

		get_template_part( 'footer' );

		exit;
	}

	/**
	 * Add staff members to student blogs
	 *
	 * @return void
	 */
	public function add_staff_to_student_blog(): void {
		// phpcs:disable
		if ( isset( $_REQUEST['lti_staff_view_blog'] ) && 'true' === $_REQUEST['lti_staff_view_blog'] ) {
			// phpcs:enable
			if ( session_status() === PHP_SESSION_NONE ) {
				session_start();
			}

			if ( ! isset( $_SESSION['lti_staff'] ) ) {
				wp_die( 'You do not have permission to view this page' );
			}

			// phpcs:disable
			$blog_id = $_REQUEST['blog_id'];
			// phpcs:enable

			$course_id  = $_SESSION['lti_staff_course_id'];
			$user_roles = $_SESSION['lti_user_roles'];

			// If someone has been messing about with the blog id and the blog has nothing to do with the current
			// course redirect them to the home page
			if ( ! Blog_Handler::is_course_blog( $course_id, $blog_id ) ) {
				$this->redirect_user_to_blog_without_login( $blog_id );
			}

			$user = $this->first_or_create_user( $_SESSION['lti_staff_user_data'] );

			$blog_handler = Blog_Handler_Factory::instance( 'student' );
			$blog_handler->add_user_to_blog( $user, $blog_id, $user_roles );

			$this->signin_user( $user, $blog_id );
		}
	}

	/**
	 * Redirect a user to the defined home URL
	 *
	 * @param string $blog_id
	 *
	 * @return void
	 */
	private function redirect_user_to_blog_without_login( $blog_id ): void {
		switch_to_blog( $blog_id );
		wp_safe_redirect( home_url() );

		exit;
	}

	/**
	 * Generates a cryptographically secure random string of a given length which can be used for generating passwords
	 *
	 * Adapted from https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php
	 *
	 * @param int $length
	 * @param string $alphabet
	 *
	 * @return string
	 * @throws Exception
	 * @throws InvalidArgumentException
	 */
	private function random_string( int $length, string $alphabet ): string {
		if ( $length < 1 ) {
			throw new InvalidArgumentException( 'Length must be a positive integer' );
		}

		$str = '';

		$alphamax = strlen( $alphabet ) - 1;

		if ( $alphamax < 1 ) {
			throw new InvalidArgumentException( 'Invalid alphabet' );
		}

		for ( $i = 0; $i < $length; ++ $i ) {
			$str .= $alphabet[ random_int( 0, $alphamax ) ];
		}

		return $str;
	}

	/**
	 * Is NS Cloner Installed.
	 *
	 * @return boolean
	 */
	public static function is_nscloner_installed(): bool {
		return is_plugin_active( 'ns-cloner-site-copier/ns-cloner.php' );
	}

	/**
	 * Get slug with slashes so it is a valid WordPress path.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public static function turn_slug_into_path( string $slug ): string {
		return rtrim( '/' . $slug, '/' ) . '/';
	}

	/**
	 * Get helpline message text.
	 *
	 * @return string
	 */
	protected function get_helpline_message(): string {
		$helpline_message = '';

		if ( ! empty( get_site_option( 'is_helpline_url' ) ) ) {
			$helpline_message = ' Please contact the <a href="' . get_site_option( 'is_helpline_url' ) . '">Helpline</a> for assistance.';
		}

		return $helpline_message;
	}
}
