<?php

GFForms::include_addon_framework();

class UFCLASMyIT extends GFAddOn {

	protected $_version = UFCLAS_MYIT_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'ufclas-myit';
	protected $_path = 'ufclas-myit/ufclas-myit.php';
	protected $_full_path = __FILE__;
	protected $_title = 'MyIT Gravity Forms Add-On';
	protected $_short_title = 'MyIT';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return UFCLASMyIT
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new UFCLASMyIT();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_filter( 'gform_entry_post_save', array( $this, 'post_to_api' ), 10, 2);
	}


	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'myit_script_js',
				'src'     => $this->get_base_url() . '/js/script.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'first'  => esc_html__( 'First Choice', 'ufclas-myit' ),
					'second' => esc_html__( 'Second Choice', 'ufclas-myit' ),
					'third'  => esc_html__( 'Third Choice', 'ufclas-myit' )
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'ufclas-myit'
					)
				)
			),

		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$styles = array(
			array(
				'handle'  => 'myit_styles_css',
				'src'     => $this->get_base_url() . '/css/styles.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'field_types' => array( 'poll' ) )
				)
			)
		);

		return array_merge( parent::styles(), $styles );
	}


	// # FRONTEND FUNCTIONS --------------------------------------------------------------------------------------------

	/**
	 * Add the text in the plugin settings to the bottom of the form if enabled for this form.
	 *
	 * @param string $button The string containing the input tag to be filtered.
	 * @param array $form The form currently being displayed.
	 *
	 * @return string
	 */
	function form_submit_button( $button, $form ) {
		$settings = $this->get_form_settings( $form );
		if ( isset( $settings['myit_enabled'] ) && true == $settings['myit_enabled'] ) {
			$text   = $this->get_plugin_setting( 'myit_apiurl' );
			$button = "<div>{$text}</div>" . $button;
		}
		return $button;
	}
	
	/**
	 * Called after form validation, before notifications and entry is stored
	 * 
	 * @param array $form form data
	*/
	function post_to_api( $entry, $form ){
		$form_settings = $this->get_form_settings( $form );
		
		// Only apply filter to forms where MyIT is enabled
		if ( isset( $form_settings['myit_enabled'] ) && true == $form_settings['myit_enabled'] ) {
			
			// Get API info from plugin settings
			$api_settings = $this->get_plugin_settings();
			$api_url = $api_settings['myit_apiurl'];
			$api_key = $api_settings['myit_apikey'];		
			
			// Get Ticket data
			$ticketdata = $this->get_ticket_data( $entry, $form, $form_settings );
			$ticketdata['api_key'] = $api_key;
			
			// Send POST request
			$response = wp_remote_post( $api_url, 
				  array(
					'method' => 'POST',
					'body' => $ticketdata,
					'redirection' => 15,
					'timeout' => 120,
					'httpversion' => '1.1',
				  )
			);
			
			$response_body = json_decode($response['body'], true);
			 
			 /** 
			   * Sets the form api_response hidden field to the ticket number or error message from the response
			   * To display, add your api_response field to the confirmation message in Form > Settings > Confirmation 
			   */
			  $api_response_id = $form_settings['myit_apiresponse'];
			  
			  if ( isset($response_body['data']) ) {
				 $entry[$api_response_id] = sprintf("Ticket #%s has been submitted.", $response_body['data']['IncidentID']) ;
			  } 
			  elseif ( isset($response_body['message']) ) {
				$entry[$api_response_id] = sprintf("Error: %s", $response_body['message']) ;
			  } 
			  else {
				$entry[$api_response_id] = sprintf("Error: %s", __('No ticket number received.', 'ufclas_myit') ) ;
			  }
			  
			  // Updates the entry to include the response message
			  GFAPI::update_entry_field( $entry['id'], $api_response_id, $entry[$api_response_id] );
		}
		return $entry;
	}
	
	/**
	 * Collects ticket information from form submission and form settings
	 *
	 * @param array $form Form data
	 * @todo Check source whether it should be 'Portal' instead of 'Phone'
	 * @todo Check whether Gravity Forms sanitizes POST data
	 * @todo Check valid request_hardware values
	 * @return array New ticket information to submit to API
	 * @since 1.0.0
	 */
	function get_ticket_data( $entry, $form, $settings ){
		$specifics_data = array();
		
		$summary = $this->get_mapped_field_value( 'myit_summary', $form, $entry, $settings);
		$gatorlink = $this->get_mapped_field_value( 'myit_gatorlink', $form, $entry, $settings);
		$ufid = $this->get_mapped_field_value( 'myit_ufid', $form, $entry, $settings);
		$description = GFCommon::replace_variables( $settings['myit_description'], $form, $entry, false, true, false );
		
		$ticket = array(
			'Portfolio' => $settings['myit_portfolio'], 
			'Service' => $settings['myit_service'], 
			'Category' => $settings['myit_category'],
			'Subcategory' => $settings['myit_subcategory'],
			'Source' => $settings['myit_source'],
			'Priority' => $settings['myit_priority'],
			'AssignedSupportLevel' => $settings['myit_assigned_support_level'],
			'OwnedByTeam' => $settings['myit_owned_by_team'],
			'LastModifiedByEmail' => $settings['myit_last_modified_by_email'],
			'Summary' => $summary,
			'Description' => $description,
			'CustomersUFID' => $ufid,
			'GatorlinkID' => $gatorlink,
		);
		
		foreach ( $settings['myit_specifics'] as $index => $specifics ){
			$key = 'Specifics.' . $specifics['custom_key'];
			$specifics_data[$key] = $entry[$specifics['value']];
		}
		
		return array_merge( $ticket, $specifics_data );
	}

	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'MyIT Settings', 'ufclas-myit' ),
				'fields' => array(
					array(
						'name'              => 'myit_apiurl',
						'tooltip'           => esc_html__( 'URL to create a new ticket in myIT', 'ufclas-myit' ),
						'label'             => esc_html__( 'API URL', 'ufclas-myit' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'name'              => 'myit_apikey',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'label'             => esc_html__( 'API Key', 'ufclas-myit' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					)
				)
			)
		);
	}

	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'MyIT Form Settings', 'ufclas-myit' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Submit to MyIT', 'ufclas-myit' ),
						'type'    => 'checkbox',
						'name'    => 'myit_enabled',
						'choices' => array(
							array(
								'label' => esc_html__( 'Create a ticket in MyIT when this form is submitted.', 'ufclas-myit' ),
								'name'  => 'myit_enabled',
							),
						),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Ticket Settings', 'ufclas-myit' ),
				'fields' => array(
					array(
						'label'             => esc_html__( 'Assigned Support Level', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_assigned_support_level',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Owned By Team', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_owned_by_team',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Portfolio', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_portfolio',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Service', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_service',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Category', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_category',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Sub Category', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_subcategory',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label' => esc_html__( 'Source', 'ufclas-myit' ),
						'type'  => 'hidden',
						'name'  => 'myit_source',
						'default_value' => 'API',
					),
					array(
						'label'             => esc_html__( 'Priority', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_priority',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'small',
						'feedback_callback' => array( $this, 'absint' ),
						'default_value' 	=> 5,
					),
					array(
						'label'             => esc_html__( 'Last Modified By Email', 'ufclas-myit' ),
						'type'              => 'text',
						'name'              => 'myit_last_modified_by_email',
						'tooltip'           => esc_html__( '', 'ufclas-myit' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
				)
			),
			array(
				'title'  => esc_html__( 'Form Fields', 'ufclas-myit' ),
				'fields' => array(
					array(
						'label'               => esc_html__( 'Summary', 'ufclas-myit' ),
						'name'                => 'myit_summary',
						'type'                => 'field_select',
					),
					array(
						'label'               => esc_html__( 'Gatorlink Username', 'ufclas-myit' ),
						'name'                => 'myit_gatorlink',
						'type'                => 'field_select',
					),
					array(
						'label'               => esc_html__( 'UFID', 'ufclas-myit' ),
						'name'                => 'myit_ufid',
						'type'                => 'field_select',
					),
					array(
						'label'             => esc_html__( 'API Response', 'ufclas-myit' ),
						'name'              => 'myit_apiresponse',
						'type'              => 'field_select',
						'tooltip'           => '<h6>' . esc_html__( 'API Response Field', 'ufclas-myit' ) . '</h6>' . esc_html__( 'Use a hidden field in your form to save the response when the ticket data is sent. This will either be the ticket number or an error message from the API', 'ufclas-myit' ),
						'args'				=> array( 'input_types' => array('hidden') ),
					),
					array(
						'label'             => esc_html__( 'Description', 'ufclas-myit' ),
						'type'              => 'textarea',
						'name'              => 'myit_description',
						'tooltip'           => esc_html__( 'This is the message of the ticket. Use the "Merge Tags" dropdown to use data from form fields.', 'ufclas-myit' ),
						'class'             => 'medium merge-tag-support mt-position-right',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Form Specifics Fields', 'ufclas-myit' ),
				'fields' => array(
					array(
						'label'               => esc_html__( 'Specifics Data', 'ufclas-myit' ),
						'name'                => 'myit_specifics',
						'type'                => 'dynamic_field_map',
						'limit'               => 20,
						'tooltip'             => '<h6>' . esc_html__( 'Specifics Data (optional)', 'ufclas-myit' ) . '</h6>' . esc_html__( 'Enter the specifics property name and select the corresponding field in the form.', 'ufclas-myit' ),
						'validation_callback' => array( $this, 'validate_specifics_data' ),
					),
				),
			),
		);
	}

	/**
	 * Validate the Secifics fields
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 */
	public function validate_specifics_data( $field ) {
		//Number of keys is limited to 20 - interface should control this, validating just in case
		//key names can only be max of 40 characters
		
		$settings = $this->get_posted_settings();
		$metaData = $settings['myit_specifics'];
		
		if ( empty( $metaData ) ) {
			return;
		}
		
		//check the number of items in metadata array
		$metaCount = count( $metaData );
		if ( $metaCount > 20 ) {
			$this->set_field_error( array( esc_html__( 'You may only have 20 custom keys.' ), 'ufclas-myit' ) );
		
			return;
		}
		
		//loop through metaData and check the key name length (custom_key)
		foreach ( $metaData as $meta ) {
			if ( empty( $meta['custom_key'] ) && ! empty( $meta['value'] ) ) {
				$this->set_field_error( array( 'name' => 'myit_specifics' ), esc_html__( "A field has been mapped to a custom key without a name. Please enter a name for the custom key, remove the metadata item, or return the corresponding drop down to 'Select a Field'.", 'ufclas-myit' ) );
				break;
			}
		}
	}

	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * The feedback callback for the 'mytextbox' setting on the plugin settings page and the 'mytext' setting on the form settings page.
	 *
	 * @param string $value The setting value.
	 *
	 * @return bool
	 */
	public function is_valid_setting( $value ) {
		return strlen( $value ) > 0;
	}

}