<div class="wrap">
	<h1><?php _e( 'CODJA Woocommerce Ajax Search Settings', 'cj-wc-ajax-search' ); ?></h1>

	<form method="POST" action="">
		<?php wp_nonce_field( 'update_cj_wc_ajax_search_settings' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="select_where_load_scripts"><?php _e( 'Where load plugin styles and scripts', 'cj-wc-ajax-search' ); ?></label></th>
				<td>
					<select name="cj_wc_ajax_search_settings[load_scripts]" id="select_where_load_scripts">
						<option value="everywhere" <?php if ( $this->settings['load_scripts'] == 'everywhere' ) echo 'selected'; ?>><?php _e( 'Everywhere', 'cj-wc-ajax-search' ); ?></option>
						<option value="specific" <?php if ( $this->settings['load_scripts'] == 'specific' ) echo 'selected'; ?>><?php _e( 'On specific page', 'cj-wc-ajax-search' ); ?></option>
					</select>
				</td>
			</tr>
			<tr <?php if ( $this->settings['load_scripts'] == 'everywhere' ) echo 'style="display: none"'; ?>>
				<th scope="row"><label for="select_load_scripts_page"><?php _e( 'Select page', 'cj-wc-ajax-search' ); ?></label></th>
				<td>
					<?php
						$pages = get_posts( array(
							'post_type' => 'page',
							'post_status' => array( 'publish', 'draft', 'future' ),
							'posts_per_page' => -1
						) );
					?>
					<select name="cj_wc_ajax_search_settings[load_scripts_page]" id="select_load_scripts_page">
						<option value="homepage"><?php _e( 'Homepage', 'cj-wc-ajax-search' ); ?></option>
						<?php foreach ($pages as $page) { ?>
							<option value="<?php echo $page->ID; ?>" <?php if ($this->settings['load_scripts_page'] == $page->ID) echo 'selected'; ?>><?php echo $page->post_title; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="str_length"><?php _e( 'How much characters needed to start the search', 'cj-wc-ajax-search' ); ?></label></th>
				<td><input name="cj_wc_ajax_search_settings[str_length]" type="text" id="str_length" value="<?php echo $this->settings['str_length']; ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="product_count"><?php _e( 'How much product to show', 'cj-wc-ajax-search' ); ?></label></th>
				<td><input name="cj_wc_ajax_search_settings[product_count]" type="text" id="product_count" value="<?php echo $this->settings['product_count']; ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Ajax-loader image', 'cj-wc-ajax-search' ); ?></th>
				<td>
					<p><?php _e( 'Image', 'cj-wc-ajax-search' ); ?>: <img id="loader_image" src="<?php echo $this->settings['loader_image']; ?>" /></p>
					<input id="loading_image_url" name="cj_wc_ajax_search_settings[loader_image]" type="hidden" value="<?php echo $this->settings['loader_image']; ?>" data-default-loader="<?php echo CJ_WC_AJAX_SEARCH_URL . 'assets/images/ajax-loader.gif'; ?>" />
					<input type="file" name="new_loader_image" id="new_loader_image" data-upload-nonce="<?php echo wp_create_nonce( 'cj_wc_ajax_search_upload_loader' ); ?>"/>
					<span id="upload_button" class="button"><?php _e( 'Upload image', 'cj-wc-ajax-search' ); ?></span>
					<span id="reset_upload_button" class="button"><?php _e( 'Reset', 'cj-wc-ajax-search' ); ?></span>
					<p class="description"><?php _e( 'You can upload images no larger than 16x16px.', 'cj-wc-ajax-search' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Product fields to display', 'cj-wc-ajax-search' ); ?></th>
				<td>
					<fieldset>
						<label><input name="cj_wc_ajax_search_settings[product_fields][title]" type="checkbox"  value="1" <?php echo $this->settings['product_fields']['title'] != 0 ? 'checked' : ''; ?> />
							<?php _e( 'Product title', 'cj-wc-ajax-search' ); ?></label><br />
						<label><input name="cj_wc_ajax_search_settings[product_fields][image]" type="checkbox" value="1" <?php echo $this->settings['product_fields']['image'] != false ? 'checked' : ''; ?> />
							<?php _e( 'Product image', 'cj-wc-ajax-search' ); ?></label><br />
						<label><input name="cj_wc_ajax_search_settings[product_fields][description]" type="checkbox" value="1" <?php echo $this->settings['product_fields']['description'] != false ? 'checked' : ''; ?> />
							<?php _e( 'Product description', 'cj-wc-ajax-search' ); ?></label><br />
						<label><input name="cj_wc_ajax_search_settings[product_fields][price]" type="checkbox" value="1" <?php echo $this->settings['product_fields']['price'] != false ? 'checked' : ''; ?> />
							<?php _e( 'Price', 'cj-wc-ajax-search' ); ?></label><br />
						<label><input name="cj_wc_ajax_search_settings[product_fields][sale_price]" type="checkbox" value="1" <?php echo $this->settings['product_fields']['sale_price'] != false ? 'checked' : ''; ?> />
							<?php _e( 'Sale price', 'cj-wc-ajax-search' ); ?></label><br />
						<label><input name="cj_wc_ajax_search_settings[product_fields][add_to_cart]" type="checkbox" value="1" <?php echo $this->settings['product_fields']['add_to_cart'] != false ? 'checked' : ''; ?> />
							<?php _e( 'Add to cart', 'cj-wc-ajax-search' ); ?></label><br />
						<label><input name="cj_wc_ajax_search_settings[product_fields][remove_from_cart]" type="checkbox" value="1" <?php echo $this->settings['product_fields']['remove_from_cart'] != false ? 'checked' : ''; ?> />
							<?php _e( 'Remove from cart', 'cj-wc-ajax-search' ); ?></label><br />
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="custom_css"><?php _e( 'Custom css', 'cj-wc-ajax-search' ); ?></label></th>
				<td><textarea name="cj_wc_ajax_search_settings[custom_css]" rows="10" cols="50" id="custom_css" class="code"><?php echo stripslashes($this->settings['custom_css']); ?></textarea></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save settings', 'cj-wc-ajax-search' ); ?>"></p>
	</form>
</div>