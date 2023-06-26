<?php
/**
 *
 * Plugin Name:       CTLT Prepare Site Administrator Mailing List
 * Plugin URI:        https://ctlt.ubc.ca
 * Description:       Based on a list of site URLs, prepare a CSV file includes administrators email addresses.
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
		if( isset( $_POST['site_urls'] ) ) {
			get_mailing_list();
			return;
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_title( wp_unslash( $_GET['page'] ) ) : '';

		ob_start(); ?>
			<div class="wrap">
				<h1>Site Administrator Mailing List</h1>
			</div>
			<form method="post" action="<?php echo esc_url( network_admin_url( 'index.php?page=' . $current_page ) ); ?>" onsubmit = "return( validate());">
				<textarea name="site_urls" id="site_urls" cols="50" rows="10"></textarea>
				<br />
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Run">
			</form>

			<script>
				function validate() {
					let input = document.getElementById('site_urls').value;
					var urlPattern = new RegExp('^(https?:\\/\\/)?'+ // validate protocol
						'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // validate domain name
						'((\\d{1,3}\\.){3}\\d{1,3}))'+ // validate OR ip (v4) address
						'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // validate port and path
						'(\\?[;&a-z\\d%_.~+=-]*)?'+ // validate query string
						'(\\#[-a-z\\d_]*)?$','i'); // validate fragment locator

					if ( ! input ) {
						alert( 'Input not valid.' );
						return false;
					}

					input = input.split(',');
					input = input.map(function(element) {
						return element.replaceAll( '"', '' );
					});

					document.getElementById('site_urls').value = input.join(',');

					for( let i=0;i<input.length;i++){
						if ( ! validURL( input[i].replaceAll( '"', '' ) ) ) {
							alert( 'Input not valid.' );
							return false;
						}
					}

					return true;
				}
				
				function validURL(str) {
					var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
						'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
						'((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
						'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
						'(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
						'(\\#[-a-z\\d_]*)?$','i'); // fragment locator
					return !!pattern.test(str);
				}
			</script>
		<?php echo ob_get_clean();
	}

	function get_mailing_list() {
		$urls = explode(",", $_POST['site_urls']);
		$out = '<pre>';


		foreach ($urls as $key => $url) {
			$id = get_blog_id_from_url( $url );
			$out .= $id . ',' . $url . ',';

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
