<?php
/**
 *
 * Plugin Name:       CTLT Prepare Site Administrator Mailing List
 * Plugin URI:        https://ctlt.ubc.ca
 * Description:       Based on a list of site IDs, prepare a CSV file includes administrators email addresses.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kelvin
 * Author URI:        https://ctlt.ubc.ca/
 * Text Domain:       ubc-prepare-site-administrator-mailing-list
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package ubc
 */

namespace UBC\CTLT\PrepareSiteAdministratorMailingList;

/**
 * Plugin init.
 *
 * @return void
 */
function init() {
	if ( ! current_user_can( 'manage_network_users' ) ) {
		return;
	}

	add_action( 'network_admin_menu', __NAMESPACE__ . '\\add_network_menu' );

	function add_network_menu() {

		add_submenu_page(
			'index.php',
			__( 'Generate Administrator Mailing List', 'ubc-prepare-site-administrator-mailing-list' ),
			__( 'Generate Administrator Mailing List', 'ubc-prepare-site-administrator-mailing-list' ),
			'manage_network_users',
			'generate_administrator_mailing_list',
			__NAMESPACE__ . '\\render_network_menu_content'
		);
	}

	function render_network_menu_content() {
		if( isset( $_POST['site_ids'] ) ) {
			get_mailing_list();
			return;
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_title( wp_unslash( $_GET['page'] ) ) : '';

		ob_start(); ?>
			<div class="wrap">
				<h1>Site Administrator Mailing List</h1>
			</div>
			<form method="post" action="<?php echo esc_url( network_admin_url( 'index.php?page=' . $current_page ) ); ?>" onsubmit = "return( validate());">
				<textarea name="site_ids" id="site_ids" cols="50" rows="10"></textarea>
				<br />
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Run">
			</form>

			<script>
				function validate() {
					let input = document.getElementById('site_ids').value;

					if ( ! input ) {
						alert( 'Input not valid.' );
						return false;
					}

					input = input.split(',');

					for( let i=0;i<input.length;i++){
						if ( ! is_int( input[i] ) ) {
							alert( 'Input not valid.' );
							return false;
						}
					}

					return true;
				}
				
			</script>
		<?php echo ob_get_clean();
	}

	function get_mailing_list() {
		$ids = explode(",", $_POST['site_ids']);
		$out = '<pre>';


		foreach ($ids as $key => $id) {
			global $wpdb;
			$dmtable = $wpdb->base_prefix . 'domain_mapping';
			$mapped_domain = $wpdb->get_var( "SELECT domain FROM {$dmtable} WHERE blog_id = '{$id}' AND active = 1 LIMIT 1" );
			$url = null == $mapped_domain ? get_site_url( $id ) : $mapped_domain;
			// Just need the domain
			$domain = str_replace(array('https:','http:','/'), array('','',''), $url);

			$out .= $id . ',' . $domain . ',';

			$user_query = new \WP_User_Query( array(
				'role' => 'Administrator',
				'blog_id' => $id,
				'fields' => 'user_email'
			) );
				
			$results = $user_query->get_results();
			$out .= '"' . join(';', $results) . '"' . PHP_EOL;
		}
		$out .= '</pre>';
		echo $out;

	}
	
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
