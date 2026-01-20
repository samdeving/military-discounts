<?php
/**
 * Form Builder class for Military Discounts plugin.
 *
 * Provides visual drag-and-drop form configuration.
 *
 * @package Military_Discounts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MD_Form_Builder
 *
 * Handles form field configuration for verification forms.
 */
class MD_Form_Builder {

	/**
	 * Available field types.
	 *
	 * @var array
	 */
	private $field_types = array(
		'text'     => 'Text Input',
		'email'    => 'Email Input',
		'date'     => 'Date Picker',
		'select'   => 'Select Dropdown',
		'state'    => 'State/Province',
		'country'  => 'Country',
	);

	/**
	 * Constructor - initialize field types with translations.
	 */
	public function __construct() {
		$this->field_types = array(
			'text'     => __( 'Text Input', 'military-discounts' ),
			'email'    => __( 'Email Input', 'military-discounts' ),
			'date'     => __( 'Date Picker', 'military-discounts' ),
			'select'   => __( 'Select Dropdown', 'military-discounts' ),
			'state'    => __( 'State/Province', 'military-discounts' ),
			'country'  => __( 'Country', 'military-discounts' ),
		);
	}

	/**
	 * Initialize form builder hooks.
	 */
	public function init() {
		add_action( 'wp_ajax_md_save_form_fields', array( $this, 'ajax_save_form_fields' ) );
		add_action( 'wp_ajax_md_reset_form_fields', array( $this, 'ajax_reset_form_fields' ) );
	}

	/**
	 * Get default veteran form fields.
	 *
	 * @return array Default fields.
	 */
	public function get_default_veteran_fields() {
		return array(
			array(
				'id'          => 'firstName',
				'type'        => 'text',
				'label'       => __( 'First Name', 'military-discounts' ),
				'placeholder' => '',
				'required'    => true,
				'api_field'   => 'firstName',
			),
			array(
				'id'          => 'middleName',
				'type'        => 'text',
				'label'       => __( 'Middle Name', 'military-discounts' ),
				'placeholder' => '',
				'required'    => false,
				'api_field'   => 'middleName',
			),
			array(
				'id'          => 'lastName',
				'type'        => 'text',
				'label'       => __( 'Last Name', 'military-discounts' ),
				'placeholder' => '',
				'required'    => true,
				'api_field'   => 'lastName',
			),
			array(
				'id'          => 'birthDate',
				'type'        => 'date',
				'label'       => __( 'Date of Birth', 'military-discounts' ),
				'placeholder' => '',
				'required'    => true,
				'api_field'   => 'birthDate',
			),
			array(
				'id'          => 'streetAddressLine1',
				'type'        => 'text',
				'label'       => __( 'Street Address', 'military-discounts' ),
				'placeholder' => '',
				'required'    => false,
				'api_field'   => 'streetAddressLine1',
			),
			array(
				'id'          => 'city',
				'type'        => 'text',
				'label'       => __( 'City', 'military-discounts' ),
				'placeholder' => '',
				'required'    => false,
				'api_field'   => 'city',
			),
			array(
				'id'          => 'state',
				'type'        => 'state',
				'label'       => __( 'State', 'military-discounts' ),
				'placeholder' => '',
				'required'    => false,
				'api_field'   => 'state',
			),
			array(
				'id'          => 'zipCode',
				'type'        => 'text',
				'label'       => __( 'ZIP Code', 'military-discounts' ),
				'placeholder' => '',
				'required'    => true,
				'api_field'   => 'zipCode',
			),
			array(
				'id'          => 'country',
				'type'        => 'country',
				'label'       => __( 'Country', 'military-discounts' ),
				'placeholder' => '',
				'required'    => false,
				'api_field'   => 'country',
			),
		);
	}

	/**
	 * Get default military form fields.
	 *
	 * @return array Default fields.
	 */
	public function get_default_military_fields() {
		return array(
			array(
				'id'          => 'militaryEmail',
				'type'        => 'email',
				'label'       => __( 'Military Email Address', 'military-discounts' ),
				'placeholder' => __( 'your.name@mail.mil', 'military-discounts' ),
				'required'    => true,
			),
		);
	}

