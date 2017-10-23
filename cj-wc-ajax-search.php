<?php
	
	/**
	 * Plugin Name: CODJA WC Ajax Search
	 * Description: Ajax Search for Woocommerce
	 * Version: 1.0.0
	 * Author: CODJA
	 * Text Domain: cj-wc-ajax-search
	 * Domain Path: /languages/
	 * 
	 */

	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	define('CJ_WC_AJAX_SEARCH_VERSION', '1.0');
	define('CJ_WC_AJAX_SEARCH_DIR', plugin_dir_path(__FILE__));
	define('CJ_WC_AJAX_SEARCH_URL', plugin_dir_url(__FILE__));
	
	// If WooCommerce is active
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		
		register_activation_hook( __FILE__, array( 'Cj_WooCommerce_Ajax_Search', 'activation' ) );
		register_deactivation_hook( __FILE__, array( 'Cj_WooCommerce_Ajax_Search', 'deactivation' ) );
		register_uninstall_hook(__FILE__, array( 'Cj_WooCommerce_Ajax_Search', 'uninstall' ));
		
		class Cj_WooCommerce_Ajax_Search {
			
			private static $instance = null;

			private $settings = array();
			
			public static function getInstance() {
				if (null === self::$instance) {
					self::$instance = new self();
				}

				return self::$instance;
			}


			/**
			 * No clone
			 */
			private function __clone() {}


			/**
			 * Constructor of the class
			 */
			private function __construct() {
				// Load texdomain
				load_plugin_textdomain( 'cj-wc-ajax-search', false, CJ_WC_AJAX_SEARCH_DIR . '/languages/' );

				// Load settings
				$this->loadSettings();

				if ( is_admin() ) {
					if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
						// Upload ajax-loader from settings page
						add_action( 'wp_ajax_CJ_WooCommerce_Ajax_Search_upload_loader', array( $this, 'uploadImageAction' ) );

						// Products search
						add_action( 'wp_ajax_CJ_WooCommerce_Ajax_Search_process', array( $this, 'processSearch' ) );
						add_action( 'wp_ajax_nopriv_CJ_WooCommerce_Ajax_Search_process', array( $this, 'processSearch' ) );

						// Add to cart
						add_action( 'wp_ajax_CJ_WooCommerce_Ajax_Search_add_to_cart', array( $this, 'addToCart' ) );
						add_action( 'wp_ajax_nopriv_CJ_WooCommerce_Ajax_Search_add_to_cart', array( $this, 'addToCart' ) );

						// Remove from cart
						add_action( 'wp_ajax_CJ_WooCommerce_Ajax_Search_remove_from_cart', array( $this, 'removeFromCart' ) );
						add_action( 'wp_ajax_nopriv_CJ_WooCommerce_Ajax_Search_remove_from_cart', array( $this, 'removeFromCart' ) );
					} else {
						// Enqueue admin styles and scripts
						add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueueScript') );

						// Make settings page in admin
						add_action( 'admin_menu', array( $this, 'adminMenu' ) );
					}
				} else {
					// Enqueue frontend scripts and styles
					add_action( 'wp_enqueue_scripts', array( $this, 'frontendEnqueueScripts' ) );

					// Add shortcode
					add_shortcode( 'cj_wc_ajax_search', array( $this, 'doShortcode' ) );
				}
			}

			public function doShortcode($atts) {
				return '<input type="text" class="cjAjaxInput" value="" />';
			}

			/**
			 * Products search
			 */
			public function processSearch() {
				if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'cj_wc_ajax_search_process' ) ) {
					$search_string = isset( $_POST['search_string'] ) ? sanitize_text_field( $_POST['search_string'] ) : '';

					// Check search string length
					if (mb_strlen($search_string) < $this->settings['str_length']) {
						wp_send_json( array( 'status' => 'error', 'message' => 'String too small' ));
					}

					$args = array(
						's' => $search_string,
						'post_type' => 'product',
						'posts_per_page' => $this->settings['product_count']
					);

					$products = get_posts( $args );

					// Found products
					if ( $products != false ) {
						ob_start();

						// Generate html for found products
						foreach ( $products as $product ) {
							$product_id = $product->ID;
							$wc_product = new WC_Product($product);

							require(CJ_WC_AJAX_SEARCH_DIR . '/templates/product.php');
						}

						$content = ob_get_clean();

						wp_send_json( array( 'status' => 'success', 'products_count' => count($products), 'html' => $content ) );
					} else { // Not found products
						ob_start();

						require(CJ_WC_AJAX_SEARCH_DIR . '/templates/no_products.php');

						$content = ob_get_clean();

						wp_send_json( array( 'status' => 'success', 'products_count' => 0, 'html' => $content ) );
					}
				} else {
					wp_send_json( array( 'status' => 'error', 'message' => 'Access denied' ), 403 );
				}
			}


			/**
			 * Ajax add to cart
			 */
			public function addToCart() {
				$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) );
				$quantity = isset( $_POST['qty'] ) ? intval($_POST['qty']) : 1;
				$product_status = get_post_status( $product_id );

				if ( $quantity <= 0 ) {
					$quantity = 1;
				}

				$wc_product = new WC_Product($product_id);
				// If product stock < quantity for add
				if ( !is_null($wc_product->get_stock_quantity()) && $wc_product->get_stock_quantity() != false && $wc_product->get_stock_quantity() < $quantity) {
					$quantity = $wc_product->get_stock_quantity();
				}

				if ( WC()->cart->add_to_cart( $product_id, $quantity ) !== false && $product_status === 'publish' ) {
					// Get fragments
					$data = $this->getRefreshedFragments( $product_id );
					$data['message'] = $quantity . ' ' . __( 'added to cart', 'cj-wc-ajax-search' );

					wp_send_json( $data );
				} else {
					$data = array(
						'status' => 'error',
						'product_url' => get_permalink( $product_id ),
						'stock' => $wc_product->get_stock_quantity(),
						'qty' => $quantity,
						'notices' => WC()->session->get('wc_notices', array())
					);

					wp_send_json( $data );
				}
			}


			/**
			 * Ajax remove from cart
			 */
			public function removeFromCart() {
				$product_id = absint( $_POST['product_id'] );
				$cart_item_key = wc_clean( $_POST['cart_item_key'] );
				$quantity = isset( $_POST['qty'] ) ? intval($_POST['qty']) : 1;

				$product_in_cart = WC()->cart->get_cart();

				if ( isset( $product_in_cart[$cart_item_key] ) ) {
					// If quantity for remove >= quantity in cart, completely remove product from cart
					if ( $quantity >= $product_in_cart[$cart_item_key]['quantity'] ) {
						WC()->cart->remove_cart_item( $cart_item_key );

						// Get fragments
						$data = $this->getRefreshedFragments( $product_id );
						$data['message'] = __( 'Product removed from cart', 'cj-wc-ajax-search' );
					} else {
						// If quantity for remove < quantity in cart, set new quantity
						WC()->cart->set_quantity( $cart_item_key, $product_in_cart[$cart_item_key]['quantity'] - $quantity );

						// Get fragments
						$data = $this->getRefreshedFragments( $product_id );
						$data['message'] = $quantity . ' ' . __( 'removed from cart', 'cj-wc-ajax-search' );
					}

					wp_send_json( $data );
				} else {
					$data = array(
						'status' => 'error',
						'product_url' => get_permalink( $product_id )
					);

					wp_send_json( $data );
				}
			}



			/**
			 * Return fragments
			 */
			private function getRefreshedFragments( $product_id ) {
				ob_start();

				woocommerce_mini_cart();

				$mini_cart = ob_get_clean();

				$fragments = array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>'
				);

				$data = array(
					'status' => 'success',
					'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', $fragments ),
					'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() ? md5( json_encode( WC()->cart->get_cart_for_session() ) ) : '', WC()->cart->get_cart_for_session() ),
				);

				// Get product key from cart
				$cart_item_key = $this->getProductKeyFromCart( $product_id );

				// Generating necessary button fragment
				if ( $cart_item_key != false) { // in cart
					ob_start();
					$this->printProductRemoveButton( $product_id, $cart_item_key );
					$data['fragments']['div#cjAjaxSearch_product__' . $product_id . ' div.cjAjaxSearch_product__buttonBox'] = ob_get_clean();
				} else { // not in cart
					ob_start();
					$this->printProductAddButton( $product_id );
					$data['fragments']['div#cjAjaxSearch_product__' . $product_id . ' div.cjAjaxSearch_product__buttonBox'] = ob_get_clean();
				}

				return $data;
			}



			/**
			 * Print 'remove from cart' button
			 */
			private function printProductRemoveButton( $product_id, $cart_item_key ) {
				$cart_items = WC()->cart->get_cart();
				$cart_item = $cart_items[ $cart_item_key ];
				require(CJ_WC_AJAX_SEARCH_DIR . '/templates/remove_button.php');
			}

			/**
			 * Print 'add to cart' button
			 */
			private function printProductAddButton( $product_id ) {
				require(CJ_WC_AJAX_SEARCH_DIR . '/templates/add_button.php');
			}

			/**
			 * Search product in cart and return his key
			 */
			private function getProductKeyFromCart( $product_id ) {
				foreach( WC()->cart->get_cart() as $cart_item_key => $product ) {
					if( $product['product_id'] == $product_id ) {
						return $cart_item_key;
					}
				}

				return false;
			}

			/**
			 * Enqueue frontend scripts and styles
			 */
			public function frontendEnqueueScripts() {
				if ( $this->settings['load_scripts'] != 'everywhere' ) {

					if ( $this->settings['load_scripts_page'] == 'homepage' ) {
						if ( is_front_page() ) {
							$this->enqueueScripts();
						}
					} else {
						if ( is_page( $this->settings['load_scripts_page'] ) ) {
							$this->enqueueScripts();
						}
					}
				} else {
					$this->enqueueScripts();
				}
			}

			private function enqueueScripts() {
				if ( is_rtl() ) {
					wp_enqueue_style( 'cj-wc-ajax-search-style', CJ_WC_AJAX_SEARCH_URL . 'assets/css/style-rtl.css');
				} else {
					wp_enqueue_style( 'cj-wc-ajax-search-style', CJ_WC_AJAX_SEARCH_URL . 'assets/css/style.css');
				}

				wp_add_inline_style( 'cj-wc-ajax-search-style', stripslashes(htmlspecialchars_decode($this->settings['custom_css'])) );

				wp_enqueue_script( 'cj-wc-ajax-search-script', CJ_WC_AJAX_SEARCH_URL . 'assets/js/script.js', array('jquery'), null, true );

				// Print plugin settings obj
				$obj = array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'settings' => array(
						'str_length' => $this->settings['str_length'],
						'loader_image' => $this->settings['loader_image'],
						'nonce' => wp_create_nonce( 'cj_wc_ajax_search_process' )
					)
				);
				wp_localize_script( 'cj-wc-ajax-search-script', 'cj_wc_ajax_search_obj', $obj);
			}

			/**
			 * Enqueue admin scripts and styles
			 */
			public function adminEnqueueScript( $hook_suffix ) {
				// Подключаем скрипты только на нашей странице настроек
				if ( $hook_suffix == 'settings_page_cj-wc-ajax-search-settings' ) {
					wp_enqueue_style( 'cj-wc-ajax-search-style', CJ_WC_AJAX_SEARCH_URL . 'assets/css/admin/style.css');
					wp_enqueue_script( 'cj-wc-ajax-search-script', CJ_WC_AJAX_SEARCH_URL . 'assets/js/admin/admin-script.js', array('jquery'), null, true );
				}
			}

			public function adminMenu() {
				// Add settings page in admin
				add_options_page(
					__('CODJA Woocommerce Ajax Search Settings', 'cj-wc-ajax-search'),
					__('CODJA Woocommerce Ajax Search Settings', 'cj-wc-ajax-search'),
					'manage_options',
					'cj-wc-ajax-search-settings',
					array($this, 'renderSettingsPage')
				);
			}

			/**
			 * Upload ajax-loader
			 */
			public function uploadImageAction() {
				// Проверка nonce
				if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'cj_wc_ajax_search_upload_loader' ) ) {
					if ( isset( $_FILES['file'] ) ) {
						// Change default uploads dirs
						add_filter( 'upload_dir', array( $this, 'changeUploadDir' ) );

						$file = $_FILES['file'];
						$_FILES['file']['name'] = sanitize_file_name($_FILES['file']['name']);

						// Check file type
						$file_type = wp_check_filetype( basename( $file['name'] ) );
						if ( $file_type['type'] != 'image/gif' ) {
							wp_send_json( array( 'status' => 'error', 'message' => 'Bad file type' ) );
						}

						// Check file size
						$size = getimagesize( $file['tmp_name'] );
						if ( $size == false ) {
							wp_send_json( array( 'status' => 'error', 'message' => 'Bad file type' ) );
						} elseif ( $size[0] > 20 || $size[1] > 20 ) {
							wp_send_json( array( 'status' => 'error', 'message' => 'Image too large' ) );
						}

						if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );

						$file = &$_FILES['file'];
						$overrides = array( 'test_form' => false );

						$movefile = wp_handle_upload( $file, $overrides );

						if ( isset( $movefile['error'] ) ) {
							wp_send_json( array( 'status' => 'error', 'message' => 'File upload error' ) );
						} else {
							wp_send_json( array( 'status' => 'success', 'img' => $movefile['url'] ) );
						}

					}
				} else {
					wp_send_json( array( 'status' => 'error', 'message' => 'Access denied' ), 403 );
				}
			}

			/**
			 * Change default uploads dirs
			 *
			 * @param $uploads
			 *
			 * @return array
			 */
			public function changeUploadDir( $uploads ) {
				$uploads['path'] = ABSPATH . '/wp-content/uploads/cj_wc_ajax_search_uploads';
				$uploads['url'] = get_home_url() . '/wp-content/uploads/cj_wc_ajax_search_uploads';
				$uploads['subdir'] = '/cj_wc_ajax_search_uploads';

				return $uploads;
			}

			/**
			 * Print settings page
			 */
			public function renderSettingsPage() {
				// Update settings if form is submited
				if ( isset( $_POST['cj_wc_ajax_search_settings'] ) ) {
					if ( current_user_can( 'manage_options' ) ) {
						// nonce verify
						check_admin_referer( 'update_cj_wc_ajax_search_settings' );

						$this->saveSettings();
					}
				}

				require_once(CJ_WC_AJAX_SEARCH_DIR . '/templates/admin/settings_page.php');
			}


			/**
			 * Process and save settings
			 */
			private function saveSettings() {
				$new_settings = array();

				if ( isset( $_POST['cj_wc_ajax_search_settings']['load_scripts'] ) && in_array( $_POST['cj_wc_ajax_search_settings']['load_scripts'], array( 'everywhere', 'specific' ) ) ) {
					$new_settings['load_scripts'] = sanitize_text_field($_POST['cj_wc_ajax_search_settings']['load_scripts']);

					if ( $new_settings['load_scripts'] == 'specific' ) {
						if ( isset( $_POST['cj_wc_ajax_search_settings']['load_scripts_page'] ) && intval( $_POST['cj_wc_ajax_search_settings']['load_scripts_page'] ) != false ) {
							$new_settings['load_scripts_page'] = intval( $_POST['cj_wc_ajax_search_settings']['load_scripts_page'] );
						} else {
							$new_settings['load_scripts_page'] = 'homepage';
						}
					}
				} else {
					$new_settings['load_scripts'] = 'everywhere';
				}

				if ( isset( $_POST['cj_wc_ajax_search_settings']['str_length'] ) && intval( $_POST['cj_wc_ajax_search_settings']['str_length'] ) != false ) {
					$new_settings['str_length'] = intval( $_POST['cj_wc_ajax_search_settings']['str_length'] );
				} else {
					$new_settings['str_length'] = $this->settings['str_length'];
				}

				if ( isset( $_POST['cj_wc_ajax_search_settings']['product_count'] ) && intval( $_POST['cj_wc_ajax_search_settings']['product_count'] ) != false ) {
					$new_settings['product_count'] = intval( $_POST['cj_wc_ajax_search_settings']['product_count'] );
				} else {
					$new_settings['product_count'] = $this->settings['product_count'];
				}

				if ( isset( $_POST['cj_wc_ajax_search_settings']['loader_image'] ) && $_POST['cj_wc_ajax_search_settings']['loader_image'] != false ) {
					$new_settings['loader_image'] = esc_url( $_POST['cj_wc_ajax_search_settings']['loader_image'] );
				} else {
					$new_settings['loader_image'] = $this->settings['loader_image'];
				}

				if ( isset( $_POST['cj_wc_ajax_search_settings']['custom_css'] ) ) {
					$new_settings['custom_css'] = wp_filter_nohtml_kses($_POST['cj_wc_ajax_search_settings']['custom_css']);
				} else {
					$new_settings['custom_css'] = $this->settings['custom_css'];
				}

				$new_settings['product_fields']['title'] = isset( $_POST['cj_wc_ajax_search_settings']['product_fields']['title'] ) ? 1 : 0;
				$new_settings['product_fields']['image'] = isset( $_POST['cj_wc_ajax_search_settings']['product_fields']['image'] ) ? 1 : 0;
				$new_settings['product_fields']['description'] = isset( $_POST['cj_wc_ajax_search_settings']['product_fields']['description'] ) ? 1 : 0;
				$new_settings['product_fields']['price'] = isset( $_POST['cj_wc_ajax_search_settings']['product_fields']['price'] ) ? 1 : 0;
				$new_settings['product_fields']['sale_price'] = isset( $_POST['cj_wc_ajax_search_settings']['product_fields']['sale_price'] ) ? 1 : 0;
				$new_settings['product_fields']['add_to_cart'] = isset( $_POST['cj_wc_ajax_search_settings']['product_fields']['add_to_cart'] ) ? 1 : 0;
				$new_settings['product_fields']['remove_from_cart'] = isset( $_POST['cj_wc_ajax_search_settings']['product_fields']['remove_from_cart'] ) ? 1 : 0;

				$this->updateSettings( $new_settings );
			}

			/**
			 * Cut post content for short description of product
			 */
			private function doDescription( $product, $maxlength = 155 ) {
				$post_excerpt = $product->post->post_excerpt;
				$post_content = $product->post->post_content;

				if ( $post_excerpt != false ) {
					$text = $post_excerpt;
				} else {
					$text = $post_content;
				}

				// cut shortcodes;
				$text = preg_replace ('~\[/?.*?\](?!\()~', '', $text );
				$text = trim( $text );

				// cut html-tags
				$text = trim( strip_tags( $text ) );

				// cut text to $maxlength chars
				if( mb_strlen($text) > $maxlength ){
					$text = mb_substr( $text, 0, $maxlength );
					// cut last word
					$text = preg_replace('~(.*)\s[^\s]*$~s', '\\1 ...', $text );
				}

				return $text;
			}

			private function updateSettings( $settings ) {
				update_option( 'cj_wc_ajax_search_settings', $settings );
				$this->settings = $settings;
			}

			private function loadSettings() {
				$this->settings = $this->getSettings();
			}

			private function getSettings() {
				return get_option( 'cj_wc_ajax_search_settings' );
			}

			public static function activation() {
				if ( ! current_user_can( 'activate_plugins' ) ) return;
				
				$defaultSettings = array(
					'load_scripts' => 'everywhere',
					'load_scripts_page' => 'homepage',
					'str_length' => 3,
					'product_count' => 5,
					'loader_image' => CJ_WC_AJAX_SEARCH_URL . 'assets/images/ajax-loader.gif',
					'product_fields' => array(
						'title' => 1,
						'image' => 1,
						'description' => 1,
						'price' => 1,
						'sale_price' => 1,
						'add_to_cart' => 1,
						'remove_from_cart' => 1,
					),
					'custom_css' => '',
				);
				
				add_option( 'cj_wc_ajax_search_settings', $defaultSettings );
			}

			public static function deactivation() {}

			public static function uninstall() {
				if ( ! current_user_can( 'activate_plugins' ) ) return;
				
				delete_option( 'cj_wc_ajax_search_settings' );
			}
		}

		Cj_WooCommerce_Ajax_Search::getInstance();
	}