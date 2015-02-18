<?php

/**
 * @link              http://duane.co.za/wordress-plugins/gravity-forms-solve-add-on
 * @since             1.0.0
 * @package           GFSolve
 *
 * @wordpress-plugin
 * Plugin Name:       Gravity Forms Solve Add-on
 * Plugin URI:        http://duane.co.za/wordpress-plugins/gravity-forms-solve-add-on
 * Description:       This is the most powerful Solve integration available for WordPress
 * Version:           0.1
 * Author:            Duane Cilliers
 * Author URI:        http://duane.co.za
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gf-solve
 * Domain Path:       /languages
 */

// Make sure Gravity Forms is active and loaded.
if ( ! class_exists('GFForms' ) ) {
	die;
}

// Load Gravity Forms Add-on Framework.
GFForms::include_addon_framework();

// Require Solve PHP Service Gateway.
require dirname( __FILE__ ) . '/includes/solve360Service.php';

/**
 * Required Backdrop. Used for running one-off tasks in the background.
 *
 * @link https://github.com/humanmade/Backdrop Backdrop
 */
require dirname( __FILE__ ) . '/includes/backdrop/hm-backdrop.php';

class GFSolve extends GFAddOn {

	// The version number is used for example during add-on upgrades.
	protected $_version = '0.1';

	// The Framework will display an appropriate message on the plugins page if necessary.
	protected $_min_gravityforms_version = '1.9.1.2';

	// This will be used for storing options, filters, actions, URLs and text-domain localization.
	protected $_slug = 'gfsolve';

	// Relative path to the plugin from the plugins folder.
	protected $_path = 'gravity-forms-solve-add-on/gravity-forms-solve-add-on.php';

	// Full path to the plugin.
	protected $_full_path = __FILE__;

	// Title of the plugin to be used on the settings page, form settings and plugins page.
	protected $_title = 'Graviy Forms Solve Add-on';

	// Short version of the plugin title to be used on menus and other places where a less verbose string is useful.
	protected $_short_title = 'Solve';

	// Required! The email address you login to Solve360 with.
	protected $user;

	// Required! Token, Solve360 menu > My Account > API Reference > API Token.
	protected $token;

	// Solve service gateway object.
	protected $solveService;

	// Gravity Form Add-on settings
	protected $plugin_settings;

	public function __construct() {

		parent::__construct();

		$this->plugin_settings 	= $this->get_plugin_settings();

		$this->user 		= isset( $this->plugin_settings['solve_user'] ) ? $this->plugin_settings['solve_user'] : false;
		$this->token 		= isset( $this->plugin_settings['solve_token'] ) ? $this->plugin_settings['solve_token'] : false;

		if ( ! $this->user && ! $this->token ) {
			throw new Exception( sprintf( 'Solve user and token are required! <a href="%s">Update your Solve credentials</a>.', $this->get_plugin_settings_url() ) );
		} else if ( ! $this->user ) {
			throw new Exception( sprintf( 'Solve user is required! <a href="%s">Update your Solve credentials</a>.', $this->get_plugin_settings_url() ) );
		} else if ( ! $this->token ) {
			throw new Exception( sprintf( 'Solve token is required! <a href="%s">Update your Solve credentials</a>.', $this->get_plugin_settings_url() ) );
		}

		$this->solveService = new Solve360Service( $this->user, $this->token );

		if ( ! $this->solveService ) {
			throw new Exception( 'Invalid Solve Credentials, failed to intitiate Solve gateway.' );
		}

		add_action( 'gform_field_advanced_settings', array( $this, 'gform_field_advanced_settings' ), 10, 2 );
		add_action( 'gform_editor_js', array( $this, 'editor_script' ) );
		add_action( 'gform_tooltips', array( $this, 'form_tooltips' ) );

	}

	public function init_frontend() {

		parent::init_frontend();
		add_action( 'gform_after_submission', array( $this, 'after_submission_init' ), 10, 2 );

	}

