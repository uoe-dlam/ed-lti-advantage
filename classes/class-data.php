<?php
namespace EdLTIAdvantage\classes;

/**
 * Handles LTI tables in WordPress
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti-advantage
 */
class Data {

	private $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * Create table to store the consumers ands passwords if not exists
	 *
	 * @return void
	 */
	public function maybe_create_platforms_table() {
		$this->wpdb->ltiplatformstable = $this->wpdb->base_prefix . 'lti_platforms';

		if ( is_user_logged_in() && is_super_admin() ) {
			// phpcs:disable
			$lti_platforms_table = $this->wpdb->get_var( "SHOW TABLES LIKE '{$this->wpdb->ltiplatformstable}'" );
			// phpcs:enable

			if ( $lti_platforms_table !== $this->wpdb->ltiplatformstable ) {
				// phpcs:disable
				$this->wpdb->query(
					"CREATE TABLE IF NOT EXISTS `{$this->wpdb->ltiplatformstable}` (
     				  id int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) NOT NULL,
					  issuer varchar(255) DEFAULT NULL,
					  client_id varchar(255) DEFAULT NULL,
					  deployment_id varchar(255) DEFAULT NULL,
					  auth_login_url varchar(255) DEFAULT NULL,
					  auth_token_url varchar(255) DEFAULT NULL,
					  key_set_url varchar(255) DEFAULT NULL,
    				  enabled tinyint(1) NOT NULL,
                      PRIMARY KEY (id),
    				  UNIQUE KEY `platform_uniq_id` (`issuer`,`client_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
				);
				// phpcs:enable
			}
		}
	}

	/**
	 * Create blogs meta table if it doesn't exist
	 *
	 * @return void
	 */
	public function maybe_create_site_blogs_meta_table() {
		$this->wpdb->blogsmetatable = $this->wpdb->base_prefix . 'blogs_meta';

		if ( is_user_logged_in() && is_super_admin() ) {
            // phpcs:disable
            $blogs_meta_table = $this->wpdb->get_var( "SHOW TABLES LIKE '{$this->wpdb->blogsmetatable}'" );
            // phpcs:enable

			if ( $blogs_meta_table !== $this->wpdb->blogsmetatable ) {
                // phpcs:disable
				$this->wpdb->query(
					"CREATE TABLE IF NOT EXISTS `{$this->wpdb->blogsmetatable}` (
                      id int(11) NOT NULL AUTO_INCREMENT,
                      blog_id  bigint(20) NOT NULL,
                      version int(11) NOT NULL,
                      course_id varchar(256) NOT NULL,
                      resource_link_id varchar(256) NOT NULL,
                      blog_type varchar(256) NOT NULL,
                      creator_id bigint(20) NOT NULL,
                      creator_firstname varchar(256),
                      creator_lastname varchar(256),
                      PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
				);

				$this->wpdb->query(
					"ALTER TABLE `{$this->wpdb->blogsmetatable}`
                    ADD CONSTRAINT `{$this->wpdb->blogsmetatable}_site_FK1` FOREIGN KEY (blog_id)
                    REFERENCES {$this->wpdb->base_prefix}blogs (blog_id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;"
				);
                // phpcs:enable
			}
		}
	}
}
