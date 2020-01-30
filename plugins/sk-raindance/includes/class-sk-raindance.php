<?php
/**
 * SK_Raindance
 * ============
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_Raindance
 */

class SK_Raindance {

	/**
	 * Instance of RD_API.
	 * @var RD_API
	 */
	private $rd_api;

	/**
	 * Checks if Raindance is active or not.
	 * @var boolean
	 */
	private $is_rd_active = false;

	/**
	 * Inits hooks and class.
	 */
	public function __construct() {
		$this->core_includes();

		register_activation_hook( SK_RD_FILE, [ 'SK_RD_Install', 'install' ] );
		register_deactivation_hook( SK_RD_FILE, [ 'SK_RD_Install', 'uninstall' ] );

		// Include all files and add all hooks.
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Includes all core includes.
	 * @return void
	 */
	private function core_includes() {
		require_once __DIR__ . '/class-sk-rd-install.php';
	}

	/**
	 * Includes all necessary files.
	 * @return void
	 */
	private function includes() {
		// Make sure pluggable is loaded.
		include_once ABSPATH . 'wp-includes/pluggable.php';

		// Include SMEX_API class file.
		include __DIR__ . '/class-sk-rd-api.php';
	}

	/**
	 * Inits Raindance.
	 * @return boolean
	 */
	private function init_rd() {
		try {
			$this->rd_api = SK_RD_API::get_instance();

			return true;
		} catch ( Exception $e ) {
			throw $e;
			error_log( __( 'Couldn\'t connect to Raindance.', 'sk-raindance' ) );
			return false;
		}
	}

	/**
	 * Initiates all action and filter hooks.
	 * @return void
	 */
	private function init_hooks() {
		// Register the listener.
		add_filter( 'fmca_listener_actions', array( $this, 'add_listener_action' ) );

		// Add our custom checkout field validation.
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_additional_fields' ) );
	}

	/**
	 * Adds a listener action for updating Raindance entities.
	 * @param  array $actions
	 * @return array
	 */
	public function add_listener_action( $actions ) {
		// Check if we can successfully connect to Raindance.
		// And if so, we can add the action.
		$this->is_rd_active = $this->init_rd();
		if ( $this->is_rd_active ) {
			if ( ! isset( $actions['update_raindance_data'] ) ) {
				$actions['update_raindance_data'] = array(
					'callable' => array(
						$this,
						'update_raindance_data',
					),
					'args' => array(),
				);
			}
		}

		return $actions;
	}

	/**
	 * Updates the Raindance data.
	 * @return array
	 */
	public function update_raindance_data() {
		$return = array(
			'responsibility_number' => 0,
			'occupation_number'     => 0,
			'activity_number'       => 0,
			'project_number'        => 0,
			'object_number'         => 0,
		);

		if ( $this->rd_api ) {
			$types = array(
				'responsibility_number' => $this->rd_api->get_remote_entities( 'responsibility_number' ),
				'occupation_number'     => $this->rd_api->get_remote_entities( 'occupation_number' ),
				'activity_number'       => $this->rd_api->get_remote_entities( 'activity_number' ),
				'project_number'        => $this->rd_api->get_remote_entities( 'project_number' ),
				'object_number'         => $this->rd_api->get_remote_entities( 'object_number' ),
			);
			foreach ( $types as $type => $numbers ) {
				if ( ! empty( $numbers ) ) {
					global $wpdb;

					// Truncate the table since that's easier
					// than to keep track of which to insert/delete.
					$table_name = sprintf( '%ssk_rd_%ss', $wpdb->prefix, $type );
					$wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore

					// Insert each number.
					foreach ( $numbers as $number ) {
						$inserted = $wpdb->insert(
							$table_name,
							array(
								$type => $number,
							)
						);
						if ( $inserted ) {
							$return[ $type ]++;
						}
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Validates the additional fields against known
	 * entries.
	 * @return void
	 * @throws Exception If validation fails on either fields.
	 */
	public function validate_additional_fields() {
		$posted = wc_clean( $_POST );

		$responsibility_number = $posted['billing_responsibility_number'];
		$validate = $this->validate( 'responsibility_number', $responsibility_number );
		if ( is_wp_error( $validate ) ) {
			throw new Exception( $validate->get_error_message(), 410 );
		}

		$occupation_number = $posted['billing_occupation_number'];
		$validate = $this->validate( 'occupation_number', $occupation_number );
		if ( is_wp_error( $validate ) ) {
			throw new Exception( $validate->get_error_message(), 410 );
		}

		if ( ! empty( $posted['billing_activity_number'] ) ) {
			$activity_number = $posted['billing_activity_number'];
			$validate = $this->validate( 'activity_number', $activity_number );
			if ( is_wp_error( $validate ) ) {
				throw new Exception( $validate->get_error_message(), 410 );
			}
		}

		if ( ! empty( $posted['billing_project_number'] ) ) {
			$project_number = $posted['billing_project_number'];
			$validate = $this->validate( 'project_number', $project_number );
			if ( is_wp_error( $validate ) ) {
				throw new Exception( $validate->get_error_message(), 410 );
			}
		}

		if ( ! empty( $posted['billing_object_number'] ) ) {
			$object_number = $posted['billing_object_number'];
			$validate = $this->validate( 'object_number', $object_number );
			if ( is_wp_error( $validate ) ) {
				throw new Exception( $validate->get_error_message(), 410 );
			}
		}
	}

	/**
	 * Allows validation of certain fields in the sense
	 * that we check if the value exists in the saved entries
	 * that we have for that field.
	 * @param  string $field
	 * @param  string $value
	 * @return boolean|WP_Error
	 */
	public function validate( $field, $value ) {
		$valid_fields = array(
			'responsibility_number',
			'occupation_number',
			'activity_number',
			'project_number',
			'object_number',
		);

		// False if the field doesn't exist.
		if ( ! in_array( $field, $valid_fields ) ) {
			return new WP_Error( 'invalid_field', sprintf( __( 'Fältet %s är inte giltigt för validering via SMEX.', 'skw' ), $field ) );
		}

		global $wpdb;
		$exists = ( $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}sk_rd_{$field}s WHERE {$field} = {$value}" ) );
		if ( ! $exists ) {
			$label = '';
			switch ( $field ) {
				case 'responsibility_number':
					$label = __( 'Ansvarsnummer', 'skw' );
					break;
				case 'occupation_number':
					$label = __( 'Verksamhetsnummer', 'skw' );
					break;
				case 'activity_number':
					$label = __( 'Aktivitetsnummer', 'skw' );
					break;
				case 'project_number':
					$label = __( 'Projektnummer', 'skw' );
					break;
				case 'object_number':
					$label = __( 'Objektnummer', 'skw' );
					break;
			}

			return new WP_Error(
				'value_does_not_exist',
				sprintf(
					__( '"%s" är inte ett giltigt %s.', 'skw' ), // phpcs:ignore
					$value,
					mb_strtolower( $label )
				)
			);
		} else {
			return true;
		}
	}

}