	public function plugin_settings_fields() {

		return array(
			array(
				'title' 	=> __( 'Solve API Credentials', $this->_slug ),
				'fields' 	=> array(
					array(
						'name' 			=> 'solve_user',
						'tooltip' 		=> __( 'Enter the email connected to your Solve account.', $this->_slug ),
						'label' 		=> __( 'Solve Email', $this->_slug ),
						'type' 			=> 'text',
						'class' 		=> 'medium',
						'placeholder' 	=> 'example@example.com'
					),
					array(
						'name' 			=> 'solve_token',
						'tooltip' 		=> __( 'Enter your Solve API Token found under My Account > API Token on your Solve dashboard.', $this->_slug ),
						'label' 		=> __( 'Solve API Token', $this->_slug ),
						'type' 			=> 'text',
						'class' 		=> 'medium'
					)
				)
			),
			array(
				'title' 	=> __( 'Email Notifications', $this->_slug ),
				'fields' 	=> array(
					array(
						'name' 			=> 'email_to',
						'tooltip' 		=> __( 'Enter email(s) to receive notifications. Separate multiple emails with a comma.', $this->_slug ),
						'label' 		=> __( 'To:', $this->_slug ),
						'type' 			=> 'text',
						'class' 		=> 'medium',
						'placeholder' 	=> 'example@example.com, User Name <user@example.com>'
					),
					array(
						'name' 			=> 'email_from',
						'tooltip' 		=> __( 'Enter email that notifications are sent from.', $this->_slug ),
						'label' 		=> __( 'From:', $this->_slug ),
						'type' 			=> 'text',
						'class' 		=> 'medium',
						'placeholder' 	=> 'From Name <from@example.com>'
					),
					array(
						'name' 			=> 'email_cc',
						'tooltip' 		=> __( 'Enter email(s) to be Cc\'d on notifications. Separate multiple emails with a comma.', $this->_slug ),
						'label' 		=> __( 'Cc:', $this->_slug ),
						'type' 			=> 'text',
						'class' 		=> 'medium',
						'placeholder' 	=> 'example@example.com, User Name <user@example.com>'
					),
					array(
						'name' 			=> 'email_bcc',
						'tooltip' 		=> __( 'Enter email(s) to be Bcc\'d on notifications. Separate multiple emails with a comma.', $this->_slug ),
						'label' 		=> __( 'Bcc:', $this->_slug ),
						'type' 			=> 'text',
						'class' 		=> 'medium',
						'placeholder' 	=> 'example@example.com, User Name <user@example.com>'
					)
				)
			)
		);

	}

	public function form_settings_fields( $form ) {

		$filterfields 	= array();
		$form_settings 	= $this->get_form_settings( $form );
		$filtermode 	= isset( $form_settings['filtermode'] ) ? $form_settings['filtermode'] : 'byemail';


		// @TODO Bug: filterfield form setting isn’t updated when filtered changes
		// wp_die( '<pre>' . print_r($form_settings, true) . '</pre>' );

		foreach ( $form['fields'] as $field ) {

			if ( ! isset( $filtermode ) ) { // get first email field and push onto $filterfields array

				if ( $field->type == 'email' ) {
					$filterfields[] = $field;
					break;
				}

			} else if ( $filtermode == 'byemail' ) { // push all email fields onto $filterfields array

				if ( $field->type == 'email' ) {
					$filterfields[] = $field;
				}

			} else if ( $filtermode == 'byphone' ) {

				if ( $field->type == 'text' || $field->type == 'number' || $field->type == 'hidden' || $field->type == 'phone' ) {
					$filterfields[] = $field;
				}

			}

		}

		// wp_die( '<pre>' . print_r($filterfields, true) . '</pre>' );

		$filterfield_choices = array();
		foreach ( $filterfields as $f ) {
			$filterfield_choices[] = array(
				'label' => $f->label,
				'value' => $f->id
			);
		}

		// wp_die( '<pre>' . print_r($filterfield_choices, true) . '</pre>' );

		return array(
			array(
				'title' 	=> __( 'Gravity to Solve Settings', $this->_slug ),
				'fields' 	=> array(
					array(
						'name'		=> 'enableSolve',
						'tooltip' 	=> __( 'Activate to feed entries from this form to Solve.', $this->_slug ),
						'label' 	=> __( 'Solve integration', $this->_slug ),
						'onclick' 	=> "jQuery(this).parents('form').find('#gform-settings-save').trigger('click');",
						'type' 		=> 'checkbox',
						'choices'	=> array(
							array(
								'label' => __( 'Enable Solve integration for this form.', $this->_slug ),
								'name' 	=> 'isEnabled'
							)
						)
					),
					array(
						'name'		=> 'filtermode',
						'tooltip' 	=> __( 'Solve contacts are searched when a form is submitted. The contact is updated if found and created if not found.', $this->_slug ),
						'label' 	=> __( 'Search Contacts by', $this->_slug ),
						'type' 		=> 'select',
						'onchange' 	=> "jQuery(this).parents('form').find('#gform-settings-save').trigger('click');",
						'choices'	=> array(
							array(
								'label' => __( 'Email (any email field)', $this->_slug ),
								'value' 	=> 'byemail'
							),
							array(
								'label' => __( 'Phone (any phone field)', $this->_slug ),
								'value' 	=> 'byphone'
							)
						)
					),
					array(
						'name'		=> 'filterfield',
						'tooltip' 	=> __( 'Select a field to search Solve contacts by.', $this->_slug ),
						'label' 	=> __( 'Search by field', $this->_slug ),
						'type' 		=> 'select',
						// 'onchange' 	=> "jQuery(this).parents('form').submit();",
						'choices'	=> $filterfield_choices
					)
				)
			)
		);

	}

