<?php
if ( class_exists( 'SP_Ifeed' ) ) {wp_die( __('iFeed-error: duplicate class found: "SP_Ifeed"') );}else{
	class SP_Ifeed {

		// class instance
		static $instance;

		// customer WP_List_Table object
		public $ifeeds_obj;

		// class constructor
		public function __construct() {
			add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
			add_action( 'admin_menu', [ $this, 'ifeed_custom_admin_menu' ] );
		}
		
		public static function set_screen( $status, $option, $value ) {
			return $value;
		}		
		
		public function ifeed_custom_admin_menu() {
			$hook = add_options_page(
				'iFeed panel',
				'iFeed',
				'manage_options',
				'ifeed-settings',
				[ $this, 'ifeed_options_page_list' ]
			);
			add_action( "load-$hook", [ $this, 'screen_option' ] );
			add_submenu_page(
				null,
				null,
				null,
				'manage_options',
				'ifeed-settings-edit',
				'ifeed_options_page_edit'
			);
		}
		
		/**
		* Screen options
		*/
		public function screen_option() {

			$option = 'per_page';
			$args   = [
				'label'   => 'Ifeeds',
				'default' => 5,
				'option'  => 'ifeeds_per_page'
			];

			add_screen_option( $option, $args );

			$this->ifeeds_obj = new Ifeed_List();
		}
		
		/**
		* Plugin settings page
		*/
		public function ifeed_options_page_list() {
			defined('ABSPATH') or die('Direct access to this script is blocked.');
			?>
			<div class="wrap">
				<h2>
				iFeed Plugin
				<a class="page-title-action" href="?page=ifeed-settings-edit">Add New</a>
				<?php if($_GET['action']=="delete") {wp_redirect( "?page=ifeed-settings" ); ?><a class="page-title-action" href="?page=ifeed-settings">Back to iFeeds List</a><?php } ?>
				</h2>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="post">
									<?php
									$this->ifeeds_obj->prepare_items();
									$this->ifeeds_obj->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
		<?php
		}
		
		/** Singleton instance */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}
?>