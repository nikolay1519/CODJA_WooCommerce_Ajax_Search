<div id="cjAjaxSearch_product__<?php echo $product->ID; ?>" class="cjAjaxSearch_product cjClear">
	<?php $permalink = get_the_permalink( $product ); ?>
	<?php if ( $this->settings['product_fields']['image'] != false ) { ?>
		<div class="cjAjaxSearch_product__image"><a href="<?php echo $permalink; ?>"><?php echo $wc_product->get_image(); ?></a></div>
	<?php } ?>

	<div class="cjAjaxSearch_product__meta">
		<?php if ( $this->settings['product_fields']['title'] != false ) { ?>
			<div class="cjAjaxSearch_product__title"><a href="<?php echo $permalink; ?>"><?php echo $wc_product->get_title(); ?></a></div>
		<?php } ?>

		<?php if ( $this->settings['product_fields']['price'] != false ) { ?>
			<div class="cjAjaxSearch_product__price"><?php echo $wc_product->get_price_html(); ?></div>
		<?php } ?>

		<?php if ( $this->settings['product_fields']['description'] != false ) { ?>
			<div class="cjAjaxSearch_product__description"><?php echo $this->doDescription($wc_product); ?></div>
		<?php } ?>

		<?php if ( $wc_product->is_in_stock() ) { ?>
			<div class="cjAjaxSearch_product__buttons cjClear">
				<?php
					$cart_item_key = $this->getProductKeyFromCart( $product->ID );
					if ( $cart_item_key != false ) {
						$this->printProductRemoveButton( $product->ID, $cart_item_key );
					} else {
						if ($wc_product->get_price() != false) {
							$this->printProductAddButton( $product->ID );
						}
					}
				?>
			</div>
		<?php } else { ?>
			<div class="cjAjaxSearch_product__outOfStock"><?php _e( 'Out of stock', 'cj-wc-ajax-search' ); ?></div>
		<?php } ?>
	</div>
</div>