	public function gform_field_advanced_settings( $position, $form_id ) {

		// @TODO: return if name field. Need to somehow get field id

		if ( $position == 550 ) {
			?>
			<li class="field_solve field_setting" style="display: list-item;">
				<label for="field_solve">
					Solve Field
					<?php gform_tooltip( 'field_solve' ); ?>
				</label>
				<input type="text" size="30" id="field_solve" onchange="SetFieldProperty('field_solve', this.value);" />
			</li>
			<?php
		}

	}

	public function form_tooltips( $tooltips ) {

		$tooltips['field_solve'] = '<h6>Solve Contact</h6> Enter the Solve field name you want this form field to populate. Refer to the list of <a href="#">available contact fields</a>.';
		return $tooltips;

	}

	public function editor_script() {

		?>
		<script type="text/javascript">

			jQuery(document).ready(function($) {

				if (typeof form.gfsolve !== 'undefined' || form.gfsolve.isEnabled) {
					// Adding setting to fields of type "text"
					console.log('fieldSettings', fieldSettings);
					$.each(fieldSettings, function(index, value) {
						fieldSettings[index] += ', .field_solve';
					});
				}

			});

			function SetFormProperty (name, value) {
				if (value) {
					form[name] = value;
				}
			}

			jQuery(document).bind('gform_load_field_settings', function (event, field, form) {

				jQuery('#field_solve').val(field['field_solve']);

				// console.log('event', event);
				console.log('field', field);
				console.log('form', form);
			});

		</script>
		<?php

	}

	public function after_submission_init( $entry, $form ) {

		// Check if solve integration is enabled
		if ( ! isset( $form['gfsolve']['isEnabled'] ) || ! $form['gfsolve']['isEnabled'] )
			return;

		$this->after_submission( $entry, $form );

		// $task = new \HM\Backdrop\Task( array( $this, 'after_submission' ), $entry, $form );
		// $task->schedule();

	}

