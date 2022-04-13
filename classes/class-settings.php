<?php
namespace EdLTI\classes;

use stdClass;

/**
 * Control the LTI settings for WordPress.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti-advantage
 */
class Settings {

	private $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;

		add_action( 'network_admin_menu', [ $this, 'network_pages' ] );
	}

	/**
	 * Edit or save lti platforms
	 *
	 * @return void
	 */
	public function lti_platform_admin() {
		$is_editing = false;

		echo '<h2>LTI: Platforms</h2>';

		if ( ! empty( $_POST['action'] ) ) {
			check_admin_referer( 'lti' );

			$id           = (int) $_POST['id'];
			$query        = "SELECT * FROM {$this->wpdb->base_prefix}lti_platforms WHERE id = %d";

			switch ( $_POST['action'] ) {
				case 'edit':
                    // phpcs:disable
					$row = $this->wpdb->get_row( $this->wpdb->prepare( $query, $id ) );
                    // phpcs:enable

					if ( $row ) {
						$this->edit( $row );
						$is_editing = true;
					} else {
						echo '<h3>Platform not found</h3>';
					}

					break;
				case 'save':
					$errors = $this->do_validation();

					if ( ! empty( $errors ) ) {
						echo '<ul style="color:red">';

						foreach ( $errors as $error ) {
							echo '<li>' . esc_html( $error ) . '</li>';
						}

						echo '</ul>';

						break;
					}

					$enabled = isset( $_POST['enabled'] ) ? 1 : 0;

                    // phpcs:disable
					$row = $this->wpdb->get_row( $this->wpdb->prepare( $query, $id ) );
                    // phpcs:enable

					if ( $row ) {
						$update_query = "UPDATE {$this->wpdb->base_prefix}lti_platforms "
							. 'SET  name = %s, issuer = %s, client_id = %s, deployment_id = %s, auth_login_url = %s,  auth_token_url = %s, key_set_url = %s, enabled = %d '
							. 'WHERE id = %d';

                        // phpcs:disable
						$this->wpdb->query(
							$this->wpdb->prepare(
								$update_query,
								$_POST['name'],
								$_POST['issuer'],
								$_POST['client_id'],
								$_POST['deployment_id'],
								$_POST['auth_login_url'],
								$_POST['auth_token_url'],
								$_POST['key_set_url'],
								$enabled,
								$id
							)
						);
                        // phpcs:enable

						echo '<p><strong>Platform Updated</strong></p>';
					} else {
						$insert_query = "INSERT INTO {$this->wpdb->base_prefix}lti_platforms "
							. '( `name`, `issuer`, `client_id`, `deployment_id`, `auth_login_url`, `auth_token_url`, `key_set_url`, `enabled`) '
							. 'VALUES ( %s, %s, %s, %s, %s, %s, %s, %d)';

                        // phpcs:disable
						$this->wpdb->query(
							$this->wpdb->prepare(
								$insert_query,
								$_POST['name'],
								$_POST['issuer'],
								$_POST['client_id'],
								$_POST['deployment_id'],
								$_POST['auth_login_url'],
								$_POST['auth_token_url'],
								$_POST['key_set_url'],
								$enabled,
							)
						);
                        // phpcs:enable

						echo '<p><strong>Platform Added</strong></p>';
					}

					break;
				case 'del':
					$delete_query = "DELETE FROM {$this->wpdb->base_prefix}lti_platforms WHERE id = %d";

                    // phpcs:disable
					$this->wpdb->query( $this->wpdb->prepare( $delete_query, $id ) );
                    // phpcs:enable

					echo '<p><strong>Platform Deleted</strong></p>';

					break;
			}
		}

		if ( ! $is_editing ) {
			echo '<h3>Search</h3>';

			if ( isset( $_POST['search_txt'] ) ) {
				$search = '%' . $this->wpdb->esc_like( addslashes( $_POST['search_txt'] ) ) . '%';

				$search_query = "SELECT * FROM {$this->wpdb->base_prefix}lti_platforms "
					. 'WHERE name LIKE %s '
					. 'OR name LIKE %s';

				$rows = $this->wpdb->get_results(
                    // phpcs:disable
					$this->wpdb->prepare(
						$search_query,
						[ $search, $search ]
					)
                    // phpcs:enable
				);

				$this->listing( $rows, 'Searching for ' . esc_html( $_POST['search_txt'] ) );
			}

			echo '<form method="POST">';

			wp_nonce_field( 'lti' );

			echo '<input type="hidden" name="action" value="search" />';
			echo '<p>Search: <input type="text" name="search_txt" value=""></p>';
			echo '<input type="hidden" name="id" value="">';
			echo '<p><input type="submit" class="button-secondary" value="Search"></p>';
			echo '</form><br>';

			$this->edit();

            // phpcs:disable
			$rows = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->base_prefix}lti_platforms LIMIT 0,20" );
            // phpcs:enable

			$this->listing( $rows );
		}
	}

	/**
	 * Validate the platform form
	 *
	 * @return array
	 */
	private function do_validation() {
		$errors = [];

        // phpcs:disable
		if ( '' === $_POST['name'] ) {
			$errors[] = 'Name is required';
		}

		if ( '' === $_POST['issuer'] ) {
			$errors[] = 'Issuer is required';
		}

		if ( '' === $_POST['client_id'] ) {
			$errors[] = 'Client ID is required';
		}

		if ( '' === $_POST['deployment_id'] ) {
			$errors[] = 'Deployment ID is required';
		}

		if ( '' === $_POST['auth_login_url'] ) {
			$errors[] = 'Authentication Request URL is required';
		}

		if ( '' === $_POST['auth_token_url'] ) {
			$errors[] = 'Access Token URL is required';
		}

		if ( '' === $_POST['key_set_url'] ) {
			$errors[] = 'Public Keyset URL is required';
		}
        // phpcs:enable

		return $errors;
	}

	/**
	 * Edit an existing LTI setting.
	 *
	 * @param mixed $row
	 *
	 * @return void
	 */
	private function edit( $row = false ) {
		$is_new = false;

		if ( is_object( $row ) ) {
			echo '<h3>Edit Platform</h3>';
		} else {
			echo '<h3>New Platform</h3>';

			$row                  = new stdClass();
			$row->id              = 0;
			$row->name            = '';
			$row->issuer          = '';
			$row->client_id       = '';
			$row->deployment_id   = '';
			$row->auth_login_url  = '';
			$row->auth_token_url  = '';
			$row->key_set_url     = '';
			$row->enabled         = 1;
		}

		echo '<form method="POST"><input type="hidden" name="action" value="save">';

		wp_nonce_field( 'lti' );

		echo '<table class="form-table">';
		echo '<tr><th>Name</th><td><input type="text" name="name" value="' . esc_attr( $row->name )
			. '" required ></td></tr>';

		echo '<tr><th>Issuer</th><td><input type="text" name="issuer" value="' . esc_attr( $row->issuer )
		     . '" required ></td></tr>';

		echo '<tr><th>Client ID</th><td><input type="text" name="client_id" value="' . esc_attr( $row->client_id )
		     . '" required ></td></tr>';

		echo '<tr><th>Deployment ID</th><td><input type="text" name="deployment_id" value="' . esc_attr( $row->deployment_id )
		     . '" required ></td></tr>';

		echo '<tr><th>Authentication Request URL</th><td><input type="text" name="auth_login_url" value="' . esc_attr( $row->auth_login_url )
		     . '" required ></td></tr>';

		echo '<tr><th>Access Token URL</th><td><input type="text" name="auth_token_url" value="' . esc_attr( $row->auth_token_url )
		     . '" required ></td></tr>';

		echo '<tr><th>Public Keyset URL</th><td><input type="text" name="key_set_url" value="' . esc_attr( $row->key_set_url )
		     . '" required ></td></tr>';

		echo '<tr><th>Enabled</th><td><input type="checkbox" name="enabled" value="1" '
			. ( '1' === $row->enabled ? 'checked' : '' ) . '></td></tr>';

		echo '</table>';
		echo '<p><input type="hidden" name="id" value="' . $row->id . '"><input type="submit" class="button - primary" value="Save"></p></form><br><br>';
	}

	/**
	 * List all LTI configurations.
	 *
	 * @param array  $rows
	 * @param string $heading
	 *
	 * @return void
	 */
	private function listing( $rows, $heading = '' ) {
		if ( $rows ) {
			if ( '' !== $heading ) {
				echo '<h3>' . esc_html( $heading ) . '</h3>';
			}

			echo '<table class="widefat" cellspacing="0"><thead><tr><th>Name</th><th>Issuer</th>'
				. '<th>Client ID</th><th>Enabled</th><th>Edit</th><th>Delete</th></tr></thead><tbody>';

			foreach ( $rows as $row ) {
				echo '<tr><td>' . esc_html( $row->name ) . '</td>';
				echo '<td>' . esc_html( $row->issuer ) . '</td>';
				echo '<td>' . esc_html( $row->client_id ) . '</td>';
				echo '<td>';
				echo '1' === $row->enabled ? 'Yes' : 'No';

				echo '</td><td><form method="POST"><input type="hidden" name="action" value="edit">'
					. '<input type="hidden" name="id" value="' . esc_attr( $row->id ) . '">';

				wp_nonce_field( 'lti' );

				echo '<input type="submit" class="button-secondary" value="Edit"></form></td><td><form method="POST">'
					. '<input type="hidden" name="action" value="del"><input type="hidden" name="id" '
					. 'value="' . esc_attr( $row->id ) . '">';

				wp_nonce_field( 'lti' );

				echo '<input type="submit" class="button-secondary" value="Del"></form>';
				echo '</td></tr>';
			}

			echo '</table>';
		}
	}

	/**
	 * Add an LTI platform submenu
	 *
	 * @return void
	 */
	public function network_pages() {
		add_submenu_page(
			'settings.php',
			'LTI Platforms',
			'LTI Platforms',
			'manage_options',
			'lti_platform_admin',
			[ $this, 'lti_platform_admin' ]
		);
	}
}