	/**
	 * Get saved form fields.
	 *
	 * @param string $type Form type ('veteran' or 'military').
	 * @return array Form fields.
	 */
	public function get_form_fields( $type ) {
		$option_name = 'md_form_fields_' . $type;
		$fields      = get_option( $option_name );

		if ( empty( $fields ) ) {
			if ( 'veteran' === $type ) {
				return $this->get_default_veteran_fields();
			} else {
				return $this->get_default_military_fields();
			}
		}

		return $fields;
	}

	/**
	 * Save form fields.
	 *
	 * @param string $type   Form type.
	 * @param array  $fields Fields to save.
	 * @return bool True on success.
	 */
	public function save_form_fields( $type, array $fields ) {
		$option_name = 'md_form_fields_' . $type;
		return update_option( $option_name, $fields );
	}

	/**
	 * Render the form builder interface.
	 */
	public function render_builder() {
		$veteran_fields  = $this->get_form_fields( 'veteran' );
		$military_fields = $this->get_form_fields( 'military' );
		?>
		<div class="md-form-builder">
			<div class="md-builder-tabs">
				<button type="button" class="md-builder-tab active" data-tab="veteran">
					<?php esc_html_e( 'Veteran Form', 'military-discounts' ); ?>
				</button>
				<button type="button" class="md-builder-tab" data-tab="military">
					<?php esc_html_e( 'Military Form', 'military-discounts' ); ?>
				</button>
			</div>

			<div class="md-builder-content">
				<!-- Veteran Form Builder -->
				<div class="md-builder-panel active" data-panel="veteran">
					<h3><?php esc_html_e( 'Veteran Verification Form Fields', 'military-discounts' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Drag fields to reorder. These fields map to the VA API.', 'military-discounts' ); ?></p>

					<div class="md-field-list" id="md-veteran-fields">
						<?php $this->render_field_list( $veteran_fields, 'veteran' ); ?>
					</div>

					<div class="md-builder-actions">
						<button type="button" class="button md-add-field" data-form="veteran">
							<?php esc_html_e( 'Add Field', 'military-discounts' ); ?>
						</button>
						<button type="button" class="button md-reset-fields" data-form="veteran">
							<?php esc_html_e( 'Reset to Defaults', 'military-discounts' ); ?>
						</button>
						<button type="button" class="button button-primary md-save-fields" data-form="veteran">
							<?php esc_html_e( 'Save Fields', 'military-discounts' ); ?>
						</button>
					</div>
				</div>

				<!-- Military Form Builder -->
				<div class="md-builder-panel" data-panel="military">
					<h3><?php esc_html_e( 'Military Verification Form Fields', 'military-discounts' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Configure fields for military email verification.', 'military-discounts' ); ?></p>

					<div class="md-field-list" id="md-military-fields">
						<?php $this->render_field_list( $military_fields, 'military' ); ?>
					</div>

					<div class="md-builder-actions">
						<button type="button" class="button md-add-field" data-form="military">
							<?php esc_html_e( 'Add Field', 'military-discounts' ); ?>
						</button>
						<button type="button" class="button md-reset-fields" data-form="military">
							<?php esc_html_e( 'Reset to Defaults', 'military-discounts' ); ?>
						</button>
						<button type="button" class="button button-primary md-save-fields" data-form="military">
							<?php esc_html_e( 'Save Fields', 'military-discounts' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Field Template -->
			<script type="text/template" id="md-field-template">
				<?php $this->render_field_item( array(), '{{index}}' ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render a list of fields.
	 *
	 * @param array  $fields Fields to render.
	 * @param string $form_type Form type.
	 */
	private function render_field_list( array $fields, $form_type ) {
		foreach ( $fields as $index => $field ) {
			$this->render_field_item( $field, $index );
		}
	}

	/**
	 * Render a single field item.
	 *
	 * @param array      $field Field data.
	 * @param int|string $index Field index.
	 */
	private function render_field_item( array $field, $index ) {
		$defaults = array(
			'id'          => '',
			'type'        => 'text',
			'label'       => '',
			'placeholder' => '',
			'required'    => false,
			'api_field'   => '',
			'options'     => array(),
		);

		$field = wp_parse_args( $field, $defaults );
		?>
		<div class="md-field-item" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="md-field-handle">
				<span class="dashicons dashicons-move"></span>
			</div>
			<div class="md-field-content">
				<div class="md-field-row">
					<label>
						<?php esc_html_e( 'Field ID', 'military-discounts' ); ?>
						<input type="text" class="md-field-id" value="<?php echo esc_attr( $field['id'] ); ?>">
					</label>
					<label>
						<?php esc_html_e( 'Type', 'military-discounts' ); ?>
						<select class="md-field-type">
							<?php foreach ( $this->field_types as $type => $label ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $field['type'], $type ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<?php esc_html_e( 'Label', 'military-discounts' ); ?>
						<input type="text" class="md-field-label" value="<?php echo esc_attr( $field['label'] ); ?>">
					</label>
				</div>
				<div class="md-field-row">
					<label>
						<?php esc_html_e( 'Placeholder', 'military-discounts' ); ?>
						<input type="text" class="md-field-placeholder" value="<?php echo esc_attr( $field['placeholder'] ); ?>">
					</label>
					<label>
						<?php esc_html_e( 'API Field', 'military-discounts' ); ?>
						<input type="text" class="md-field-api" value="<?php echo esc_attr( $field['api_field'] ); ?>">
					</label>
					<label class="md-field-required-label">
						<input type="checkbox" class="md-field-required" <?php checked( $field['required'] ); ?>>
						<?php esc_html_e( 'Required', 'military-discounts' ); ?>
					</label>
				</div>
			</div>
			<div class="md-field-actions">
				<button type="button" class="button-link md-remove-field">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for saving form fields.
	 */
	public function ajax_save_form_fields() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'military-discounts' ) );
		}

		$form_type = isset( $_POST['form_type'] ) ? sanitize_key( $_POST['form_type'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
		$raw_fields = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array();
		$fields     = is_array( $raw_fields ) ? $raw_fields : array();

		if ( ! in_array( $form_type, array( 'veteran', 'military' ), true ) ) {
			wp_send_json_error( __( 'Invalid form type.', 'military-discounts' ) );
		}

		// Sanitize fields.
		$sanitized_fields = array();
		foreach ( $fields as $field ) {
			// Use preg_replace to preserve camelCase for field IDs (required for VA API).
			$field_id = isset( $field['id'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $field['id'] ) : '';
			$api_field = isset( $field['api_field'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $field['api_field'] ) : '';
			
			// Handle required field - JS sends "true"/"false" strings or boolean.
			$is_required = false;
			if ( isset( $field['required'] ) ) {
				$is_required = filter_var( $field['required'], FILTER_VALIDATE_BOOLEAN );
			}
			
			$sanitized_fields[] = array(
				'id'          => $field_id,
				'type'        => isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text',
				'label'       => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
				'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
				'required'    => $is_required,
				'api_field'   => $api_field,
			);
		}

		$this->save_form_fields( $form_type, $sanitized_fields );

		wp_send_json_success( __( 'Fields saved successfully.', 'military-discounts' ) );
	}

	/**
	 * AJAX handler for resetting form fields.
	 */
	public function ajax_reset_form_fields() {
		check_ajax_referer( 'md_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'military-discounts' ) );
		}

		$form_type = isset( $_POST['form_type'] ) ? sanitize_key( $_POST['form_type'] ) : '';

		if ( ! in_array( $form_type, array( 'veteran', 'military' ), true ) ) {
			wp_send_json_error( __( 'Invalid form type.', 'military-discounts' ) );
		}

		// Delete saved fields to reset to defaults.
		delete_option( 'md_form_fields_' . $form_type );

		// Return default fields.
		if ( 'veteran' === $form_type ) {
			$fields = $this->get_default_veteran_fields();
		} else {
			$fields = $this->get_default_military_fields();
		}

		wp_send_json_success( array(
			'message' => __( 'Fields reset to defaults.', 'military-discounts' ),
			'fields'  => $fields,
		) );
	}
}