	public function after_submission( $entry, $form ) {

		$contact_data 	= array();
		$categories 	= array();
		$form_settings 	= $this->get_form_settings( $form );
		$filtermode 	= isset( $form_settings['filtermode'] ) ? $form_settings['filtermode'] : false;
		$filterfield 	= isset( $form_settings['filterfield'] ) ? $form_settings['filterfield'] : false;

		// wp_die( '<pre>' . print_r($form, true) . '</pre>' );

		// Populate $contact_data array based on solve field selections
		foreach ( $form['fields'] as $field ) {

			if ( $field->type == 'name' ) {
				$contact_data['firstname'] 	= isset( $entry[$field->id . '.3'] ) ? $entry[$field->id . '.3'] : '';
				$contact_data['lastname']	= isset( $entry[$field->id . '.6'] ) ? $entry[$field->id . '.6'] : '';
			} else if ( ! empty( $solve_field = $field->field_solve ) ) {
				if ( 1 < count( $category = explode( ':', $solve_field) ) ) {
					$categories[] = (int) $category[1];
				} else if ( 'category' == trim( $solve_field ) ) {
					$categories[] = (int) $entry[$field->id];
				} else {
					$contact_data[$solve_field] = $entry[$field->id];
				}
			}

		}

		$contact_data['categories'] = array(
			'add' => array( 'category' => $categories )
		);

		$this->log_debug( $contact_data );

		// Check if $contact_data is empty
		if ( empty( $contact_data ) ) {
			$this->log_debug( sprintf( 'Entry %s from form %s not posted to solve, no field data found.', $entry['id'], $form['id'] ) );
			wp_mail( 'duane@signpost.co.za', sprintf( 'Entry %s from form %s not posted to solve', $entry['id'], $form['id'] ), sprintf( 'Entry %s from form %s not posted to solve, no field data found.', $entry['id'], $form['id'] ) );
			return false;
		}

		$this->log_debug( '$filtermode: ' . $filtermode );
		$this->log_debug( '$filterfield: ' . $filterfield );

		// Search existing Solve contacts
		if ( $filtermode && $filterfield) {
			try {
				$contacts = $this->solveService->searchContacts( array(
					'filtermode' => $filtermode,
					'filtervalue' => $entry[$filterfield]
				) );
			} catch (Exception $e) {
				$this->log_debug( 'Solve search failed! ' . $e->getMessage );
			}
		}

		// wp_die(
		// 	"filtermode: $filtermode<br>filtervalue: $filterfield<br>".
		// 	'<h2>Contacts:</h2><pre>' . print_r($contacts, true) . '</pre>' .
		// 	'<h2>Entry Object:</h2><pre>' . print_r($entry, true) . '</pre>' .
		// 	'<h2>contact_data:<h2><pre>' . print_r($contact_data, true) . '</pre>'
		// );

		if ( isset( $contacts ) && (integer) $contacts->count > 0 ) {

			$contact_id 	= (integer) current( $contacts->children() )->id;
			$contact_name 	= (string) current( $contacts->children() )->name;

			try {
				$contact 	= $this->solveService->editContact( $contact_id, $contact_data );
				$status 	= 'updated';
			} catch (Exception $e) {
				$this->log_debug( 'Failed to updated Solve contact!' . $e->getMessage() );
				$contact = new stdClass();
				$contact->errors = array( 'status' => 'failed', 'message' => $e->getMessage() );
			}

		} else {

			try {
				$contact 		= $this->solveService->addContact( $contact_data );
				$contact_name 	= (string) $contact->item->name;
				$contact_id 	= (integer) $contact->item->id;
				$status			= 'added';
			} catch (Exception $e) {
				$this->log_debug( 'Failed to updated Solve contact!' . $e->getMessage() );
				$contact = new stdClass();
				$contact->errors = array( 'status' => 'failed', 'message' => $e->getMessage() );
			}
		}

		if ( isset( $contact->errors ) ) {
			$this->log_debug( 'Error while adding contact to Solve', 'Error: ' . $contact->errors->asXml() );
			wp_mail( 'duane@signpost.co.za', 'Error while ' . $status . ' contact on Solve', 'Error: ' . $contact->errors->asXml() );
		} else {
			wp_mail( 'duane@signpost.co.za', 'Contact ' . $status . ' on Solve', "Contact $contact_name https://secure.solve360.com/contact/$contact_id was posted to Solve." );
		}

	}

}

try {
	new GFSolve();
} catch ( Exception $e ) {
	add_action( 'admin_notices', function() use ( $e ) {
		?>
		<div class="error">
			<p>
				<?php echo $e->getMessage(); ?>
			</p>
		</div>
		<?php
	} );
}
