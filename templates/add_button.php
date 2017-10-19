<div class="cjAjaxSearch_product__buttonBox">
	<?php if ( $this->settings['product_fields']['add_to_cart'] ) { ?>
		<input class="cjAjaxSearch_product__qty" type="number" size="1" name="qty" min="1" max="10" value="1">
		<span class="cjAjaxSearch_product__button cjAjaxSearch_product__addToCart" data-product-id="<?php echo $product_id; ?>">
			<?php _e( 'Add to cart', 'cj-wc-ajax-search' ); ?>
		</span>
		<span class="cjAjaxSearch_product__button_tooltip"></span>
	<?php } ?>
</div>