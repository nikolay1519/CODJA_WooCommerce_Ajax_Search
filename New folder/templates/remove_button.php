<div class="cjAjaxSearch_product__buttonBox">
	<?php if ( $this->settings['product_fields']['remove_from_cart'] ) { ?>
		<input class="cjAjaxSearch_product__qty" type="number" size="1" name="qty" min="1" max="<?php echo $cart_item['quantity']; ?>" value="<?php echo $cart_item['quantity']; ?>">
		<span class="cjAjaxSearch_product__button cjAjaxSearch_product__deleteFromCart" data-cart-item-key="<?php echo $cart_item_key; ?>" data-product-id="<?php echo $product_id; ?>">
			<?php _e( 'Remove from cart', 'cj-wc-ajax-search'); ?>
		</span>
		<span class="cjAjaxSearch_product__button_tooltip"></span>
	<?php } ?>
</div>