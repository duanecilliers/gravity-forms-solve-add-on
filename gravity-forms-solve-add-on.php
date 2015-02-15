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

		$this->plugin_settings = $this->get_plugin_settings();

		$this->user 	= isset( $this->plugin_settings['solve_user'] ) ? $this->plugin_settings['solve_user'] : false;
		$this->token 	= isset( $this->plugin_settings['solve_token'] ) ? $this->plugin_settings['solve_token'] : false;

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

		return array(
			array(
				'title' 	=> __( 'Gravity to Solve Settings', $this->_slug ),
				'fields' 	=> array(
					array(
						'name'		=> 'enableSolve',
						'tooltip' 	=> __( 'Activate to feed entries from this form to Solve.', $this->_slug ),
						'label' 	=> __( 'Solve integration', $this->_slug ),
						'onclick' 	=> "jQuery(this).parents('form').submit();",
						'type' 		=> 'checkbox',
						'choices'	=> array(
							array(
								'label' => __( 'Enable Solve integration for this form.', $this->_slug ),
								'name' 	=> 'isEnabled'
							)
						)
					)
				)
			)
		);

	}

	public function gform_field_advanced_settings( $position, $form_id ) {

		if ( $position == 25 ) {

			// $categories = $solveService->getCategories( $solveService::ITEM_CONTACTS );
			// echo'<pre>' . print_r($categories, true) . '</pre>';

			// if ( !isset( $categories->categories ) ) {
			// 	return;
			// }
			?>
			<li class="admin_label_setting field_setting">
				<label for="solve_field">
					<?php _e( 'Solve field', $this->_slug ); ?>
					<?php gform_tooltip( 'form_field_solve_field' ); ?>
				</label>
				<select name="solve_field" id="solve_field">
					<?php
						// foreach ( $categories->categories->category as $c ) {
						// 	printf( '<option value="%s">%s</option>', $c->id, $c->name );
						// }
					?>
				</select>
			</li>
			<?php
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
