/**
 * yab_shop
 *
 * A Textpattern CMS plugin for e-commerce / shopping cart / checkout
 *  -> Txp articles are products
 *  -> Product variants / prices / RRP supported
 *  -> Customisable shopping cart, checkout, tax and shipping options
 *  -> Integrates with PayPal and Google Checkout
 *
 * This plugin is released under the GNU General Public License Version 2 and above
 * Version 2: http://www.gnu.org/licenses/gpl-2.0.html
 * Version 3: http://www.gnu.org/licenses/gpl-3.0.html

 * @author Tommy Schmucker (trenc)
 * @author Stef Dawson (Bloke)
 * @author Steve Dickinson (netcarver)
 * @link   http://www.yablo.de/
 * @link   http://stefdawson.com/
 */

global $yab_shop_prefs, $yab_shop_public_lang;

// NB: may be empty if the table isn't installed
$yab_shop_prefs = yab_shop_get_prefs();

$_yab_shop_public_i18n = array(
	'price'                             => 'Price',
	'quantity'                          => 'Quantity',
	'sub_total'                         => 'Subtotal',
	'to_checkout'                       => 'Proceed to Checkout',
	'empty_cart'                        => 'No items in Cart',
	'add_to_cart'                       => 'Add to Cart',
	'table_caption_content'             => 'Content',
	'table_caption_change'              => 'Quantity',
	'table_caption_price'               => 'Price Sum',
	'custom_field_property_1'           => 'Size',
	'custom_field_property_2'           => 'Color',
	'custom_field_property_3'           => 'Variant',
	'checkout_tax_exclusive'            => '{tax}% Tax exclusive',
	'checkout_tax_inclusive'            => '{tax}% Tax inclusive (where applicable)',
	'checkout_shipping_unavailable'     => 'Please choose a different shipping method.',
	'checkout_free_shipping'            => 'Free!',
	'shipping_costs'                    => 'Shipping Costs',
	'grand_total'                       => 'Total',
	'checkout_edit'                     => 'Change Qty',
	'checkout_delete'                   => 'x',
	'promocode_label'                   => 'Promo code',
	'promocode_button'                  => 'Apply',
	'promocode_error'                   => 'Sorry, wrong promo code',
	'promocode_success'                 => '{discount}% promo discount applied to your order.',
	'checkout_required_field_notice'    => 'Required fields are indicated.',
	'checkout_firstname'                => 'First Name',
	'checkout_surname'                  => 'Last Name',
	'checkout_street'                   => 'Street',
	'checkout_postal'                   => 'ZIP Code',
	'checkout_city'                     => 'City',
	'checkout_state'                    => 'State',
	'checkout_country'                  => 'Country',
	'checkout_phone'                    => 'Phone',
	'checkout_email'                    => 'E-mail',
	'checkout_message'                  => 'Message',
	'checkout_tou'                      => 'Terms Of Use',
	'checkout_terms_of_use'             => 'I have read the <a href="{tou}" title="Terms of Use">Terms of Use</a>',
	'checkout_summary'                  => 'This table shows the cart with selected products and the total sum of the products.',
	'remember_me'                       => 'Remember my data for next visit (cookie)',
	'forget_me'                         => 'Forget my data',
	'checkout_order'                    => 'Purchase/Order',
	'checkout_legend'                   => 'Purchase Form',
	'checkout_payment_acc'              => 'Purchase on Account',
	'checkout_payment_pod'              => 'Purchase on Delivery',
	'checkout_payment_pre'              => 'Purchase against Prepayment',
	'checkout_payment_paypal'           => 'Purchase via Paypal',
	'checkout_payment_google'           => 'Purchase via Google Checkout',
	'checkout_payment'                  => 'Payment Method',
	'checkout_paypal_forward'           => 'You will be forwarded to Paypal. Please wait&hellip;',
	'checkout_paypal_button'            => 'Go to paypal',
	'checkout_paypal_no_forward'        => 'Please click the button to proceed.',
	'paypal_return_message'             => 'Thank you for purchasing.',
	'checkout_google_forward'           => 'You will be forwarded to Google Checkout. Please wait&hellip;',
	'checkout_google_no_forward'        => 'Please click the button to proceed.',
	'checkout_history_back'             => 'Back to Shop',
	'checkout_mail_error'               => 'Your order could not be sent',
	'checkout_mail_success'             => 'Your order was successfully sent',
	'checkout_mail_email_error'         => 'E-mail is invalid',
	'checkout_mail_affirmation_error'   => 'Your order was successfully sent but a confirmation e-mail could not be delivered to you (perhaps you did not supply an e-mail address?)',
	'checkout_mail_affirmation_success' => 'Your order and confirmation were successfully sent.',
	'checkout_mail_field_error'         => 'Please correctly fill out the following required fields:',
	'admin_mail_subject'                => 'Shop Order',
	'admin_mail_pre_products'           => 'The following was ordered:',
	'admin_mail_after_products'         => 'This text will be on the end of the admin e-mail',
	'admin_mail_promocode'              => 'The order is already calculated with promo discount',
	'affirmation_mail_subject'          => 'Your Shop Order',
	'affirmation_mail_pre_products'     => 'Thank you for shopping with us. Your order summary follows:',
	'affirmation_mail_after_products'   => '',
	'affirmation_mail_promocode'        => 'Your order is already calculated with promo discount',
	'cart_message_add'                  => 'Product has been added',
	'cart_message_edit'                 => 'Cart has been updated',
	'cart_message_del'                  => 'Product has been deleted',
	'has_options_hint'                  => 'More options available',
	'please_call'                       => '<b>Please call us</b>',
	'value_unavailable'                 => 'Unavailable',
);
$yab_shop_public_lang = new yab_shop_MLP( 'yab_shop' , $_yab_shop_public_i18n);

//============
// PUBLIC TAGS
//============
function yab_shop_cart($atts, $thing = null)
{
	global $thisarticle, $prefs;

	extract(lAtts(array(
		'output' => 'cart',
	), $atts));

	$articleid = $thisarticle['thisid'];
	$section = $thisarticle['section'];

	yab_shop_start_session();

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new yab_shop_wfCart();

	$custom_field_price = yab_shop_get_custom_field_names(yab_shop_config('custom_field_price_name'));
	$custom_field_price = ucfirst($custom_field_price);

	yab_promocode();

	if ($section)
	{
		$products = array();
		$products[$articleid] = safe_row("ID as id, Title as name, $custom_field_price as price", "textpattern", "ID = $articleid");
	}

	if (ps('yab-shop-id'))
	{
		$articleid = preg_replace("/[^0-9]/", '', ps('yab-shop-id'));
		$products = array();
		$products[$articleid] = safe_row("ID as id, Title as name, $custom_field_price as price", "textpattern", "ID = $articleid");
	}

	if (ps('add') != '')
	{
		$pqty = preg_replace("/[^0-9]/", '', ps('qty'));
		$product = $products[$articleid];
		$product_ps_1 = ps(yab_shop_ascii(strtolower(yab_shop_config('custom_field_property_1_name'))));
		$product_ps_1 = explode(': ',$product_ps_1);
		$product_property_1 = $product_ps_1[0];
		$product_ps_2 = ps(yab_shop_ascii(strtolower(yab_shop_config('custom_field_property_2_name'))));
		$product_ps_2 = explode(': ',$product_ps_2);
		$product_property_2 = $product_ps_2[0];
		$product_ps_3 = ps(yab_shop_ascii(strtolower(yab_shop_config('custom_field_property_3_name'))));
		$product_ps_3 = explode(': ',$product_ps_3);
		$product_property_3 = $product_ps_3[0];
		$product_id = $product['id'].'-'.yab_shop_check_item($cart, $product['id'], $product_property_1, $product_property_2, $product_property_3);
		$product_db_id = $product['id'];

		$pp_req = do_list(yab_shop_config('custom_field_property_required'));
		$pp_fail = (!$product_property_1 && in_array('property_1', $pp_req))
			|| (!$product_property_2 && in_array('property_2', $pp_req))
			|| (!$product_property_3 && in_array('property_3', $pp_req));

		$product_prices = do_list($product['price'], '|');
		$product_price = $product_prices[0];
		$product_rrp = (isset($product_prices[1])) ? $product_prices[1] : '';
		$product_price = yab_shop_replace_commas($product_price);
		$product_rrp = yab_shop_replace_commas($product_rrp);
		$product_price_saving = ($product_rrp) ? $product_rrp - $product_price : '';
		$p1_used = $p2_used = $p3_used = false;

		if (yab_shop_check_property_prices($product_db_id) != false)
		{
			$property_db_1 = '';
			$property_db_2 = '';
			$property_db_3 = '';
			if (yab_shop_config('custom_field_property_1_name') != '')
			{
				if (ps('yab-shop-id'))
				{
					$field_name_1 = yab_shop_get_custom_field_names(yab_shop_config('custom_field_property_1_name'));
					$property_db_1 = safe_field("`$field_name_1`", 'textpattern', "ID = $product_db_id");
				}
				else
					$property_db_1 = $thisarticle[strtolower(yab_shop_config('custom_field_property_1_name'))];
			}

			if (yab_shop_config('custom_field_property_2_name') != '')
			{
				if (ps('yab-shop-id'))
				{
					$field_name_2 = yab_shop_get_custom_field_names(yab_shop_config('custom_field_property_2_name'));
					$property_db_2 = safe_field("`$field_name_2`", 'textpattern', "ID = $product_db_id");
				}
				else
					$property_db_2 = $thisarticle[strtolower(yab_shop_config('custom_field_property_2_name'))];
			}

			if (yab_shop_config('custom_field_property_3_name') != '')
			{
				if (ps('yab-shop-id'))
				{
					$field_name_3 = yab_shop_get_custom_field_names(yab_shop_config('custom_field_property_3_name'));
					$property_db_3 = safe_field("`$field_name_3`", 'textpattern', "ID = $product_db_id");
				}
				else
					$property_db_3 = $thisarticle[strtolower(yab_shop_config('custom_field_property_3_name'))];
			}

			if ($product_ps_1[1] != '')
			{
				$product_db_1_array = explode(';', $property_db_1);
				foreach ($product_db_1_array as $product_db_1_part)
				{
					$product_db_1_part_array = explode('--', $product_db_1_part);
					if (trim($product_db_1_part_array[0]) == trim($product_ps_1[0]))
					{
						$product_price = yab_shop_replace_commas(preg_replace('/[^{\,,\.}0-9]/', '', $product_db_1_part_array[1]));
						break;
					}
				}
			}
			if ($product_ps_2[1] != '')
			{
				$product_db_2_array = explode(';', $property_db_2);
				foreach ($product_db_2_array as $product_db_2_part)
				{
					$product_db_2_part_array = explode('--', $product_db_2_part);
					if (trim($product_db_2_part_array[0]) == trim($product_ps_2[0]))
					{
						$product_price = yab_shop_replace_commas(preg_replace('/[^{\,,\.}0-9]/', '', $product_db_2_part_array[1]));
						break;
					}
				}
			}
			if ($product_ps_3[1] != '')
			{
				$product_db_3_array = explode(';', $property_db_3);
				foreach ($product_db_3_array as $product_db_3_part)
				{
					$product_db_3_part_array = explode('--', $product_db_3_part);
					if (trim($product_db_3_part_array[0]) == trim($product_ps_3[0]))
					{
						$product_price = yab_shop_replace_commas(preg_replace('/[^{\,,\.}0-9]/', '', $product_db_3_part_array[1]));
						break;
					}
				}
			}
		}

		$product_spec_shipping = '';
		if (yab_shop_config('custom_field_shipping_name') != '')
		{
			if (ps('yab-shop-id'))
			{
				$shipping_field_name = yab_shop_get_custom_field_names(yab_shop_config('custom_field_shipping_name'));
				$product_spec_shipping = safe_field("`$shipping_field_name`", 'textpattern', "ID = $product_id");
			}
			else
				$product_spec_shipping = $thisarticle[strtolower(yab_shop_config('custom_field_shipping_name'))];
		}

		$product_weight = 0;
		if (yab_shop_config('custom_field_weight') != '') {
			$weight_field = strtolower(yab_shop_config('custom_field_weight'));
			$product_weight = (isset($thisarticle[$weight_field])) ? $thisarticle[$weight_field] : 0;
		}

		list($product_tax_bands, $product_tax) = yab_shop_tax_rates();
		if (yab_shop_config('custom_field_tax_band') != '') {
			$tax_idx = $thisarticle[strtolower(yab_shop_config('custom_field_tax_band'))] - 1;
			$product_tax = isset($product_tax_bands[$tax_idx]) ? $product_tax_bands[$tax_idx] : $product_tax_bands[0];
		}

		$callback_params = array(
			'id'           => $product_id,
			'article_id'   => $articleid,
			'qty'          => $pqty,
			'price'        => $product_price,
			'rrp'          => $product_rrp,
			'price_saving' => $product_price_saving,
			'name'         => $product['name'],
			'property_1'   => $product_property_1,
			'property_2'   => $product_property_2,
			'property_3'   => $product_property_3,
			'special_ship' => $product_spec_shipping,
			'tax'          => $product_tax,
			'weight'       => $product_weight,
		);
		$block_action = callback_event( 'yab_shop_cart_add', '', 1, $cart, $callback_params );
		if( strlen($block_action) === 0 )
		{
			if ($pp_fail == false) {
				$cart->add_item($product_id, $articleid, $pqty, $product_price, $product_rrp, $product_price_saving, $product['name'], $product_property_1, $product_property_2, $product_property_3, $product_spec_shipping, $product_tax, $product_weight);
				callback_event( 'yab_shop_cart_add', '', 0, $cart, $callback_params );
			}
		}
	}

	if (ps('edit') != '')
	{
		$qty = preg_replace("/[^0-9]/", '', ps('editqty'));
		$id = preg_replace("/[^{\\-}0-9]/", '', ps('editid'));

		if ($qty != '' and $id != '')
		{
			$block_action = callback_event( 'yab_shop_cart_item_update', '', 1, $cart, $id, $qty );
			if( strlen($block_action) === 0 )
			{
				$cart->edit_item($id, $qty);
				callback_event( 'yab_shop_cart_item_update', '', 0, $cart, $id, $qty );
			}
		}
		else
		{
			$block_action = callback_event( 'yab_shop_cart_remove', '', 1, $cart, $id );
			if( strlen($block_action) === 0 )
			{
				$cart->del_item($id);
				callback_event( 'yab_shop_cart_remove', '', 0, $cart, $id );
			}
		}
	}

	if (ps('del') != '')
	{
		$id = preg_replace("/[^{\\-}0-9]/", '', ps('editid'));

		$block_action = callback_event( 'yab_shop_cart_remove', '', 1, $cart, $id );
		if( strlen($block_action) === 0 )
		{
			$cart->del_item($id);
			callback_event( 'yab_shop_cart_remove', '', 0, $cart, $id );
		}
	}

	if (ps('shipping_band')) {
		$cart->set_ship_method(ps('shipping_band'));
	}

	if ($output == 'cart')
	{
		if ($thing === null)
			$out = yab_shop_cart_items();
		else
			$out = parse($thing);
	}
	else
		$out = '';

	return $out;
}

function yab_shop_cart_items()
{
	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new yab_shop_wfCart();

	return yab_shop_build_cart($cart);
}

function yab_shop_cart_subtotal($atts)
{
	extract(lAtts(array(
		'showalways' => '1',
		'break'      => br,
		'label'      => yab_shop_lang('sub_total'),
		'wraptag'    => '',
		'class'      => '',
		'separator'  => ': ',
	), $atts));

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new yab_shop_wfCart();

	if ($label)
		$label = htmlspecialchars($label.$sep);

	$out = '';
	if ($showalways == '1')
	{
		$out .= $label;
		$out .= yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total);
		$out = doTag($out, $wraptag, $class).$break;
	}
	else
	{
		if ($cart->itemcount > 0)
		{
			$out .= $label;
			$out .= yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total);
			$out = doTag($out, $wraptag, $class).$break;
		}
	}
	return $out;
}

function yab_shop_cart_quantity($atts)
{
	extract(lAtts(array(
		'output'     => 'single',
		'showalways' => '1',
		'break'      => br,
		'label'      => yab_shop_lang('quantity'),
		'wraptag'    => '',
		'class'      => '',
		'separator'  => ': ',
	), $atts));

	yab_shop_start_session();

	if ($label)
		$label = htmlspecialchars($label.$sep);

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new yab_shop_wfCart();

	$qty = 0;
	$out = '';

	if ($output == 'single')
		$qty += $cart->itemcount;
	else
	{
		if ($cart->itemcount > 0)
		{
			foreach ($cart->get_contents() as $item)
			{
				$qty += $item['qty'];
			}
		}
	}

	if ($showalways == '1')
	{
		$out .= $label.$qty;
		$out = doTag($out, $wraptag, $class).$break;
	}
	else
	{
		if ($cart->itemcount > 0)
		{
			$out .= $label.$qty;
			$out = doTag($out, $wraptag, $class).$break;
		}
	}
	return $out;
}

function yab_shop_cart_message($atts)
{
	extract(lAtts(array(
		'add'     => yab_shop_lang('cart_message_add'),
		'edit'    => yab_shop_lang('cart_message_edit'),
		'del'     => yab_shop_lang('cart_message_del'),
		'break'   => br,
		'wraptag' => '',
		'class'   => ''
	), $atts));

	$message = '';

	if (ps('add'))
		$message = htmlspecialchars($add);
	elseif (ps('edit'))
		$message = htmlspecialchars($edit);
	elseif (ps('del'))
		$message = htmlspecialchars($del);

	return (!empty($message)) ? doTag($message, $wraptag, $class).$break : '';
}

function yab_shop_cart_link($atts)
{
	extract(lAtts(array(
		'label'      => yab_shop_lang('to_checkout'),
		'break'      => br,
		'showalways' => '1',
		'wraptag'    => '',
		'class'      => ''
	), $atts));

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new yab_shop_wfCart();

	$url = yab_shop_mlp_inject( pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))));
	$label = htmlspecialchars($label);
	$out = '';

	if ($class and !$wraptag)
		$link = href($label, $url, ' title="'.$label.'" class="'.$class.'"');
	else
		$link = href($label, $url, ' title="'.$label.'"');

	if ($showalways == '1')
		$out = doTag($link, $wraptag, $class).$break;
	else
	{
		if ($cart->itemcount > 0)
			$out = doTag($link, $wraptag, $class).$break;
	}

	return $out;
}

function yab_shop_checkout($atts)
{
	global $yab_shop_prefs;

	extract(lAtts(array(
		'summary' => yab_shop_lang('checkout_summary'),
	), $atts));

	yab_shop_start_session();
	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new yab_shop_wfCart();

	yab_promocode();

	$checkout_err = false;

	if ($cart->itemcount > 0)
	{
		$affirmation = yab_shop_config('order_affirmation_mail');
		$to_shop = graf(tag(yab_shop_lang('checkout_history_back'), 'a', ' href="'.yab_shop_mlp_inject(yab_shop_config('back_to_shop_link')).'"'), ' class="history-back"');
		$checkout_display = pluggable_ui('yab_shop', 'checkout_cart_preamble', '', $cart, $yab_shop_prefs);
		$checkout_display .= yab_shop_build_checkout_table($cart, $summary);

		$block_action = callback_event( 'yab_shop_on_checkout', '', 1, $cart, $yab_shop_prefs );
		if( '' !== $block_action )
			return $checkout_display.$block_action;	# The returned string will be displayed in the UI.

		$checkout_display .= yab_build_promo_input($cart);
		$checkout_display .= pluggable_ui('yab_shop', 'checkout_cart_postamble', '', $cart, $yab_shop_prefs);
		$checkout_message = graf(yab_shop_lang('checkout_required_field_notice'), ' class="yab-shop-notice"');
		$checkout_form = yab_shop_build_checkout_form();

		// Verify the shipping method is appropriate
		$ship_data = yab_shop_available_shipping_options();
		if (!$ship_data['method_available']) {
			$checkout_err = true;
			$checkout_message = graf(yab_shop_lang('checkout_shipping_unavailable'), ' class="yab-shop-notice"');
			$checkout_form = '';
		}

		// Covers eventuality that 'order' input type is an image or a <button>
		$is_order = ( (ps('order') != '') || (ps('order_x') != '') );

		if (!$checkout_err) {
			if ($is_order)
			{
				$ps_order = array();
				$ps_order = yab_shop_clean_input($_POST);
				$checkout_message = graf(yab_shop_lang('checkout_mail_field_error'), ' class="yab-shop-required-notice" id="yab-shop-checkout-anchor"');

				$notice = yab_shop_check_required_fields($ps_order);
				if ($notice != '')
				{
					$checkout_message .= tag($notice, 'ul', ' class="yab-shop-notice"');
					$checkout_form = yab_shop_build_checkout_form();
				}
				else
				{
					$checkout_display = '';
					$checkout_form = '';

					yab_remember(ps('remember'), ps('forget'), ps('checkbox_type'));
					switch (ps('payment'))
					{
						case yab_shop_lang('checkout_payment_paypal'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							if (yab_shop_config('use_encrypted_paypal_button') == '1')
								$checkout_form = yab_shop_build_paypal_encrypted_form($cart);
							else
								$checkout_form = yab_shop_build_paypal_form($cart);

							callback_event( 'yab_shop_on_checkout', 'paypal', 0, $cart );
							$cart->empty_cart();
							break;
						case yab_shop_lang('checkout_payment_google'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							$checkout_form = yab_shop_build_google_form($cart);
							callback_event( 'yab_shop_on_checkout', 'google', 0, $cart );
							$cart->empty_cart();
							break;
						default:
							$checkout_message = graf(yab_shop_lang('checkout_mail_error'), ' class="yab-shop-message"');
							if (yab_shop_shop_mail(yab_shop_config('admin_mail'), yab_shop_lang('admin_mail_subject'), yab_shop_build_mail_body($cart, $ps_order)))
							{
								if ($affirmation == '1') {
									$checkout_message = graf(yab_shop_lang('checkout_mail_affirmation_error'), ' class="yab-shop-message"');
									if (isset($ps_order['email']) and $ps_order['email'] != '') {
										if (yab_shop_shop_mail($ps_order['email'], yab_shop_lang('affirmation_mail_subject'), yab_shop_build_mail_body($cart, $ps_order, '1')))
										{
											yab_shop_redirect(yab_shop_config('checkout_thanks_site'));
											$checkout_message = graf(yab_shop_lang('checkout_mail_affirmation_success'), ' class="yab-shop-message"').$to_shop;
											callback_event( 'yab_shop_on_checkout', 'default,success', 0, $cart );
										}
										else
											callback_event( 'yab_shop_on_checkout', 'default,partial', 0, $cart );
									} else {
										callback_event( 'yab_shop_on_checkout', 'default,no_affirmation', 0, $cart );
									}
									$cart->empty_cart();
								} else {
									callback_event( 'yab_shop_on_checkout', 'default,success', 0, $cart );
									$cart->empty_cart();

									yab_shop_redirect(yab_shop_config('checkout_thanks_site'));
									$checkout_message = graf(yab_shop_lang('checkout_mail_success'), ' class="yab-shop-message"').$to_shop;
								}
							}
							else
								callback_event( 'yab_shop_on_checkout', 'default,failure', 0, $cart );
							break;
					}
				}
			}
		}
		return $checkout_display.$checkout_message.$checkout_form;
	}
	else
	{
		if (gps('return') != '')
		{
			yab_shop_redirect(yab_shop_config('checkout_thanks_site'));
			$checkout_display = graf(yab_shop_lang('paypal_return_message'), ' class="yab-shop-message"').graf(tag(yab_shop_lang('checkout_history_back'), 'a', ' href="'.yab_shop_mlp_inject(yab_shop_config('back_to_shop_link')).'"'), ' class="history-back"');
		}
		else
		  $checkout_display = callback_event( 'yab_shop_on_checkout_empty_cart' );

		if( '' === $checkout_display )
			$checkout_display = graf(yab_shop_lang('empty_cart'), ' class="yab-empty"');

		return $checkout_display;
	}
}

function yab_shop_add( $atts )
{
	extract(lAtts(array(
		'hide_options'         => '',
		'options_hint_class'   => 'yab_shop_option_hint',
		'options_hint_wraptag' => 'p',
		'options_hint'         => yab_shop_lang('has_options_hint'),
	), $atts));

	global $thisarticle, $is_article_list;

	$hide_options = ((int)$hide_options != 0);

	$id = $thisarticle['thisid'];
	$reqlist = do_list(yab_shop_config('custom_field_property_required'));
	$property_1_name = yab_shop_config('custom_field_property_1_name');
	$property_2_name = yab_shop_config('custom_field_property_2_name');
	$property_3_name = yab_shop_config('custom_field_property_3_name');
	$hinput = '';
	$purl = yab_shop_mlp_inject( permlinkurl_id($id));
	$script = '';

	if ($is_article_list == true)
	{
		$hinput = hInput('yab-shop-id', $id);
		if (serverSet('REQUEST_URI') and serverSet('HTTP_HOST'))
			$purl = PROTOCOL.serverSet('HTTP_HOST').serverSet('REQUEST_URI');
	}
	else
	  $hide_options = false;

	if (yab_shop_config('use_property_prices') == '1')
		$script .= yab_shop_property_prices($id).n;

	$options =
	  yab_shop_build_custom_select_tag($property_1_name, yab_shop_lang('custom_field_property_1'), $hide_options, in_array('property_1', $reqlist)).
		yab_shop_build_custom_select_tag($property_2_name, yab_shop_lang('custom_field_property_2'), $hide_options, in_array('property_2', $reqlist)).
		yab_shop_build_custom_select_tag($property_3_name, yab_shop_lang('custom_field_property_3'), $hide_options, in_array('property_3', $reqlist));

	$add_form = tag(
		$hinput.
		$options.
		graf(
			fInput('number','qty','1','','','','1').
			fInput('submit','add',yab_shop_lang('add_to_cart'),'submit'),
			' class="yab-add"'
		),
	'form', ' method="post" action="'.$purl.'#yab-shop-form-'.$id.'" id="yab-shop-form-'.$id.'"'
	);

	if($hide_options && strlen($options) > 0) {
	  if( !empty( $options_hint_class ) )
			$class = " class=\"$options_hint_class\"";
		else
		  $class = '';

		$options_hint = "<$options_hint_wraptag$class>$options_hint</$options_hint_wraptag>";
	}
	else
	  $options_hint = '';

	return $script.$add_form.$options_hint;
}

function yab_shop_price($atts)
{
	extract(lAtts(array(
		'type'    => 'price', // price, rrp, saving
		'raw'     => '0', // Whether to get the raw price minus currency symbol
		'wraptag' => 'span',
		'class'   => 'yab-shop-price'
	), $atts));

	global $thisarticle;
	$id = $thisarticle['thisid'];

	$custom_field = yab_shop_config('custom_field_price_name');
	$out = yab_shop_custom_field(array('name' => $custom_field, 'type' => $type, 'raw' => $raw));
	$out = tag($out, $wraptag, ' id="yab-shop-price-'.$id.'" class="'.$class.'"');

	return $out;
}

function yab_shop_show_config($atts)
{
	extract(lAtts(array(
		'name' => ''
	),$atts));

	$config_value = yab_shop_config($name);

	if ($config_value)
		return $config_value;

	return 'No config value with this name available.';
}

function yab_shop_custom_field($atts)
{
	global $thisarticle, $prefs;
	assert_article();

	extract(lAtts(array(
		'name'     => @$prefs['custom_1_set'],
		'type'     => 'price',
		'raw'      => '0',
		'default'  => '',
		'hide'     => false,
		'required' => false,
	),$atts));

	$currency = yab_shop_config('currency');
	$name = strtolower($name);
	$custom_field_price_name = strtolower(yab_shop_config('custom_field_price_name'));

	if (!empty($thisarticle[$name]))
	{
		if ($name == $custom_field_price_name)
		{
			$out = $thisarticle[$name];

			// Price may contain RRP too, so extract it if present
			$product_prices = do_list($out, '|');
			$product_price = $product_prices[0];
			$product_rrp = (isset($product_prices[1])) ? $product_prices[1] : '';
			$product_price = yab_shop_replace_commas($product_price);
			$product_rrp = yab_shop_replace_commas($product_rrp);
			$product_price_saving = ($product_rrp) ? $product_rrp - $product_price : '';

			$out = ($type == 'price') ? $product_price : ( ($type == 'rrp') ? $product_rrp : $product_price_saving );
			$out = ($raw) ? $out : yab_shop_currency_out($currency, 'cur').yab_shop_currency_out($currency, 'toform', $out);
		}
		else
		{
			$out = $thisarticle[$name];
			$out = explode(';', $out);
			$out = str_replace('--', ': '.yab_shop_currency_out($currency, 'cur'), $out);
			$out = yab_shop_type_select_custom($out, $name, $hide, $required);
		}
	}
	else
		$out = $default;

	return $out;
}

function yab_shop_property_prices($id = '')
{
	$out = '';

	if (!empty($id))
	{
		$base_price = yab_shop_custom_field(array('name' => yab_shop_config('custom_field_price_name')));
		$cust_field = yab_shop_check_property_prices($id);

		if ($cust_field != false)
		{
			$cust_field = yab_shop_ascii($cust_field);
			$out .= '<script type="text/javascript">'.n;
			$out .= '/* <![CDATA[ */'.n;
			$out .= '	$(document).ready(function() {'.n;
			$out .= '		$("#select-'.$cust_field.'-'.$id.'").change(function () {'.n;
			$out .= '			$("#select-'.$cust_field.'-'.$id.' option:selected").each(function() {'.n;
			$out .= '				var str = $(this).text().match(/: /) ? $(this).text().replace(/.*: /, "") : "'.$base_price.'";'.n;
			$out .= '			$("#yab-shop-price-'.$id.'").text(str);'.n;
			$out .= '			})'.n;
			$out .= '		})'.n;
			$out .= '	});'.n;
			$out .= '/* ]]> */'.n;
			$out .= '</script>';
		}
	}
	return $out;
}

function yab_shop_field_names($in)
{
	global $prefs;

	foreach ($prefs as $val => $key)
	{
		if ($key == $in)
		{
			return $val;
			break;
		}
	}
}

function yab_shop_get_custom_field_names($val)
{
	return str_replace('_set', '', yab_shop_field_names($val));
}

function yab_shop_check_property_prices($id = '')
{
	global $thisarticle, $is_article_list;

	if (yab_shop_config('use_property_prices') == '1')
	{
		$regex = '/--[0-9]*(,|.|)[0-9]{2}/';
		$thisproperty_1 = '';
		$thisproperty_2 = '';
		$thisproperty_3 = '';

		if ($is_article_list == true)
		{
			$prop_1 = yab_shop_config('custom_field_property_1_name');
			$prop_2 = yab_shop_config('custom_field_property_2_name');
			$prop_3 = yab_shop_config('custom_field_property_3_name');
			$list_prop_1 = yab_shop_get_custom_field_names($prop_1);
			$list_prop_2 = yab_shop_get_custom_field_names($prop_2);
			$list_prop_3 = yab_shop_get_custom_field_names($prop_3);
			$article_properties = array();
			$article_properties = safe_row("$list_prop_1 as property_1, $list_prop_2 as property_2, $list_prop_3 as property_3", 'textpattern', "ID = $id");
			$article_property_1 = isset($article_properties['property_1']) ? $article_properties['property_1'] : '';
			$article_property_2 = isset($article_properties['property_2']) ? $article_properties['property_2'] : '';
			$article_property_3 = isset($article_properties['property_3']) ? $article_properties['property_3'] : '';
		}
		else
		{
			$prop_1 = strtolower(yab_shop_config('custom_field_property_1_name'));
			$prop_2 = strtolower(yab_shop_config('custom_field_property_2_name'));
			$prop_3 = strtolower(yab_shop_config('custom_field_property_3_name'));
			$article_property_1 = isset($thisarticle[$prop_1]) ? $thisarticle[$prop_1] : '';
			$article_property_2 = isset($thisarticle[$prop_2]) ? $thisarticle[$prop_2] : '';
			$article_property_3 = isset($thisarticle[$prop_3]) ? $thisarticle[$prop_3] : '';
		}

		if (isset($article_property_1))
			$thisproperty_1 = $article_property_1;

		if (isset($article_property_2))
			$thisproperty_2 = $article_property_2;

		if (isset($article_property_3))
			$thisproperty_3 = $article_property_3;

		if (preg_match($regex, $thisproperty_1))
		{
			return $prop_1;
		}
		elseif (preg_match($regex, $thisproperty_2))
		{
			return $prop_2;
		}
		elseif (preg_match($regex, $thisproperty_3))
		{
			return $prop_3;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}

function yab_shop_check_required_fields($ps_order)
{
	$notice = '';

	if (yab_shop_config('using_tou_checkbox') == '1')
	{
		if (!isset($_POST['tou']))
			$ps_order['tou|r'] = '';
	}

	foreach ($ps_order as $key => $ps)
	{
		if (preg_match('/\|r$/', $key) and $ps == '')
			$notice .= tag(yab_shop_lang('checkout_'.preg_replace('/\|r$/', '', $key).''), 'li');
	}

	if (isset($ps_order['email']) and $ps_order['email'] != '') {
		if (!is_valid_email($ps_order['email'])) {
			$notice .= tag(yab_shop_lang('checkout_mail_email_error'), 'li');
		}
	}

	return $notice;
}

function yab_shop_required_fields() {
	// Permitted fields that can be optional/required
	$opt_fields = do_list('firstname, surname, street, city, state, postal, country, phone, email');

	$req_fields = do_list(yab_shop_config('checkout_required_fields'));
	$req_cls = ' yab-shop-required';
	$req_mod = '|r';

	$req_matrix = array();

	foreach ($opt_fields as $fld) {
		$req_matrix[$fld] = array(
			'mod' => ((in_array($fld, $req_fields)) ? $req_mod : ''),
			'cls' => ((in_array($fld, $req_fields)) ? $req_cls : ''),
		);
	}

	return $req_matrix;
}

function yab_shop_check_item($cart, $productid, $product_property_1, $product_property_2, $product_property_3)
{
	$i = 0;
	foreach ($cart->get_contents() as $item)
	{
		if (preg_match('/'.$productid.'-/', $item['itemid']))
		{
			$i++;
			if ($item['property_1'] == $product_property_1 and $item['property_2'] == $product_property_2 and $item['property_3'] == $product_property_3)
			{
				$i = str_replace($productid.'-', '', $item['itemid']);
				break;
			}
		}
	}
	return $i;
}

function yab_shop_return_input($input)
{
	$output = '';
	$is_order = ( (ps('order') != '') || (ps('order_x') != '') );
	if ($is_order)
		$output = yab_shop_clean_input($_POST[$input]);
	elseif (cs('yab_shop_remember') == 1)
		$output = cs('yab_shop_'.$input);

	return $output;
}

function yab_shop_build_paypal_form($cart)
{
	$subdomain = '';
	if (yab_shop_config('paypal_live_or_sandbox') == 'sandbox')
		$subdomain = '.sandbox';

	$tax = '0.00';
	if (yab_shop_config('tax_inclusive') == '0')
		$tax = number_format(yab_shop_calculate_sum('tax'),2);

	$req_matrix = yab_shop_required_fields();
	$email = '';
	if (ps('email'.$req_matrix['email']['mod']))
		$email = hInput('email', yab_shop_return_input('email'.$req_matrix['email']['mod'])).n;
	$state = '';
	if (ps('state'.$req_matrix['state']['mod']))
		$state = hInput('state', yab_shop_return_input('state'.$req_matrix['state']['mod'])).n;
	$custom = '';
	if (ps('yab_shop_custom'))
		$custom = hInput('custom', yab_shop_return_input('yab_shop_custom')).n;
	$phone = '';
	if (ps('phone'.$req_matrix['phone']['mod']))
		$phone = hInput('contact_phone', yab_shop_return_input('phone'.$req_matrix['phone']['mod'])).n;
	$action = 'https://www'.$subdomain.'.paypal.com/cgi-bin/webscr';
	$message = yab_shop_lang('checkout_paypal_no_forward');
	$message2 = yab_shop_lang('checkout_paypal_forward');
	$business_email = yab_shop_config('paypal_business_mail');
	$country = yab_shop_return_input('country'.$req_matrix['country']['mod']);
	if (!$country) {
		$country = yab_shop_config('paypal_prefilled_country');
	}
	$lc = yab_shop_mlp_select_lang( yab_shop_config('paypal_interface_language') );
	$thanks = yab_shop_mlp_inject( yab_shop_config('checkout_thanks_site') );
	$currency = yab_shop_config('currency');
	$ship_data = yab_shop_available_shipping_options();
	$shipping = $ship_data['shipping_costs']; // Assumption is this won't return an error since we've trapped shipping errors earlier
	$ship_meth = $cart->get_ship_method();

	$i = 0;
	$products = '';
	foreach ($cart->get_contents() as $item)
	{
		$i++;
		$products .= hInput('item_number_'.$i, $item['itemid']).n.
						hInput('item_name_'.$i, $item['name']).n.
						hInput('amount_'.$i, $item['price']).n.
						hInput('quantity_'.$i, $item['qty']).n;

		$properties = '';
		if ($item['property_1'] != '')
		{
			$properties .=	hInput('on0_'.$i, yab_shop_lang('custom_field_property_1')).n.
											hInput('os0_'.$i, $item['property_1']).n;
		}
		if ($item['property_2'] != '')
		{
			if ($item['property_3'] != '')
			{
				$properties .=	hInput('on1_'.$i, yab_shop_lang('custom_field_property_2').'/'.yab_shop_lang('custom_field_property_3')).n.
												hInput('os1_'.$i, $item['property_2'].'/'.$item['property_3']).n;
			}
			else
			{
				$properties .=	hInput('on1_'.$i, yab_shop_lang('custom_field_property_2')).n.
												hInput('os1_'.$i, $item['property_2']).n;
			}
		}
		else
		{
			if ($item['property_3'] != '')
			{
				$properties .=	hInput('on1_'.$i, yab_shop_lang('custom_field_property_3')).n.
												hInput('os1_'.$i, $item['property_3']).n;
			}
		}
		$products .= $properties;
	}

	$form = '';
	$form = '<script type="text/javascript">function doPaypal(){var New="'.$message2.'";document.getElementById("yabshoppaypalforward").innerHTML=New;document.getElementById("yab-paypal-form").submit();document.getElementById("yabpaypalsubmit").style.display="none"}window.onload=doPaypal;</script>';
	$form .= graf($message, ' class="yab-shop-message" id="yabshoppaypalforward"');
	$form .= tag(
		hInput('cmd', '_ext-enter').n.
		hInput('redirect_cmd', '_cart').n.
		hInput('upload', '1').n.
		hInput('business', $business_email).n.
		hInput('return', $thanks).n.
		hInput('country', $country).n.
		hInput('lc', $lc).n.
		hInput('currency_code', $currency).n.
		hInput('tax_cart', $tax).n.
		hInput('shipping_1', $shipping).n.
		hInput('shipping_method', $ship_meth).n.
		hInput('first_name', yab_shop_return_input('firstname'.$req_matrix['firstname']['mod'])).n.
		hInput('last_name', yab_shop_return_input('surname'.$req_matrix['surname']['mod'])).n.
		$email.
		$phone.
		hInput('address1', yab_shop_return_input('street'.$req_matrix['street']['mod'])).n.
		hInput('city', yab_shop_return_input('city'.$req_matrix['city']['mod'])).n.
		hInput('zip', yab_shop_return_input('postal'.$req_matrix['postal']['mod'])).n.
		$state.
		$custom.
		$products.
		fInput('submit','paypal', yab_shop_lang('checkout_paypal_button'), 'submit', '', '', '', '', 'yabpaypalsubmit').n,'form', ' method="post" action="'.$action.'" id="yab-paypal-form"'
	);

	return $form;
}

function yab_shop_build_paypal_encrypted_form($cart)
{
	global $tempdir;

	$subdomain = '';
	if (yab_shop_config('paypal_live_or_sandbox') == 'sandbox')
		$subdomain = '.sandbox';

	$req_matrix = yab_shop_required_fields();
	$email = '';
	if (ps('email'.$req_matrix['email']['mod']))
		$email = yab_shop_return_input('email'.$req_matrix['email']['mod']);
	$state = '';
	if (ps('state'.$req_matrix['state']['mod']))
		$state = yab_shop_return_input('state'.$req_matrix['state']['mod']);
	$custom = '';
	if (ps('yab_shop_custom'))
		$custom = hInput('custom', yab_shop_return_input('yab_shop_custom')).n;
	$phone = '';
	if (ps('phone'.$req_matrix['phone']['mod']))
		$phone = hInput('contact_phone', yab_shop_return_input('phone'.$req_matrix['phone']['mod'])).n;

	$tax = '0.00';
	if (yab_shop_config('tax_inclusive') == '0')
		$tax = number_format(yab_shop_calculate_sum('tax'),2);

	$action = 'https://www'.$subdomain.'.paypal.com/cgi-bin/webscr';
	$message = yab_shop_lang('checkout_paypal_no_forward');
	$message2 = yab_shop_lang('checkout_paypal_forward');
	$business_email = yab_shop_config('paypal_business_mail');
	$country = yab_shop_return_input('country'.$req_matrix['country']['mod']);
	if (!$country) {
		$country = yab_shop_config('paypal_prefilled_country');
	}
	$lc = yab_shop_mlp_select_lang( yab_shop_config('paypal_interface_language'));
	$thanks = yab_shop_mlp_inject( yab_shop_config('checkout_thanks_site') );
	$currency = yab_shop_config('currency');
	$ship_data = yab_shop_available_shipping_options();
	$shipping = $ship_data['shipping_costs']; // Assumption is this won't return an error since we've trapped shipping errors earlier
	$ship_meth = $cart->get_ship_method();

	$myPublicCertificate = yab_shop_config('paypal_certificates_path').'/'.yab_shop_config('paypal_my_public_certificate_name');
	$myPrivateKey = yab_shop_config('paypal_certificates_path').'/'.yab_shop_config('paypal_my_private_key_name');
	$CertificateID = yab_shop_config('paypal_certificate_id');
	$PayPalPublicCertificate = yab_shop_config('paypal_certificates_path').'/'.yab_shop_config('paypal_public_certificate_name');

	$paypal = new PayPalEWP();
	$paypal->setTempDir($tempdir);
	$paypal->setCertificate($myPublicCertificate, $myPrivateKey);
	$paypal->setCertificateID($CertificateID);
	$paypal->setPayPalCertificate($PayPalPublicCertificate);

	$parameters = array(
		'cmd'             => '_ext-enter',
		'redirect_cmd'    => '_cart',
		'upload'          => '1',
		'business'        => $business_email,
		'cert_id'         => $CertificateID,
		'return'          => $thanks,
		'country'         => $country,
		'lc'              => $lc,
		'currency_code'   => $currency,
		'tax_cart'        => $tax,
		'shipping_1'      => $shipping,
		'shipping_method' => $ship_meth,
		'first_name'      => yab_shop_return_input('firstname'.$req_matrix['firstname']['mod']),
		'last_name'       => yab_shop_return_input('surname'.$req_matrix['surname']['mod']),
		'email'           => $email,
		'contact_phone'   => $phone,
		'address1'        => yab_shop_return_input('street'.$req_matrix['street']['mod']),
		'city'            => yab_shop_return_input('city'.$req_matrix['city']['mod']),
		'zip'             => yab_shop_return_input('postal'.$req_matrix['postal']['mod']),
		'state'           => $state,
		'custom'          => $custom,
	);

	$i = 0;
	foreach ($cart->get_contents() as $item)
	{
		$i++;
		$parameters['item_number_'.$i] = $item['itemid'];
		$parameters['item_name_'.$i]   = $item['name'];
		$parameters['amount_'.$i]		 = $item['price'];
		$parameters['quantity_'.$i]	 = $item['qty'];

		if ($item['property_1'] != '')
		{
			$parameters['on0_'.$i] = yab_shop_lang('custom_field_property_1');
			$parameters['os0_'.$i] = $item['property_1'];
		}
		if ($item['property_2'] != '')
		{
			if ($item['property_3'] != '')
			{
				$parameters['on1_'.$i] = yab_shop_lang('custom_field_property_2').'/'.yab_shop_lang('custom_field_property_3');
				$parameters['os1_'.$i] = $item['property_2'].'/'.$item['property_3'];
			}
			else
			{
				$parameters['on1_'.$i] = yab_shop_lang('custom_field_property_2');
				$parameters['os1_'.$i] = $item['property_2'];
			}
		}
		else
		{
			if ($item['property_3'] != '')
			{
				$parameters['on1_'.$i] = yab_shop_lang('custom_field_property_3');
				$parameters['os1_'.$i] = $item['property_3'];
			}
		}
	}

	if (ps('email'.$req_matrix['email']['mod']))
		$parameters['email'] = yab_shop_return_input('email'.$req_matrix['email']['mod']);

	$encryptedButton = $paypal->encryptButton($parameters);

	$form = '<script type="text/javascript">function doPaypal(){var New="'.$message2.'";document.getElementById("yabshoppaypalforward").innerHTML=New;document.getElementById("yab-paypal-form").submit();document.getElementById("yabpaypalsubmit").style.display="none"}window.onload=doPaypal;</script>';
	$form .= graf($message, ' class="yab-shop-message" id="yabshoppaypalforward"');
	$form .= tag(
		hInput('cmd', '_s-xclick').n.
		hInput('encrypted', $encryptedButton).n.
		fInput('submit','paypal', yab_shop_lang('checkout_paypal_button'), 'submit', '', '', '', '', 'yabpaypalsubmit').n,'form', ' method="post" action="'.$action.'" id="yab-paypal-form"'
	);

	switch ($paypal->error)
	{
		case 0:
			$out = $form;
			break;
		case 1:
			$out = 'Paypal certificate id is not set.';
			break;
		case 2:
			$out = 'Your public and/or private certificate is not readable. Please check permissions, names and paths.';
			break;
		case 3:
			$out = 'Paypal public certificate is not readable. Please check permissions, names and paths.';
			break;
		case 4:
			$out = 'It seems that openssl is not supported.';
			break;
		default:
			$out =	'Unknown error occured.';
	}
	return $out;
}

function yab_shop_build_google_form($cart)
{
	$merchant_id = yab_shop_config('google_merchant_id');
	$merchant_key = yab_shop_config('google_merchant_key');
	$message = yab_shop_lang('checkout_google_no_forward');
	$message2 = yab_shop_lang('checkout_google_forward');
	$currency = yab_shop_config('currency');
	$ship_data = yab_shop_available_shipping_options();
	$shipping = $ship_data['shipping_costs']; // Assumption is this won't return an error since we've trapped shipping errors earlier

	$domain = 'https://checkout.google.com/api/checkout/v2/checkout/Merchant/'.$merchant_id;
	if (yab_shop_config('google_live_or_sandbox') == 'sandbox')
		$domain = 'https://sandbox.google.com/checkout/api/checkout/v2/checkout/Merchant/'.$merchant_id;

	$gitems = '';
	$gitem_property_1 = '';
	$gitem_property_2 = '';
	$gitem_property_3 = '';
	$gitem_properties = '';

	foreach ($cart->get_contents() as $item)
	{

		$gi = 0;
		if ($item['property_1'] != '')
		{
			$gitem_property_1 = yab_shop_lang('custom_field_property_1').': '.$item['property_1'];
			$gi++;
		}
		if ($item['property_2'] != '')
		{
			$gitem_property_2 = ': '.yab_shop_lang('custom_field_property_2').': '.$item['property_2'];
			$gi++;
		}
		if ($item['property_3'] != '')
		{
			$gitem_property_3 = ': '.yab_shop_lang('custom_field_property_3').': '.$item['property_3'];
			$gi++;
		}

		if ($gi != 0)
			$gitem_properties = tag($gitem_property_1.$gitem_property_2.$gitem_property_3,'item-description');
		else
			$gitem_properties = tag(' ','item-description');

		$gitems .= tag(
			tag($item['name'], 'item-name').
			tag($item['price'], 'unit-price' , ' currency="'.$currency.'"').
			tag($item['qty'], 'quantity').
			$gitem_properties
		, 'item');
	}

	$gcart_xml = '<?xml version="1.0" encoding="UTF-8"?>'.n;
	$gcart_xml .= tag(
		tag(
			tag($gitems, 'items')
		, 'shopping-cart').
		tag(
			tag(
				tag(
					tag(
						tag($shipping, 'price', ' currency="'.$currency.'"')
					, 'flat-rate-shipping', ' name="'.yab_shop_config('shipping_via').'"')
				, 'shipping-methods')
			, 'merchant-checkout-flow-support')
		, 'checkout-flow-support')
	, 'checkout-shopping-cart', ' xmlns="http://checkout.google.com/schema/2"');

	$gsig = CalcHmacSha1($gcart_xml, $merchant_key);
	$base64_gcart = base64_encode($gcart_xml);
	$base64_gsig = base64_encode($gsig);

	$form = graf($message, ' class="yab-shop-message" id="yabshopgoogleforward"');
	$form .= tag(
		hInput('cart', $base64_gcart).n.
		hInput('signature', $base64_gsig).n.
		'<input type="image" name="Google Checkout" alt="Fast checkout through Google" src="http://checkout.google.com/buttons/checkout.gif?merchant_id='.$merchant_id.'&w=160&h=43&style=white&variant=text&loc=en_US" id="yabgooglesubmit" />'
	,'form', ' method="post" action="'.$domain.'" id="yab-google-form"');

	return $form;
}

function yab_shop_build_checkout_form()
{
	global $yab_shop_prefs;
	$city = '';
	$state = '';
	$country = '';
	$tou = '';

	$req_matrix = yab_shop_required_fields();

	if (yab_shop_config('using_checkout_state') == '1')
	{
		$state = graf(
			tag(yab_shop_lang('checkout_state'), 'label', ' for="state"').
			fInput('text', 'state'.$req_matrix['state']['mod'], yab_shop_return_input('state'.$req_matrix['state']['mod']), '', '', '', '', '', 'state'), ' class="yab-shop-state'.$req_matrix['state']['cls'].'"'
		);
	}

	if (yab_shop_config('using_checkout_country') == '1')
	{
		$key = yab_shop_return_input('country'.$req_matrix['country']['mod']);
		$country = graf(
			tag(yab_shop_lang('checkout_country'), 'label', ' for="country"').
			yab_shop_get_countries('country'.$req_matrix['country']['mod'], $key, true, '', 'country'), ' class="yab-shop-country'.$req_matrix['country']['cls'].'"'
		);
	}

	if (yab_shop_config('using_tou_checkbox') == '1')
	{
		$tou = graf(
			checkbox('tou', '1', '0', '', 'yab-tou').
			tag(yab_shop_lang('checkout_terms_of_use', array('{tou}' => yab_shop_config('tou_link'))), 'label', ' for="yab-tou"'),
			' class="yab-shop-required tou"'
			);
	}

	$form = tag(
		fieldset(
			pluggable_ui('yab_shop', 'checkout_form_preamble', '', $yab_shop_prefs).
			graf(
				tag(yab_shop_lang('checkout_firstname'), 'label', ' for="firstname"').
				fInput('text', 'firstname'.$req_matrix['firstname']['mod'], yab_shop_return_input('firstname'.$req_matrix['firstname']['mod']), '', '', '', '', '', 'firstname'), ' class="yab-shop-firstname'.$req_matrix['firstname']['cls'].'"'
			).
			graf(
				tag(yab_shop_lang('checkout_surname'), 'label', ' for="surname"').
				fInput('text', 'surname'.$req_matrix['surname']['mod'], yab_shop_return_input('surname'.$req_matrix['surname']['mod']), '', '', '', '', '', 'surname'), ' class="yab-shop-surname'.$req_matrix['surname']['cls'].'"'
			).
			graf(
				tag(yab_shop_lang('checkout_street'), 'label', ' for="street"').
				fInput('text', 'street'.$req_matrix['street']['mod'], yab_shop_return_input('street'.$req_matrix['street']['mod']), '', '', '', '', '', 'street'), ' class="yab-shop-street'.$req_matrix['street']['cls'].'"'
			).
			graf(
				tag(yab_shop_lang('checkout_city'), 'label', ' for="city" class="city"').
				fInput('text', 'city'.$req_matrix['city']['mod'], yab_shop_return_input('city'.$req_matrix['city']['mod']), '', '', '', '', '', 'city'), ' class="yab-shop-city'.$req_matrix['city']['cls'].'"'
			).
			graf(
				tag(yab_shop_lang('checkout_postal'), 'label', ' for="postal"').
				fInput('text', 'postal'.$req_matrix['postal']['mod'], yab_shop_return_input('postal'.$req_matrix['postal']['mod']), '', '', '', '', '', 'postal'), ' class="yab-shop-zip'.$req_matrix['postal']['cls'].'"'
			).
			$state.$country.
			graf(
				tag(yab_shop_lang('checkout_phone'), 'label', ' for="phone"').
				fInput('tel', 'phone'.$req_matrix['phone']['mod'], yab_shop_return_input('phone'.$req_matrix['phone']['mod']), '', '', '', '', '', 'phone'), ' class="yab-shop-phone'.$req_matrix['phone']['cls'].'"'
			).
			graf(
				tag(yab_shop_lang('checkout_email'), 'label', ' for="email"').
				fInput('email', 'email'.$req_matrix['email']['mod'], yab_shop_return_input('email'.$req_matrix['email']['mod']), '', '', '', '', '', 'email'), ' class="yab-shop-email'.$req_matrix['email']['cls'].'"'
			).
			yab_shop_checkout_payment_methods().
			graf(
				tag(yab_shop_lang('checkout_message'), 'label', ' for="message"').
				'<textarea cols="40" rows="5" name="message" id="message">'.yab_shop_return_input('message').'</textarea>', ' class="yab-shop-text"'
			).
			$tou.
			graf(yab_remember_checkbox(), ' class="tou remember"').
			pluggable_ui('yab_shop', 'checkout_form_postamble', '', $yab_shop_prefs).
			pluggable_ui('yab_shop', 'checkout_form_order_button', graf(
			fInput('submit', 'order', yab_shop_lang('checkout_order'), 'submit'),
			' class="submit"'
			), $yab_shop_prefs)
		),'form', ' method="post" action="'.yab_shop_mlp_inject( pagelinkurl(array('s' => yab_shop_config('checkout_section_name')))).'#yab-shop-checkout-anchor" id="yab-checkout-form"'
	);

	return $form;
}

function yab_shop_build_checkout_table($cart, $summary, $no_change = false)
{
	$tax_inclusive = yab_shop_config('tax_inclusive');

	$checkout_display = tr(
		tag(yab_shop_lang('table_caption_content'), 'th').
		tag(yab_shop_lang('table_caption_change'), 'th', ' class="yab-checkout-change"').
		tag(yab_shop_lang('table_caption_price'), 'th', ' class="yab-checkout-price"')
	).n;

	list($tax_bands, $default_tax_rate) = yab_shop_tax_rates();

	$class = '';
	if ($no_change != false)
		$class = ' class="yab-shop-nochange"';

	foreach ($cart->get_contents() as $item)
	{
		$item_price = yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price']);
		$item_price_sum = yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price'] * $item['qty']);

		$out_qty = yab_shop_checkout_qty_edit($item['itemid'], $item['qty']);
		if ($no_change != false)
			$out_qty = $item['qty'];

		$checkout_display .= tr(
			td(
				yab_shop_checkout_image($item['txpid']).href($item['name'], permlinkurl_id($item['txpid'])).
				tag(
				yab_shop_build_checkout_customs($item['property_1'], yab_shop_lang('custom_field_property_1'), yab_shop_config('custom_field_property_1_name')).
				yab_shop_build_checkout_customs($item['property_2'], yab_shop_lang('custom_field_property_2'), yab_shop_config('custom_field_property_2_name')).
				yab_shop_build_checkout_customs($item['property_3'], yab_shop_lang('custom_field_property_3'), yab_shop_config('custom_field_property_3_name')).
				yab_shop_build_checkout_customs($item_price, yab_shop_lang('price'), yab_shop_config('custom_field_price_name'))
				, 'ul')
			).
			td($out_qty, '', 'yab-checkout-change').
			td($item_price_sum, '', 'yab-checkout-price')
		).n;
	}

	$ship_data = yab_shop_available_shipping_options();
	$shipping_costs = $ship_data['shipping_costs'];
	$tax_shipping_costs = ($shipping_costs === 'NA') ? 0.00 : $shipping_costs;

	if ($tax_inclusive == '0')
	{
		$checkout_display .= tr(
			tda(yab_shop_lang('sub_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), ' class="yab-checkout-sum"'),
			' class="yab-checkout-subtotal"'
		).n;
		$checkout_display .= tr(
			tda( ( ($default_tax_rate == '0') ? '&nbsp;' : yab_shop_lang('checkout_tax_exclusive', array('{tax}' => $default_tax_rate)) ), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax')), ' class="yab-checkout-sum"'),
			' class="yab-checkout-tax"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('shipping_costs') . (($ship_data['shipping_options']) ? sp. $ship_data['shipping_options'] : ''), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-shipping"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('grand_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('brutto') + $tax_shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-total"'
		);
	}
	else
	{
		$checkout_display .= tr(
			tda(yab_shop_lang('sub_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), ' class="yab-checkout-sum"'),
			' class="yab-checkout-subtotal"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('shipping_costs') . (($ship_data['shipping_options']) ? sp . $ship_data['shipping_options'] : ''), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-shipping"'
		).n;
		$checkout_display .= tr(
			tda(yab_shop_lang('grand_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total + $tax_shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-total"'
		).n;
		$checkout_display .= tr(
			tda( ( ($default_tax_rate == '0') ? '&nbsp;' : yab_shop_lang('checkout_tax_inclusive', array('{tax}' => $default_tax_rate)) ), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax')), ' class="yab-checkout-sum"'),
			' class="yab-checkout-tax"'
		);
	}
	$checkout_display = tag($checkout_display, 'table', ' id="yab-checkout-table" summary="'.$summary.'"'.$class);
	return $checkout_display;
}

function yab_shop_build_cart($cart)
{
	$cart_display = '';

	if ($cart->itemcount > 0)
	{
		foreach ($cart->get_contents() as $item)
		{
			$cart_display .= tag(
				href($item['name'], yab_shop_mlp_inject(permlinkurl_id($item['txpid']))).
				tag(
					tag(yab_shop_lang('price').':&nbsp;'.yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price']), 'li', ' class="yab-price"').
						tag(yab_shop_lang('quantity').':&nbsp;'.$item['qty'], 'li', ' class="yab-qty"'),
				'ul'),
			'li', ' class="yab-item"');
		}
		$cart_display = tag($cart_display, 'ul', ' class="yab-cart"');
		$cart_display .= tag(yab_shop_lang('sub_total').':&nbsp;'.yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), 'span', ' class="yab-subtotal"');
		$cart_display .= tag(yab_shop_lang('to_checkout'), 'a', ' href="'.yab_shop_mlp_inject(pagelinkurl(array('s' => yab_shop_config('checkout_section_name')))).'" title="'.yab_shop_lang('to_checkout').'" class="yab-to-checkout"');
	}
	else
	{
		$cart_display = tag(yab_shop_lang('empty_cart'), 'span', ' class="yab-empty"');
	}
	return $cart_display;
}

function yab_shop_build_mail_body($cart, $ps_order, $affirmation = '0')
{
	global $yab_shop_mail_info;

	$line_1 = '----------------------------------------------------------------------';
	$line_2 = '======================================================================';
	$line_3 = '______________________________________________________________________';

	$body = $body_form = $item_form = '';
	$items = array();

	$forms = do_list(yab_shop_config('email_body_form'));
	if (!empty($forms[0])) {
		$body_form = fetch_form($forms[0]);
	}
	if (isset($forms[1]) && !empty($forms[1])) {
		$item_form = fetch_form($forms[1]);
	}

	$eol = "\r\n";
	if (!is_windows())
		$eol = "\n";

	$yab_shop_mail_info['label']['eol'] = $eol;

	$req_matrix = yab_shop_required_fields();
	$promo_admin = '';
	$promo_client = '';
	if ($cart->get_promocode() != NULL)
	{
		$promo_admin = yab_shop_lang('admin_mail_promocode');
		$promo_client = yab_shop_lang('affirmation_mail_promocode');
	}

	$yab_shop_mail_info['body']['pre_products'] = ($affirmation == '1') ? yab_shop_lang('affirmation_mail_pre_products') : yab_shop_lang('admin_mail_pre_products');

	if (!$body_form) {
		$body .= $yab_shop_mail_info['body']['pre_products'].$eol;
	}

	list($tax_bands, $default_tax_rate) = yab_shop_tax_rates();

	$state = $country = '';
	if (yab_shop_config('using_checkout_state') == '1')
		$state = yab_shop_lang('checkout_state').': '.$ps_order['state'.$req_matrix['state']['mod']];

	if (yab_shop_config('using_checkout_country') == '1')
		$country = yab_shop_lang('checkout_country').': '.$ps_order['country'.$req_matrix['country']['mod']];

	$yab_shop_mail_info['body']['state'] = $state;
	$yab_shop_mail_info['body']['country'] = $country;
	$yab_shop_mail_info['body']['firstname'] = $ps_order['firstname'.$req_matrix['firstname']['mod']];
	$yab_shop_mail_info['body']['surname'] = $ps_order['surname'.$req_matrix['surname']['mod']];
	$yab_shop_mail_info['body']['street'] = $ps_order['street'.$req_matrix['street']['mod']];
	$yab_shop_mail_info['body']['city'] = $ps_order['city'.$req_matrix['city']['mod']];
	$yab_shop_mail_info['body']['postal'] = $ps_order['postal'.$req_matrix['postal']['mod']];
	$yab_shop_mail_info['body']['phone'] = $ps_order['phone'.$req_matrix['phone']['mod']];
	$yab_shop_mail_info['body']['email'] = $ps_order['email'.$req_matrix['email']['mod']];
	$yab_shop_mail_info['body']['payment'] = $ps_order['payment'];
	$yab_shop_mail_info['body']['message'] = $ps_order['message'];

	$yab_shop_mail_info['label']['state'] = yab_shop_lang('checkout_state');
	$yab_shop_mail_info['label']['country'] = yab_shop_lang('checkout_country');
	$yab_shop_mail_info['label']['firstname'] = yab_shop_lang('checkout_firstname');
	$yab_shop_mail_info['label']['surname'] = yab_shop_lang('checkout_surname');
	$yab_shop_mail_info['label']['street'] = yab_shop_lang('checkout_street');
	$yab_shop_mail_info['label']['city'] = yab_shop_lang('checkout_city');
	$yab_shop_mail_info['label']['postal'] = yab_shop_lang('checkout_postal');
	$yab_shop_mail_info['label']['phone'] = yab_shop_lang('checkout_phone');
	$yab_shop_mail_info['label']['email'] = yab_shop_lang('checkout_email');
	$yab_shop_mail_info['label']['payment'] = yab_shop_lang('checkout_payment');
	$yab_shop_mail_info['label']['message'] = yab_shop_lang('checkout_message');
	$yab_shop_mail_info['label']['property_1'] = yab_shop_lang('custom_field_property_1');
	$yab_shop_mail_info['label']['property_2'] = yab_shop_lang('custom_field_property_2');
	$yab_shop_mail_info['label']['property_3'] = yab_shop_lang('custom_field_property_3');

	if (!$body_form) {
		$body .=
			$eol.yab_shop_lang('checkout_firstname').': '.$yab_shop_mail_info['body']['firstname'].
			$eol.yab_shop_lang('checkout_surname').': '.$yab_shop_mail_info['body']['surname'].
			$eol.yab_shop_lang('checkout_street').': '.$yab_shop_mail_info['body']['street'].
			$eol.yab_shop_lang('checkout_city').': '.$yab_shop_mail_info['body']['city'].
			$eol.yab_shop_lang('checkout_postal').': '.$yab_shop_mail_info['body']['postal'].
			(($state) ? $eol.$state : '') . (($country) ? $eol.$country : '').
			$eol.yab_shop_lang('checkout_phone').': '.$yab_shop_mail_info['body']['phone'].
			$eol.yab_shop_lang('checkout_email').': '.$yab_shop_mail_info['body']['email'].
			$eol.yab_shop_lang('checkout_payment').': '.$yab_shop_mail_info['body']['payment'].
			$eol.yab_shop_lang('checkout_message').': '.$yab_shop_mail_info['body']['message'].$eol;
		$body .= $eol.$line_1.$eol;
	}

	$ship_data = yab_shop_available_shipping_options();
	$shipping = $ship_data['shipping_costs'];
	$tax_shipping = ($shipping === 'NA') ? 0.00 : $shipping;

	foreach ($cart->get_contents() as $item)
	{
		$yab_shop_mail_info['item']['price'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price']);
		$yab_shop_mail_info['item']['qty'] = $item['qty'];
		$yab_shop_mail_info['item']['price_sum'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price'] * $item['qty']);
		$yab_shop_mail_info['item']['name'] = $item['name'];
		$yab_shop_mail_info['item']['property_1'] = $item['property_1'];
		$yab_shop_mail_info['item']['property_2'] = $item['property_2'];
		$yab_shop_mail_info['item']['property_3'] = $item['property_3'];

		if ($item_form) {
			$items[] = parse($item_form);
		} else {
			$items[] = $eol.$item['name'].
				$eol.$item['qty'].' x '.$yab_shop_mail_info['item']['price'].' = '.$yab_shop_mail_info['item']['price_sum'].$eol.
				yab_shop_build_mail_customs($item['property_1'], yab_shop_lang('custom_field_property_1'), $eol).
				yab_shop_build_mail_customs($item['property_2'], yab_shop_lang('custom_field_property_2'), $eol).
				yab_shop_build_mail_customs($item['property_3'], yab_shop_lang('custom_field_property_3'), $eol);
		}
	}

	$yab_shop_mail_info['body']['items'] = join('', $items);
	if (!$body_form) {
		$body .= $yab_shop_mail_info['body']['items'];
	}

	$yab_shop_mail_info['body']['sub_total'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total);
	$yab_shop_mail_info['body']['shipping'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $shipping);

	$yab_shop_mail_info['label']['grand_total'] = yab_shop_lang('grand_total');
	$yab_shop_mail_info['label']['shipping_costs'] = yab_shop_lang('shipping_costs');
	$yab_shop_mail_info['label']['sub_total'] = yab_shop_lang('sub_total');

	if (yab_shop_config('tax_inclusive') == '0')
	{
		$yab_shop_mail_info['body']['grand_total'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('brutto') + $tax_shipping);
		$yab_shop_mail_info['body']['tax'] = 0;
		$yab_shop_mail_info['label']['tax_system'] = yab_shop_lang('checkout_tax_exclusive', array('{tax}' => $default_tax_rate));

		if (!$body_form) {
			$body .= $eol.$line_1.
				$eol.$eol.$yab_shop_mail_info['label']['sub_total'].': '.$yab_shop_mail_info['body']['sub_total'].
				$eol.$yab_shop_mail_info['label']['shipping_costs'].': '.$yab_shop_mail_info['body']['shipping'].
				$eol.$eol.$line_2.
				$eol.$eol.$yab_shop_mail_info['label']['grand_total'].': '.$yab_shop_mail_info['body']['grand_total'].
				$eol.$yab_shop_mail_info['label']['tax_system'].
				$eol.$line_3.$eol.$line_2;
		}
	}
	else
	{
		$yab_shop_mail_info['body']['grand_total'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total + $tax_shipping);
		$yab_shop_mail_info['body']['tax'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax'));
		$yab_shop_mail_info['label']['tax_system'] = yab_shop_lang('checkout_tax_inclusive', array('{tax}' => $default_tax_rate));

		if (!$body_form) {
			$body .= $eol.$line_1.
				$eol.$eol.$yab_shop_mail_info['label']['sub_total'].': '.$yab_shop_mail_info['body']['sub_total'].
				$eol.$yab_shop_mail_info['label']['shipping_costs'].': '.$yab_shop_mail_info['body']['shipping'].
				$eol.$eol.$line_2.
				$eol.$eol.$yab_shop_mail_info['label']['grand_total'].': '.$yab_shop_mail_info['body']['grand_total'].
				$eol.$line_3.$eol.$line_2.
				$eol.$eol.$yab_shop_mail_info['label']['tax_system'].': '.$yab_shop_mail_info['body']['tax'].$eol;
		}
	}

	$yab_shop_mail_info['body']['after_products'] = ($affirmation == '1') ? yab_shop_lang('affirmation_mail_after_products') : yab_shop_lang('admin_mail_after_products');
	$yab_shop_mail_info['body']['promo'] = ($affirmation == '1') ? $promo_client : $promo_admin;

	if (!$body_form) {
		$body .= $yab_shop_mail_info['body']['promo'].$eol.$eol.$yab_shop_mail_info['body']['after_products'];
	}

	if ($body_form) {
		$body = parse($body_form);
	}

	return $body;
}

function yab_shop_build_mail_customs($item, $lang, $eol)
{
	$out = '';
	if ($item != '')
		$out = $lang.': '.$item.$eol;

	return $out;
}

function yab_shop_shop_mail($to, $subject, $body)
{
	global $prefs;

	if ($prefs['override_emailcharset'] and is_callable('utf8_decode'))
	{
		$charset = 'ISO-8859-1';
		$subject = utf8_decode($subject);
		$body = utf8_decode($body);
	}
	else
		$charset = 'UTF-8';

	if (!is_callable('mail'))
		return false;
	else
	{
		$eol = "\r\n";
		if (!is_windows())
			$eol = "\n";

		$sitename = yab_shop_mailheader($prefs['sitename'], 'text');
		$subject = yab_shop_mailheader($subject, 'text');

		return mail($to, $subject, $body,
			'From: '.$sitename.' <'.yab_shop_config('admin_mail').'>'.''.
			$eol.'Reply-To: '.$sitename.' <'.yab_shop_config('admin_mail').'>'.''.
			$eol.'X-Mailer: Textpattern (yab_shop)'.
			$eol.'Content-Transfer-Encoding: 8-bit'.
			$eol.'Content-Type: '.yab_shop_config('email_mime_type').'; charset="'.$charset.'"'.$eol
		);
	}
}

function yab_shop_mailheader($string, $type)
{
	global $prefs;

	if (!strstr($string,'=?') and !preg_match('/[\x00-\x1F\x7F-\xFF]/', $string))
	{
		if ("phrase" == $type)
		{
			if (preg_match('/[][()<>@,;:".\x5C]/', $string))
				$string = '"'.strtr($string, array("\\" => "\\\\", '"' => '\"')).'"';
		}
		elseif ("text" != $type)
			trigger_error('Unknown encode_mailheader type', E_USER_WARNING);
		return $string;
	}

	if ($prefs['override_emailcharset'])
	{
		$start = '=?ISO-8859-1?B?';
		$pcre	= '/.{1,42}/s';
	}
	else
	{
		$start = '=?UTF-8?B?';
		$pcre	= '/.{1,45}(?=[\x00-\x7F\xC0-\xFF]|$)/s';
	}

	$end = '?=';
	$sep = "\r\n";

	if (!is_windows())
		$sep = "\n";

	preg_match_all($pcre, $string, $matches);

	return $start.join($end.$sep.' '.$start, array_map('base64_encode',$matches[0])).$end;
}

// Main function to retrieve values for use in e-mail affirmation messages
function yab_shop_mail_info($atts, $thing=NULL) {
	global $yab_shop_mail_info;

	extract(lAtts(array(
		'type'    => 'body',
		'item'    => '',
		'wraptag' => '',
		'class'   => '',
		'break'   => '',
	),$atts));

	$data = is_array($yab_shop_mail_info) ? $yab_shop_mail_info : array();

	$items = do_list($item);
	$out = array();
	foreach ($items as $it) {
		if (isset($data[$type][$it])) {
			$out[] = $data[$type][$it];
		}
	}

	return ($out && $wraptag) ? doWrap($out, $wraptag, $break, $class) : join($break, $out);
}

// Three convenience e-mail info retrieval functions to save having to specify 'type'
function yab_shop_mail_body($atts, $thing=NULL) {
	$atts['type'] = 'body';
	return yab_shop_mail_info($atts, $thing);
}
function yab_shop_mail_label($atts, $thing=NULL) {
	$atts['type'] = 'label';
	return yab_shop_mail_info($atts, $thing);
}
function yab_shop_mail_item($atts, $thing=NULL) {
	$atts['type'] = 'item';
	return yab_shop_mail_info($atts, $thing);
}

function yab_shop_clean_input($input, $modus = 'output')
{
	if ($input == '')
		$cleaned = $input;

	if (is_array($input))
	{
		foreach ($input as $key => $val)
		{
			$cleaned[$key] = yab_shop_clean_input($val);
		}
	}
	else
	{
		$cleaned = str_replace(array('=', '&', '"', '\'', '<', '>', ';', '\\'), '', $input);
		if ($modus != 'output')
			$cleaned = doSlash($cleaned);
		else
			$cleaned = doStrip($cleaned);
	}
	return $cleaned;
}

function yab_shop_checkout_payment_methods()
{
	$option = '';
	$attr = '';
	$select = '';
	$content = '';
	$hidden_value = '';
	$label = tag(yab_shop_lang('checkout_payment'), 'label', ' for="payment"');
	$b = 0;

	if (yab_shop_config('payment_method_acc') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_acc');
		$option .= tag(yab_shop_lang('checkout_payment_acc'), 'option', ' value="'.yab_shop_lang('checkout_payment_acc').'"');
	}
	if (yab_shop_config('payment_method_pod') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_pod');
		$option .= tag(yab_shop_lang('checkout_payment_pod'), 'option', ' value="'.yab_shop_lang('checkout_payment_pod').'"');
	}
	if (yab_shop_config('payment_method_pre') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_pre');
		$option .= tag(yab_shop_lang('checkout_payment_pre'), 'option', ' value="'.yab_shop_lang('checkout_payment_pre').'"');
	}
	if (yab_shop_config('payment_method_paypal') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_paypal');
		$option .= tag(yab_shop_lang('checkout_payment_paypal'), 'option', ' value="'.yab_shop_lang('checkout_payment_paypal').'"');
	}
	if (yab_shop_config('payment_method_google') == '1')
	{
		$b++;
		$hidden_value .= yab_shop_lang('checkout_payment_google');
		$option .= tag(yab_shop_lang('checkout_payment_google'), 'option', ' value="'.yab_shop_lang('checkout_payment_google').'"');
	}

	if ($b == 0)
	{
		$content .= 'No payment method available!';
		$attr .= ' style="font-weight: bold; color: #9E0000"';
	}
	elseif ($b == 1)
	{
		$select .=	fInput('hidden', 'payment', $hidden_value, '', '', '', '', '', 'payment').
								tag($hidden_value, 'span', ' id="yab-shop-one-payment"');
		$content .= $label.$select;
	}
	else
	{
		$select .= tag($option, 'select', ' name="payment" id="payment"');
		$content .= $label.$select;
	}

	$payment = graf($content, ' class="yab-shop-payments"'.$attr);
	return $payment;
}

function yab_shop_checkout_qty_edit($itemid, $qty)
{
	$edit_form = tag(
			tag(
				hInput('editid', $itemid).
				fInput('number','editqty',$qty,'','','','1').
				fInput('submit','edit',yab_shop_lang('checkout_edit'), 'submit-edit').
				fInput('submit','del',yab_shop_lang('checkout_delete'), 'submit-del'),
			'div'),
	'form', ' method="post" action="'.yab_shop_mlp_inject(pagelinkurl(array('s' => yab_shop_config('checkout_section_name')))).'"'
	);
	return $edit_form;
}

function yab_shop_build_checkout_customs($item, $lang, $conf)
{
	$conf = yab_shop_ascii($conf);
	$out = '';
	if ($item != '')
	{
		$item = htmlspecialchars($item);
		$out = tag($lang.': '.$item, 'li', ' class="yab-checkout-item-'.$conf.'"');
	}
	return $out;
}

function yab_shop_build_custom_select_tag($custom_field, $label_name, $hide = false, $required = false )
{
	global $thisarticle;
	$custom_field_low = strtolower($custom_field);
	$out = '';
	$id = $thisarticle['thisid'];

	if ($thisarticle[$custom_field_low] != '')
	{
		$custom_field_ascii = yab_shop_ascii($custom_field);

		if( $hide )
			$out = yab_shop_custom_field(array('name' => $custom_field, 'hide'=>true ));
		else
		{
			$out =	graf(
				tag($label_name.': ', 'label', ' for="select-'.$custom_field_ascii.'-'.$id.'"').
				yab_shop_custom_field(array('name' => $custom_field, 'required' => $required)),' class="yab-add-select-'.$custom_field_ascii.(($required) ? ' yab-shop-required' : '').'"'
  		);
		}
	}
	return $out;
}

// Work out which of the shipping options are valid and return
// an array of useful shipping info.
// Calculate shipping costs based on weight (if appropriate) and any special shipping extras per product.
// Take into account a carrier's weight limit and bomb out if its exceeded.
// Free shipping limit is honoured.
function yab_shop_available_shipping_options()
{
	$cart =& $_SESSION[ yab_shop_cartname() ];

	$sub_total = $cart->total;
	$method = $cart->get_ship_method();
	$weight = 0.00;
	$shipping_costs = 0.00;
	$cost_found = false;
	$out = array();

	if (yab_shop_config('custom_field_weight') != '') {
		foreach ($cart->get_contents() as $item) {
			$weight += $item['weight'] * $item['qty'];
		}
	}

	$weight = floatval($weight);
	$shipping_bands = do_list(yab_shop_replace_commas(yab_shop_config('shipping_costs')), '|');
	$num_shipping_bands = count($shipping_bands);
	$free_shipping = yab_shop_replace_commas(yab_shop_config('free_shipping'));
	$shipping_options = '';
	$disabled_methods = 0;

	$out['weight'] = $weight;
	$out['unavailable']['method'] = $out['unavailable']['msg'] = array();

	// Create the shipping options dropdown and find the
	// shipping costs using the current method (if valid)
	foreach ($shipping_bands as $sband) {
		$weight_bands = do_list($sband, ';');
		$sidx = array_shift($weight_bands);
		$band_name = array_shift($weight_bands);
		$numbands = count($weight_bands) - 1;

		foreach ($weight_bands as $widx => $wband) {
			$wparts = do_list($wband, '--');
			$cweight = floatval($wparts[0]);
			// Is the cart weight under the current band or have we
			// reached the end of the bands without finding a match
			// (i.e. cart is heavier than the heaviest defined weight)
			if ( $weight <= $cweight || (($weight > $cweight) && $widx == $numbands) ) {
				if (strpos(strtolower($wparts[0]), 'max') !== false) {
					if ($weight > $cweight) {
						$out['unavailable']['method'][] = $sidx;
						$out['unavailable']['msg'][] = 'yab_err_max_weight';
					}
				}
				if ($cost_found === false) {
					if ($weight > 0 && ($method == '' || $method == $sidx) ) {
						$shipping_costs = floatval($wparts[1]);
						$cost_found = true;
					}
				}
			}
		}

		if (is_numeric($sidx)) {
			// Regular flat-rate shipping
			$shipping_costs = $sidx;
		} else if (count($shipping_bands) == 1) {
			// One band
			$shipping_options = $band_name;
			$cart->set_ship_method($sidx);
			$method = $sidx;
		} else {
			// Multi-band
			$numbands = count($weight_bands) - 1;
			$disabled = false;
			if (in_array($sidx, $out['unavailable']['method'])) {
				$disabled_methods++;
				$disabled = true;
			}
			$shipping_options .= '<option value="' . $sidx . '"' . (($method == $sidx) ? ' selected="selected"' : '') . (($disabled) ? ' disabled="disabled"' : '') . '>' . $band_name . '</option>';
		}
	}

	if ($num_shipping_bands > 1) {
		$shipping_options = '<form method="post" id="yab-ship-method" action="'.yab_shop_mlp_inject(pagelinkurl(array('s' => yab_shop_config('checkout_section_name')))).'" name="edit_ship_method"><select name="shipping_band" onchange="this.form.submit()">' . $shipping_options .'</select></form>';
	}

	if (yab_shop_config('custom_field_shipping_name') != '')
	{
		$special_cost = 0.00;
		foreach ($cart->get_contents() as $item)
			$special_cost += yab_shop_replace_commas($item['spec_shipping']);

		$shipping_costs += $special_cost;
	}

	// Current shipping method unavailable? Say so
	if (isset($out['unavailable']['method']) && in_array($method, $out['unavailable']['method'])) {
		$out['method_available'] = false;
		$shipping_costs = 'NA';
	} else {
		$out['method_available'] = true;
	}

	// No valid shipping options available at all? Say so
	if ($disabled_methods >= $num_shipping_bands) {
		$shipping_options = yab_shop_lang('please_call');
		$shipping_costs = 'NA';
	}

	// Still honour free shipping even if weight has exceeded carrier's limit, because
	// the shipping costs are loaded into the price meaning the merchant can ship by any
	// method they choose
	if ($sub_total >= $free_shipping) {
		$shipping_costs = 0.00;
		$shipping_options = yab_shop_lang('checkout_free_shipping');
		$out['method_available'] = true; // hack
	}

	$out['shipping_costs'] = $shipping_costs;
	$out['shipping_method'] = $method;
	$out['shipping_options'] = $shipping_options;

	return $out;
}

//TODO: Calculate tax on shipping?
function yab_shop_calculate_sum($what)
{
	$cart =& $_SESSION[ yab_shop_cartname() ];
	$tax_inclusive = yab_shop_config('tax_inclusive');
	$calculated = array('netto' => 0, 'brutto' => 0, 'tax' => 0);
	$mycart = $cart->get_contents();
	foreach ($mycart as $bought) {
		$sub_total = $bought['subtotal'];
		if ($tax_inclusive == '0')
		{
			$calculated['netto'] += $sub_total;
			$calculated['brutto'] += yab_shop_rounding($sub_total * ($bought['tax'] / 100 + 1));
			$calculated['tax'] += yab_shop_rounding($sub_total * ($bought['tax'] / 100));
		}
		else
		{
			$calculated['brutto'] += $sub_total;
			$gtax = yab_shop_rounding($sub_total / ($bought['tax'] / 100 + 1), 'down');
			$calculated['netto'] += $gtax;
			$calculated['tax'] += yab_shop_rounding($gtax * ($bought['tax'] / 100));
		}
	}
	return $calculated[$what];
}

function yab_shop_rounding($value, $modus = 'up')
{
	$decimal = 2;

	$rounded = ceil($value * pow(10, $decimal)) / pow(10, $decimal);
	if ($modus != 'up')
		$rounded = floor($value * pow(10, $decimal)) / pow(10, $decimal);

	return $rounded;
}

function yab_shop_tax_rates() {
	$tax_bands = do_list(yab_shop_replace_commas(yab_shop_config('tax_rate')), '|');
	return array($tax_bands, $tax_bands[0]);
}

function yab_shop_replace_commas($input)
{
	$replaced = str_replace(',', '.', $input);
	return $replaced;
}

function yab_shop_currency_out($currency, $what, $toform = '')
{
	if ($toform === 'NA') {
		$currency = 'UNAVAILABLE';
	} else {
		$toform = (double)$toform;
	}

	switch ($currency)
	{
		case 'USD':
			$out = array(
				'cur'    => '$',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'GBP':
			$out = array(
				'cur'    => '£',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'CAD':
			$out = array(
				'cur'    => '$',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'JPY':
			$out = array(
				'cur'    => '?',
				'toform' => number_format($toform)
			);
			break;
		case 'AUD':
			$out = array(
				'cur'    => '$',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'NZD':
			$out = array(
				'cur'    => '$',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'CHF':
			$out = array(
				'cur'    => 'SFr.',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'HKD':
			$out = array(
				'cur'    => '$',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'SGD':
			$out = array(
				'cur'    => '$',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'SEK':
			$out = array(
				'cur'    => 'kr',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'DKK':
			$out = array(
				'cur'    => 'kr',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'PLN':
			$out = array(
				'cur'    => 'z?',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'NOK':
			$out = array(
				'cur'    => 'kr',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'HUF':
			$out = array(
				'cur'    => 'Ft',
				'toform' => number_format($toform)
			);
			break;
		case 'CZK':
			$out = array(
				'cur'    => 'Kc(',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'EEK':
			$out = array(
				'cur'    => 'kr',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'RSD':
			$out = array(
				'cur'    => 'RSD ',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'BRL':
			$out = array(
				'cur'    => 'R$',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'ZAR':
			$out = array(
				'cur'    => 'R',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'PHP':
			$out = array(
				'cur'    => 'PhP ',
				'toform' => number_format($toform, 2)
			);
			break;
		case 'RON':
			$out = array(
				'cur'    => 'lei ',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
		case 'UNAVAILABLE':
			$out = array(
				'cur'    => '',
				'toform' => yab_shop_lang('value_unavailable'),
			);
			break;
		default:
			$out = array(
				'cur'    => '€',
				'toform' => number_format($toform, 2, ',', '.')
			);
			break;
	}
	return $out[$what];
}

function yab_shop_type_select_custom($array, $name = 'type', $hide=false, $required=false)
{
	global $thisarticle;
	$id = $thisarticle['thisid'];
	$name_ascii = yab_shop_ascii($name);

	if( $hide )
	{
		$option = htmlspecialchars(trim($array[0]));
		$out = '<input name="'.$name_ascii.'" id="select-'.$name_ascii.'-'.$id.'" value="'.$option.'" type="hidden"/>'.n;
	}
	else
	{
		$req = ($required) ? ' required="1"' : '';
		$out = '<select name="'.$name_ascii.'" id="select-'.$name_ascii.'-'.$id.'"'.$req.'>'.n;
		foreach ($array as $option)
		{
			$option = htmlspecialchars(trim($option));
			$out .= t.'<option value="'.$option.'">'.$option.'</option>'.n;
		}
		$out .= '</select>'.n;
	}

	return $out;
}

function yab_shop_start_session()
{
	if (headers_sent())
	{
		if (!isset($_SESSION))
			$_SESSION = array();

		return false;
	}
	elseif (!isset($_SESSION))
	{
		session_cache_limiter("must-revalidate");
		session_start();
		return true;
	}
	else
		return true;
}

function yab_shop_ascii($input)
{
	if (!preg_match('/![a-zA-Z0-9]/', $input))
	{
		$out = htmlentities($input, ENT_NOQUOTES, 'UTF-8');
		$out = preg_replace('/[^{$a-zA-Z0-9}]/', '', $out);
		$out = strtolower($out);
	}
	else
		$out = strtolower($input);

	return $out;
}

function yab_promocode()
{
	$cart =& $_SESSION[ yab_shop_cartname() ];
	$pcode = ps('yab-promo');

	if (yab_shop_config('promocode') != '')
	{
		$discounts = do_list(yab_shop_config('promo_discount_percent'));
		$codes = do_list(yab_shop_config('promocode'));
		$pos = array_search($pcode, $codes);
		if ($pcode != '')
		{
			if ($pos !== false)
			{
				$cart->set_promocode($pcode);
				$discount = (isset($discounts[$pos])) ? $discounts[$pos] : 0;
				foreach ($cart->get_contents() as $item)
				{
					if ($item['promocode'] == 0)
					{
						// Promo not yet applied to this product, so apply it
						$cart->edit_promocodes($item['itemid'], 1);
						$cart->edit_promo_prices($item['itemid'], yab_calc_promo_prices($item['price'], $discount));
					}
				}
			}
		}
		else
		{
			$code_in_use = $cart->get_promocode();
			if ($code_in_use != NULL)
			{
				$pos = array_search($code_in_use, $codes);
				$discount = (isset($discounts[$pos])) ? $discounts[$pos] : 0;
				foreach ($cart->get_contents() as $item)
				{
					if ($item['promocode'] == 0)
					{
						$cart->edit_promocodes($item['itemid'], 1);
						$cart->edit_promo_prices($item['itemid'], yab_calc_promo_prices($item['price'], $discount));
					}
				}
			}
		}
	}
	return true;
}

function yab_build_promo_input($cart)
{
	$pcode = ps('yab-promo');
	$out = '';

	if (yab_shop_config('promocode') != '')
	{
		$discounts = do_list(yab_shop_config('promo_discount_percent'));

		if ($pcode != '')
		{
			if ( ($pos = array_search($pcode, do_list(yab_shop_config('promocode')))) !== false )
			{
				$out .= graf(yab_shop_lang('promocode_success', array('{discount}' => $discounts[$pos])), ' class="yab-shop-notice yab-promo-success"');
			}
			else
			{
				$out .= graf(yab_shop_lang('promocode_error'), ' class="yab-shop-notice yab-promo-error"').
								tag(
									graf(
										tag(yab_shop_lang('promocode_label'), 'label', ' for="yab-promo"').
											fInput('text','yab-promo','','','','','','','yab-promo').
											fInput('submit','',yab_shop_lang('promocode_button'), 'yab-promo-submit'),
										' class="yab-promocode"'),
									'form', ' method="post" action="'.yab_shop_mlp_inject(pagelinkurl(array('s' => yab_shop_config('checkout_section_name')))).'"'
								);
			}
		}
		else
		{
			if ( ($scode = $cart->get_promocode()) != NULL)
			{
				$pos = array_search($scode, do_list(yab_shop_config('promocode')));
				$out .= graf(yab_shop_lang('promocode_success', array('{discount}' => $discounts[$pos])), ' class="yab-shop-notice yab-promo-success"');
			}
			else
			{
				$out .= tag(
					graf(
						tag(yab_shop_lang('promocode_label'), 'label', ' for="yab-promo"').
						fInput('text','yab-promo','','','','','','','yab-promo').
						fInput('submit','',yab_shop_lang('promocode_button'), 'yab-promo-submit'),
					' class="yab-promocode"'),
				'form', ' method="post" action="'.yab_shop_mlp_inject(pagelinkurl(array('s' => yab_shop_config('checkout_section_name')))).'"'
				);
			}
		}
	}
	return $out;
}

function yab_calc_promo_prices($price = false, $discount = 0)
{
	$price_tmp = $price * ($discount / 100);
	$price = $price - $price_tmp;
	return round($price, 2);
}

function yab_remember_checkbox()
{
	$rememberCookie = cs('yab_shop_remember');
	$remember = ps('remember');
	$forget = ps('forget');
	if ($rememberCookie === '')
	{
		$checkbox_type = 'remember';
		$remember = 1;
	}
	elseif ($rememberCookie == 1)
		$checkbox_type = 'forget';
	else
		$checkbox_type = 'remember';

	if ($checkbox_type == 'forget')
	{
		if ($forget == 1)
			yab_shop_destroyCookies();

		$checkbox = checkbox('forget', 1, $forget, '', 'yab-remember').tag(yab_shop_lang('forget_me'), 'label', ' for="yab-remember"');
	}
	else
	{
		if ($remember != 1)
			yab_shop_destroyCookies();

		$checkbox = checkbox('remember', 1, $remember, '', 'yab-remember').tag(yab_shop_lang('remember_me'), 'label', ' for="yab-remember"');
	}

	$checkbox .= ' '.hInput('checkbox_type', $checkbox_type);

	return $checkbox;
}

function yab_shop_setCookies($nv = array())
{
	$cookietime = time() + (365*24*3600);
	ob_start();
	foreach ($nv as $idx => $val) {
		setcookie('yab_shop_'.$idx, $val, $cookietime, '/');
	}
	setcookie('yab_shop_last', date('H:i d/m/Y'), $cookietime, '/');
	setcookie('yab_shop_remember', '1', $cookietime, '/');
}

function yab_shop_destroyCookies($nv = array())
{
	$cookietime = time()-3600;
	$req_matrix = yab_shop_required_fields();

	ob_start();
	$nv = (empty($nv)) ? array(
		'firstname'.$req_matrix['firstname']['mod'],
		'surname'.$req_matrix['surname']['mod'],
		'street'.$req_matrix['street']['mod'],
		'postal'.$req_matrix['postal']['mod'],
		'city'.$req_matrix['city']['mod'],
		'state'.$req_matrix['state']['mod'],
		'country'.$req_matrix['country']['mod'],
		'phone'.$req_matrix['phone']['mod'],
		'email'.$req_matrix['email']['mod']
		) : $nv;
	foreach ($nv as $idx) {
		setcookie('yab_shop_'.$idx, '', $cookietime, '/');
	}
	setcookie('yab_shop_last', '', $cookietime, '/');
	setcookie('yab_shop_remember', '0', $cookietime + (365*25*3600), '/');
}

function yab_remember($remember, $forget, $checkbox_type)
{
	if ($remember == 1 || $checkbox_type == 'forget' && $forget != 1)
	{
		$req_matrix = yab_shop_required_fields();
		$cookie_block = array(
			'firstname'.$req_matrix['firstname']['mod'] => ps('firstname'.$req_matrix['firstname']['mod']),
			'surname'.$req_matrix['surname']['mod']     => ps('surname'.$req_matrix['surname']['mod']),
			'street'.$req_matrix['street']['mod']       => ps('street'.$req_matrix['street']['mod']),
			'postal'.$req_matrix['postal']['mod']       => ps('postal'.$req_matrix['postal']['mod']),
			'city'.$req_matrix['city']['mod']           => ps('city'.$req_matrix['city']['mod']),
			'state'.$req_matrix['state']['mod']         => ps('state'.$req_matrix['state']['mod']),
			'country'.$req_matrix['country']['mod']     => ps('country'.$req_matrix['country']['mod']),
			'phone'.$req_matrix['phone']['mod']         => ps('phone'.$req_matrix['phone']['mod']),
			'email'.$req_matrix['email']['mod']         => ps('email'.$req_matrix['email']['mod']),
		);
		yab_shop_setCookies($cookie_block);
	}
	else
	{
		yab_shop_destroyCookies();
	}
}

function yab_shop_checkout_image($articleid)
{
	global $img_dir;
	$out = '';

	if (yab_shop_config('use_checkout_images') == '1')
	{
		$rsi = safe_row('*', 'textpattern', "ID = $articleid");
		if ($rsi)
		{
			if (is_numeric(intval($rsi['Image'])))
			{
				$rs = safe_row('*', 'txp_image', 'id = '.intval($rsi['Image']));
				if ($rs)
				{
					if ($rs['thumbnail'])
					{
						extract($rs);
						$alt = htmlspecialchars($alt);
						$out .= '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'" />';
					}
				}
			}
		}
	}
	return $out;
}

function yab_shop_redirect($uri)
{
	if ($uri != '')
	{
		$uri = yab_shop_mlp_inject($uri);
		txp_status_header('303 See Other');
		header('Location: '.$uri);
		header('Connection: close');
		header('Content-Length: 0');
	}
	else
		return false;
}


//=======================================
// SHOPPING CART AND MERCHANT INTEGRATION
//=======================================
if (!function_exists('CalcHmacSha1'))
{
/**
 * Copyright (C) 2007 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *			http://www.apache.org/licenses/LICENSE-2.0
 *
 * Calculates the cart's hmac-sha1 signature, this allows google to verify
 * that the cart hasn't been tampered by a third-party.
 *
 * @link http://code.google.com/apis/checkout/developer/index.html#create_signature
 *
 * Modified merchant key validation and access by Joe Wilson <http://www.joecode.com/txp/82/joe_gcart>
 */

function CalcHmacSha1($data,$merchant_key)
{
	$key = $merchant_key;
	$blocksize = 64;
	$hashfunc = 'sha1';

	if (strlen($key) > $blocksize)
	{
		$key = pack('H*', $hashfunc($key));
	}

	$key = str_pad($key, $blocksize, chr(0x00));
	$ipad = str_repeat(chr(0x36), $blocksize);
	$opad = str_repeat(chr(0x5c), $blocksize);
	$hmac = pack(
		'H*', $hashfunc(
			($key^$opad).pack(
				'H*', $hashfunc(
					($key^$ipad).$data
				)
			)
		)
	);
	return $hmac;
}
}

if (!class_exists('yab_shop_wfCart'))
{

/**
 * Modified Webforce Cart v.1.5
 *
 * 2008-03-02 Cleaned some code (Tommy Schmucker)
 * 2008-12-18 Modified with promocode-support (Tommy Schmucker)
 * 2009-01-27 Modified with Txp-ID-support (Tommy Schmucker)
 * 2012-02-06 Modified with multi-promocode support and cart weight (Stef Dawson)
 *
 * Webforce Cart v.1.5
 * A Session based, Object Oriented Shopping Cart Component for PHP.
 *
 * (c) 2004-2005 Webforce Ltd, NZ
 * http://www.webforcecart.com/
 * all rights reserved
 *
 * Webforce cart is free software. Licence LGPL. (c) 2004-2005 Webforce Ltd, New Zealand.
 * Licence: LGPL - http://www.gnu.org/copyleft/lesser.txt
 *
 */

class yab_shop_wfCart
{
	var $total = 0;
	var $ship_method = '';
	var $itemcount = 0;
	var $items = array();
	var $itemprices = array();
	var $itemrrps = array();
	var $itemsavings = array();
	var $itemnames = array();
	var $itemproperties_1 = array();
	var $itemproperties_2 = array();
	var $itemproperties_3 = array();
	var $itemspecshipping = array();
	var $itemqtys = array();
	var $promocode = NULL;
	var $promocodes = array();
	var $itemtxpids = array();
	var $itemtaxes = array();
	var $itemweights = array();

	function set_promocode($value) {
		$this->promocode = $value;
	}

	function get_promocode() {
		return $this->promocode;
	}

	function edit_promocodes($itemid, $value) {
		$this->promocodes[$itemid] = $value;
	}

	function edit_promo_prices($itemid, $value) {
		$this->itemprices[$itemid] = $value;
		$this->_update_total();
	}

	function set_ship_method($value) {
		$this->ship_method = $value;
	}

	function get_ship_method() {
		return $this->ship_method;
	}

	function get_contents() {
		$items = array();
		foreach ($this->items as $tmp_item)
		{
			$item = false;
			$item['itemid'] = $tmp_item;
			$item['qty'] = $this->itemqtys[$tmp_item];
			$item['price'] = $this->itemprices[$tmp_item];
			$item['rrp'] = $this->itemrrps[$tmp_item];
			$item['price_saving'] = $this->itemsavings[$tmp_item];
			$item['name'] = $this->itemnames[$tmp_item];
			$item['property_1'] = $this->itemproperties_1[$tmp_item];
			$item['property_2'] = $this->itemproperties_2[$tmp_item];
			$item['property_3'] = $this->itemproperties_3[$tmp_item];
			$item['spec_shipping'] = $this->itemspecshipping[$tmp_item];
			$item['promocode'] = $this->promocodes[$tmp_item];
			$item['txpid'] = $this->itemtxpids[$tmp_item];
			$item['tax'] = $this->itemtaxes[$tmp_item];
			$item['weight'] = $this->itemweights[$tmp_item];
			$item['subtotal'] = $item['qty'] * $item['price'];
			$item['ship_method'] = $this->ship_method;
			$items[] = $item;
		}
		return $items;
	}

	function add_item($itemid, $txpid, $qty = 1, $price = false, $rrp = false, $saving, $name = false, $property_1 = false, $property_2 = false, $property_3 = false, $spec_shipping = false, $tax = false, $weight = false) {
		if ($qty > 0)
		{
			if (isset($this->itemqtys[$itemid]) and $this->itemqtys[$itemid] > 0)
			{
				$this->itemqtys[$itemid] += $qty;
				$this->_update_total();
			}
			else
			{
				$this->items[]= $itemid;
				$this->itemqtys[$itemid] = $qty;
				$this->itemprices[$itemid] = $price;
				$this->itemrrps[$itemid] = $rrp;
				$this->itemsavings[$itemid] = $saving;
				$this->itemnames[$itemid] = $name;
				$this->itemproperties_1[$itemid] = $property_1;
				$this->itemproperties_2[$itemid] = $property_2;
				$this->itemproperties_3[$itemid] = $property_3;
				$this->itemspecshipping[$itemid] = $spec_shipping;
				$this->itemtaxes[$itemid] = $tax;
				$this->itemweights[$itemid] = $weight;
				$this->promocodes[$itemid] = 0;
				$this->itemtxpids[$itemid] = $txpid;
			}
			$this->_update_total();
		}
	}

	function edit_item($itemid, $qty) {
		if ($qty < 1)
		{
			$this->del_item($itemid);
		}
		else
		{
			$this->itemqtys[$itemid] = $qty;
		}
		$this->_update_total();
	}

	function del_item($itemid) {
		$ti = array();
		$this->itemqtys[$itemid] = 0;
		foreach ($this->items as $item)
		{
			if ($item != $itemid)
			{
				$ti[] = $item;
			}
		}
		$this->items = $ti;
		unset($this->itemprices[$itemid]);
		unset($this->itemrrps[$itemid]);
		unset($this->itemsavings[$itemid]);
		unset($this->itemnames[$itemid]);
		unset($this->itemproperties_1[$itemid]);
		unset($this->itemproperties_2[$itemid]);
		unset($this->itemproperties_3[$itemid]);
		unset($this->itemspecshipping[$itemid]);
		unset($this->itemqtys[$itemid]);
		unset($this->itemtaxes[$itemid]);
		unset($this->itemweights[$itemid]);
		unset($this->promocodes[$itemid]);
		unset($this->itemtxpids[$itemid]);
		$this->_update_total();
	}

	function empty_cart() {
		$this->total = 0;
		$this->ship_method = '';
		$this->itemcount = 0;
		$this->items = array();
		$this->itemprices = array();
		$this->itemrrps = array();
		$this->itemsavings = array();
		$this->itemproperties_1 = array();
		$this->itemproperties_2 = array();
		$this->itemproperties_3 = array();
		$this->itemspecshipping = array();
		$this->itemqtys = array();
		$this->itemtaxes = array();
		$this->itemweights = array();
		$this->promocode = NULL;
		$this->promocodes = array();
		$this->itemtxpids = array();
	}

	function _update_total() {
		$this->itemcount = 0;
		$this->total = 0;
		if (sizeof($this->items > 0))
		{
			foreach ($this->items as $item)
			{
				$this->total += ($this->itemprices[$item] * $this->itemqtys[$item]);
				$this->itemcount++;
			}
		}
	}
}
}

if (!class_exists('PayPalEWP'))
{

/**
 * The PayPal class implements the dynamic encryption of
 * PayPal "buy now" buttons using the PHP openssl functions.
 *
 * Original Author: Ivor Durham (ivor.durham@ivor.cc)
 * Edited by PayPal_Ahmad	(Nov. 04, 2006)
 * Posted originally on PDNCommunity:
 * http://www.pdncommunity.com/pdn/board/message?board.id=ewp&message.id=87#M87
 *
 * Using the orginal code on PHP 4.4.4 on WinXP Pro
 * I was getting the following error:
 *
 * "The email address for the business is not present in the encrypted blob.
 * Please contact your merchant"
 *
 * I modified and cleaned up a few things to resolve the error - this was
 * tested on PHP 4.4.4 + OpenSSL on WinXP Pro
 *
 * Modified 2008 by tf@1agency.de for PHP5 and PHPDoc
 *
 * 2008-03-26 Modified for usage with PHP4, Textpattern and
 * website payments pro (german: Standard-Zahlungslösung)
 * extended error handling
 *
 * @copyright Ivor Durham <ivor.durham@ivor.cc>
 * @copyright PayPal_Ahmad	(Nov. 04, 2006)
 * @copyright Unknown Modifier
 * @copyright Thomas Foerster <tf@1agency.de>
 * @copyright Tommy Schmucker
 * @package PayPal
 */

class PayPalEWP
{
	var $certificate;
	var $certificateFile;
	var $privateKey;
	var $privateKeyFile;
	var $paypalCertificate;
	var $paypalCertificateFile;
	var $certificateID;
	var $tempFileDirectory;
	var $error;

	/**
	 * Constructor
	 *
	 */
	function __PayPalEWP()
	{
			$this->error = 0;
	}

	function setTempDir($tempdir)
	{
		$this->tempFileDirectory = $tempdir;
	}

	/**
	 * Sets the ID assigned by PayPal to the client certificate
	 *
	 * @param string $id The certificate ID assigned when the certificate
	 * was uploaded to PayPal
	 */
	function setCertificateID($id)
	{
		if ($id != '')
		{
			$this->certificateID = $id;
		}
		else
		{
			$this->error = 1;
		}
	}

	/**
	 * Set the client certificate and private key pair.
	 *
	 * @param string $certificateFilename The path to the client
	 * (public) certificate
	 * @param string $privateKeyFilename The path to the private key
	 * corresponding to the certificate
	 * @return bool TRUE if the private key matches the certificate,
	 * FALSE otherwise
	 */
	function setCertificate($certificateFilename, $privateKeyFilename)
	{
	 if (is_readable($certificateFilename) and is_readable($privateKeyFilename))
	 {
			$handle = fopen($certificateFilename, "r");
			if ($handle === false)
			{
				return false;
			}

			$size = filesize($certificateFilename);
			$certificate = fread($handle, $size);
			@fclose($handle);

			unset($handle);

			$handle = fopen($privateKeyFilename, "r");
			if ($handle === false)
			{
				return false;
			}

			$size = filesize($privateKeyFilename);
			$privateKey = fread($handle, $size);
			@fclose($handle);

			if (($certificate !== false) and ($privateKey !== false) and openssl_x509_check_private_key($certificate, $privateKey))
			{
				$this->certificate		 = $certificate;
				$this->certificateFile = $certificateFilename;
				$this->privateKey			= $privateKey;
				$this->privateKeyFile	= $privateKeyFilename;
				return true;
			}
		}
		else
		{
			$this->error = 2;
			return false;
		}
	}

	/**
	 * Sets the PayPal certificate
	 *
	 * @param string $fileName The path to the PayPal certificate
	 * @return bool TRUE if the certificate is read successfully,
	 * FALSE otherwise.
	 */
	function setPayPalCertificate($fileName)
	{
		if (is_readable($fileName))
		{
			$handle = fopen($fileName, "r");
			if ($handle === false)
			{
				return false;
			}

			$size = filesize($fileName);
			$certificate = fread($handle, $size);
			if ($certificate === false)
			{
				return false;
			}

			fclose($handle);

			$this->paypalCertificate		 = $certificate;
			$this->paypalCertificateFile = $fileName;

			return true;
		}
		else
		{
			$this->error = 3;
			return false;
		}
	}

	/**
	 * Using the previously set certificates and the tempFileDirectory to
	 * encrypt the button information
	 *
	 * @param array $parameters Array with parameter names as keys
	 * @return mixed The encrypted string OR false
	 */
	function encryptButton($parameters)
	{
		if (($this->certificateID == '') or !isset($this->certificate) or !isset($this->paypalCertificate))
		{
			return false;
		}

		$clearText = '';
		$encryptedText = '';

		$data = "cert_id=" . $this->certificateID . "\n";

		foreach($parameters as $k => $v)
		{
			$d[] = "$k=$v";
		}

		$data .= join("\n", $d);

		$dataFile = tempnam($this->tempFileDirectory, 'data');

		$out = fopen("{$dataFile}_data.txt", 'wb');
		fwrite($out, $data);
		fclose($out);

		$out = fopen("{$dataFile}_signed.txt", "w+");

		if (!openssl_pkcs7_sign("{$dataFile}_data.txt", "{$dataFile}_signed.txt", $this->certificate, $this->privateKey, array(), PKCS7_BINARY))
		{
			$this->error = 4;
			return false;
		}

		fclose($out);

		$signedData = explode("\n\n", file_get_contents("{$dataFile}_signed.txt"));

		$out = fopen("{$dataFile}_signed.txt", 'wb');
		fwrite($out, base64_decode($signedData[1]));
		fclose($out);

		if (!openssl_pkcs7_encrypt("{$dataFile}_signed.txt", "{$dataFile}_encrypted.txt", $this->paypalCertificate, array(), PKCS7_BINARY))
		{
			$this->error = 4;
			return false;
		}

		$encryptedData = explode("\n\n", file_get_contents("{$dataFile}_encrypted.txt"));

		$encryptedText = $encryptedData[1];

		@unlink($dataFile);
		@unlink("{$dataFile}_data.txt");
		@unlink("{$dataFile}_signed.txt");
		@unlink("{$dataFile}_encrypted.txt");

		return "-----BEGIN PKCS7-----\n".$encryptedText."\n-----END PKCS7-----";
	}
}
}

#===============================================================================
#	MLP Pack integration. Inject the l10n language (if known)...
#===============================================================================
function yab_shop_cartname()
{
	$lang = yab_shop_mlp_select_lang();
	return ($lang) ? 'yab_shop_wfcart_'.$lang : 'yab_shop_wfcart' ;
}

function yab_shop_mlp_select_lang( $default = '', $type = 'short' )
{
	global $l10n_language;
	$out = ( isset($l10n_language) ) ? $l10n_language[$type] : $default ;
	return $out;
}

function yab_shop_mlp_inject($in)
{
	global $l10n_language;
	$out = ( isset($l10n_language) ) ? strtr($in, array( hu => hu.$l10n_language['short'].'/' ) ) : $in ;
	return $out;
}

class yab_shop_MLP {
	var $strings;
	var $owner;
	var $prefix;
	var $lang;
	var $event;
	function yab_shop_MLP($plug, $strarray, $prefx='', $lng='en-gb', $ev='public') {
		$this->owner = $plug;
		$this->prefix = (empty($prefx)) ? strtolower( strtr($plug, array('-'=>'_') ) ) : $prefx;
		$this->strings = $strarray;
		$this->lang = $lng;
		$this->event = $ev;    // 'public', 'admin' or 'common'
		register_callback(array(&$this, 'yab_shop_Callback'), 'l10n.enumerate_strings');
	}
	function yab_shop_Callback($event='l10n.enumerate_strings', $step='', $pre=0) {
		$r = array(
			'owner' => $this->owner,
			'prefix' => $this->prefix,
			'lang' => $this->lang,
			'event' => $this->event,
			'strings' => $this->strings,
		);
		return $r;
	}
	// Generic lookup
	//  $what = key to look up
	//  $args = any arguments the key is expecting for replacement
	function gTxt($what, $args = array()) {
		global $textarray;

		// Prepare the prefixed key for use
		$key = $this->prefix . '-' . $what;
		$key = strtolower($key);

		// Grab from the global textarray (possibly edited by MLP) if we can
		if(isset($textarray[$key])) {
			$str = $textarray[$key];
		} else {
			// The string isn't in the localised textarray so fallback to using
			// the (non prefixed) string array in the plugin
			$key = strtolower($what);
			$str = (isset($this->strings[$key])) ? $this->strings[$key] : $what;
		}
		// Perform substitutions
		if(!empty($args)) {
			$str = strtr($str, $args);
		}

		return $str;
	}
}

//================
// ADMIN INTERFACE
//================
if (@txpinterface == 'admin')
{
  $_yab_shop_admin_i18n = array(
    #
		# Labels for the shop prefs page. No longer need the labels for the l10n
		# page as the MLP pack takes care of that for free.
		#
		'shop_common_prefs'                 => 'Shop common settings',
		'product_prefs'                     => 'Product settings',
		'prefs_checkout'                    => 'Checkout settings',
		'paypal_prefs'                      => 'Paypal settings',
		'prefs_payment'                     => 'Payment settings',
		'google_prefs'                      => 'Google checkout settings',
		'tax_rate'                          => 'Tax bands (%, pipe-delimited, base rate first)',
		'shipping_costs'                    => 'Shipping costs (fixed value or weight-table)',
		'shipping_via'                      => 'Shipping via (Used by Google Checkout)',
		'free_shipping'                     => 'Free shipping at',
		'currency'                          => 'Currency (<a href="http://www.xe.com/iso4217.php">ISO 4217</a>)',
		'promocode'                         => 'Promocode key(s) (comma-delimited)',
		'promo_discount_percent'            => 'Promo discounts (%, comma-delimited: one per promocode)',
		'tax_inclusive'                     => 'Tax inclusive (otherwise exclusive)',
		'payment_method_acc'                => 'Use payment method: Purchase on account',
		'payment_method_pod'                => 'Use payment method: Purchase on delivery',
		'payment_method_pre'                => 'Use payment method: Purchase against prepayment',
		'payment_method_paypal'             => 'Use payment method: Paypal checkout',
		'payment_method_google'             => 'Use payment method: Google checkout',
		'using_checkout_state'              => 'Use state field in checkout form',
		'using_checkout_country'            => 'Use country field in checkout form',
		'using_tou_checkbox'                => 'Use Terms Of Use checkbox in checkout form',
		'tou_link'                          => 'Terms Of Use link',
		'checkout_section_name'             => 'Name of the checkout section',
		'checkout_thanks_site'              => 'Thanks-for-your-order page (Full URI)',
		'checkout_required_fields'          => 'List of required fields in checkout form',
		'back_to_shop_link'                 => 'Back-to-shop-link (Full URI)',
		'custom_field_price_name'           => 'Name of the <em>Price</em> custom field',
		'custom_field_property_required'    => 'Mandatory properties',
		'custom_field_property_1_name'      => 'Name of the <em>Property 1</em> custom field',
		'custom_field_property_2_name'      => 'Name of the <em>Property 2</em> custom field',
		'custom_field_property_3_name'      => 'Name of the <em>Property 3</em> custom field',
		'custom_field_shipping_name'        => 'Name of the <em>Special shipping costs</em> custom field',
		'custom_field_tax_band'             => 'Name of the <em>Tax bands</em> custom field',
		'custom_field_weight'               => 'Name of the <em>Product weight</em> custom field',
		'admin_mail'                        => 'Admin e-mail address (receives the orders)',
		'order_affirmation_mail'            => 'Send affirmation e-mail to buyers',
		'email_mime_type'                   => 'MIME type of the affirmation / admin e-mail',
		'email_body_form'                   => 'Textpattern form(s) for e-mail body layout',
		'use_property_prices'               => 'Use property prices',
		'use_checkout_images'               => 'Use images in checkout form',
		'use_encrypted_paypal_button'       => 'Use an encrypted Paypal button',
		'paypal_prefilled_country'          => 'Prefilled country in Paypal interface',
		'paypal_interface_language'         => 'Paypal interface language',
		'paypal_business_mail'              => 'Email of the Paypal business account',
		'paypal_live_or_sandbox'            => 'Live or sandbox',
		'paypal_certificate_id'             => 'Paypal certificate ID',
		'paypal_certificates_path'          => 'Path to Paypal certificate (absolute)',
		'paypal_public_certificate_name'    => 'Name of the public Paypal certificate',
		'paypal_my_public_certificate_name' => 'Name of your public certificate',
		'paypal_my_private_key_name'        => 'Name of your private key',
		'google_live_or_sandbox'            => 'Live or sandbox',
		'google_merchant_id'                => 'Google merchant ID',
		'google_merchant_key'               => 'Google merchant key',
		'shop_prefs'                        => 'Shop settings',
		'prefs_updated'                     => 'yab_shop settings saved.',
		'tables_delete_error'               => 'Could not delete yab_shop database tables.',
		'tables_delete_success'             => 'yab_shop database tables deleted.',
		'klick_to_update'                   => 'Click to upgrade yab_shop:',
		);

	global $yab_shop_admin_lang;
	$yab_shop_admin_lang = new yab_shop_MLP( 'yab_shop_admin', $_yab_shop_admin_i18n );

	add_privs('yab_shop_prefs', '1');
	register_tab('extensions', 'yab_shop_prefs', yab_shop_admin_lang('shop_prefs'));
	register_callback('yab_shop_prefs', 'yab_shop_prefs');
}

/**
 * Initialise and draw shop prefs
 *
 * @param string $event as $_GET or $_POST
 * @param string $step as $_GET or $_POST
 * @return string echo the admin ui
 */
function yab_shop_prefs($evt, $stp)
{
	$message = '';
	$content = '';
	$available_steps = array(
		'yab_shop_first_install' => false,
		'yab_shop_update'        => false,
		'yab_shop_uninstall'     => false,
		'yab_shop_prefs_save'    => true,
	);

	if (!$stp)
	{
		// check for prefs db-table
		$exists = yab_shop_table_exist('yab_shop_prefs');
		if ($exists === true)
		{
			$content = yab_shop_display_prefs();
		}
		else if ($exists === false)
		{
			$content = yab_shop_draw_instup();
		}
		else
		{
			$content = yab_shop_draw_instup('update');
		}
	}
	else
	{
		if (bouncer($stp, $available_steps))
		{
			$message = $stp();
		}
		else
		{
			$message = array('Cannot run ' . $stp, E_WARNING);
		}
		if (yab_shop_table_exist('yab_shop_prefs') === true)
			$content = yab_shop_display_prefs();
	}
	echo pagetop(yab_shop_admin_lang('shop_prefs'), $message).$content;
}

/**
 * Draw installation/update form
 *
 * @param string $modus Choose between 'install' or 'update'
 * @return string Draw from
 */
function yab_shop_draw_instup($modus = 'install')
{
	if ($modus == 'install')
	{
		$sInput = sInput('yab_shop_first_install');
		$button = gTxt('install');
		$text = 'Click to install yab_shop:';
	}
	else
	{
		$sInput = sInput('yab_shop_update');
		$button = gTxt('update');
		$text = yab_shop_admin_lang('klick_to_update');
	}

	$out = startTable('list');
	$out .= tr(
		tda($text, ' style="vertical-align: middle"').
		tda(
			form(
				fInput('submit', 'submit', $button, 'publish').
				$sInput.
				eInput('yab_shop_prefs')
			)
		)
	);
	$out .= endTable();

	return $out;
}

/**
 * Call installation routine and return message
 *
 * @return string Message
 */
function yab_shop_first_install()
{
	if (yab_shop_install('yab_shop_prefs'))
	{
		$message = 'yab_shop installed';
	}
	else
	{
		$message = 'Could not install yab_shop';
	}

	return $message;
}

/**
 * Check for a given table in DB and version to determine if install/upgrade required
 *
 * @param string Table name without prefix
 * @return int
 */
function yab_shop_table_exist($tbl)
{
	$r = @safe_count($tbl, '1=1');

	$all = yab_shop_get_preflist();
	$expected = 0;
	foreach($all as $grp => $items) {
		foreach ($items as $key=>$item) {
			$expected++;
		}
	}

	if ($r) {
		$current_ver = safe_field('val', 'yab_shop_prefs', "name='yab_shop_version'");
		if ((yab_shop_version() != $current_ver) || ($expected != $r))
		{
			return '-1'; // Upgrade required
		}
		else
		{
			return true; // Everything OK
		}
	}

	// Install required
	return false;
}

/**
 * yab_shop installed version info
 *
 * @return string yab_shop installed version number
 */
function yab_shop_version()
{
	global $plugins_ver;
	return $plugins_ver['yab_shop'];
}

/**
 * Get yab_shop prefs or language and display it
 *
 * @return string
 */
function yab_shop_display_prefs()
{
	// choose step and event
	$submit = sInput('yab_shop_prefs_save').eInput('yab_shop_prefs').hInput('prefs_id', '1');

	$out = '<form method="post" action="index.php">'.startTable('list');

	$rs = safe_rows('*', 'yab_shop_prefs', "type = 1 AND prefs_id = 1 ORDER BY event DESC, position");
	$installed = array();
	foreach($rs as $row) {
		$installed[$row['name']] = $row['val'];
	}

	$all = yab_shop_get_preflist();

	// now make a html table from the database table
	$cur_evt = '';
	foreach ($all as $group => $items)
	{
		if ($group == 'system') continue;

		foreach ($items as $key => $a)
		{
			if ($group != $cur_evt)
			{
				$cur_evt = $group;
				$out .= n.tr(
					n.tdcs(
						hed(yab_shop_admin_lang($group), 2, ' class="pref-heading"')
					, 2)
				);
			}

			$aval = isset($installed[$key]) ? $installed[$key] : '';

//			if ($a['html'] != 'yesnoradio')
				$label = '<label for="'.$key.'">'.yab_shop_admin_lang($key).'</label>';
//			else
//				$label = yab_shop_admin_lang($key);

			if ($a['html'] == 'text_input')
			{
				$size = (isset($a['size'])) ? $a['size'] : 8;
				$out_tr = td(
					yab_shop_pref_func('yab_shop_text_input', $key, $aval, $size)
				);
			}
			elseif ($a['html'] == 'text_area')
			{
				$size = 50;

				$out_tr = td(
					yab_shop_pref_func('yab_shop_text_area', $key, $aval, $size)
				);
			}
			else
			{
				if (is_callable($a['html']))
				{
					$out_tr = td(
						yab_shop_pref_func($a['html'], $key, $aval)
					);
				}
				else
				{
					$out.= n.td($aval);
				}
			}
			$out .= n.tr(
				n.tda($label, ' style="text-align: right; vertical-align: middle;"').
				n.$out_tr
			);
		}
	}

	$out .= n.tr(
		n.tda(
			fInput('submit', 'Submit', gTxt('save_button'), 'publish').
			$submit
		, ' colspan="2" class="noline"')
	).
	n.n.endTable().
	tInput().
	n.n.'</form>';
	return $out;
}

/**
 * Return config values
 *
 * @param string $what
 * @return string | NULL
 */
function yab_shop_config($what)
{
	global $yab_shop_prefs;
	return (isset($yab_shop_prefs[$what])) ? $yab_shop_prefs[$what] : NULL;
}

/**
 * Return public language strings
 *
 * @param string $what Thing to translate
 * @param array  $args Any '{name}' => val replacements to be made
 * @return string Return language if exists, otherwise @param $what
 */
function yab_shop_lang($what, $args=array())
{
	global $yab_shop_public_lang;
	return $yab_shop_public_lang->gTxt( $what, $args );
}

/**
 * Return admin language strings
 *
 * @param string $what Thing to translate
 * @param array  $args Any '{name}' => val replacements to be made
 * @return string Return language if exists, otherwise @param $what
 */
function yab_shop_admin_lang($what, $args=array())
{
	global $yab_shop_admin_lang;
	return $yab_shop_admin_lang->gTxt( $what, $args );
}

/**
 * Return prefs array from database
 *
 * @return array Prefs
 */
function yab_shop_get_prefs()
{
	$r = @safe_rows('name, val', 'yab_shop_prefs', 'prefs_id=1');

	if ($r)
	{
		foreach ($r as $a)
		{
			$out[$a['name']] = $a['val'];
		}
		return $out;
	}
	return array();
}

/**
 * Return language array from database, depending of a
 * given event (for public or admin ui) and the choosen language
 *
 * @param string $lang_event
 * @return array
 */
function yab_shop_get_lang($lang_event)
{
	// does choosen language exists in yab_shop_lang
	$lang_count = safe_count('yab_shop_lang', "lang='".doSlash(LANG)."'");
	if ($lang_count)
		$lang_code = LANG;
	else
		$lang_code = 'en-gb'; // fallback language

	$r = safe_rows_start('name, val', 'yab_shop_lang',"lang='".doSlash($lang_code)."' AND event='".doSlash($lang_event)."'");

	if ($r)
	{
		while ($a = nextRow($r))
		{
			$out[$a['name']] = $a['val'];
		}
		return $out;
	}
	return array();
}

/**
 * Return the raw array of countries
 *
 * @return array
 */
function yab_shop_get_country_list() {
	return array(
		"AF" => "Afghanistan",
		"AL" => "Albania",
		"DZ" => "Algeria",
		"AS" => "American Samoa",
		"AD" => "Andorra",
		"AO" => "Angola",
		"AI" => "Anguilla",
		"AQ" => "Antarctica",
		"AG" => "Antigua and Barbuda",
		"AR" => "Argentina",
		"AM" => "Armenia",
		"AW" => "Aruba",
		"AU" => "Australia",
		"AT" => "Austria",
		"AZ" => "Azerbaijan",
		"BS" => "Bahamas",
		"BH" => "Bahrain",
		"BD" => "Bangladesh",
		"BB" => "Barbados",
		"BY" => "Belarus",
		"BE" => "Belgium",
		"BZ" => "Belize",
		"BJ" => "Benin",
		"BM" => "Bermuda",
		"BT" => "Bhutan",
		"BO" => "Bolivia",
		"BA" => "Bosnia and Herzegovina",
		"BW" => "Botswana",
		"BV" => "Bouvet Island",
		"BR" => "Brazil",
		"IO" => "British Indian Ocean Territory",
		"BN" => "Brunei Darussalam",
		"BG" => "Bulgaria",
		"BF" => "Burkina Faso",
		"BI" => "Burundi",
		"KH" => "Cambodia",
		"CM" => "Cameroon",
		"CA" => "Canada",
		"CV" => "Cape Verde",
		"KY" => "Cayman Islands",
		"CF" => "Central African Republic",
		"TD" => "Chad",
		"CL" => "Chile",
		"CN" => "China",
		"CX" => "Christmas Island",
		"CC" => "Cocos (Keeling) Islands",
		"CO" => "Colombia",
		"KM" => "Comoros",
		"CG" => "Congo",
		"CD" => "Congo, the Democratic Republic of the",
		"CK" => "Cook Islands",
		"CR" => "Costa Rica",
		"CI" => "Cote D'Ivoire",
		"HR" => "Croatia",
		"CU" => "Cuba",
		"CY" => "Cyprus",
		"CZ" => "Czech Republic",
		"DK" => "Denmark",
		"DJ" => "Djibouti",
		"DM" => "Dominica",
		"DO" => "Dominican Republic",
		"EC" => "Ecuador",
		"EG" => "Egypt",
		"SV" => "El Salvador",
		"GQ" => "Equatorial Guinea",
		"ER" => "Eritrea",
		"EE" => "Estonia",
		"ET" => "Ethiopia",
		"FK" => "Falkland Islands (Malvinas)",
		"FO" => "Faroe Islands",
		"FJ" => "Fiji",
		"FI" => "Finland",
		"FR" => "France",
		"GF" => "French Guiana",
		"PF" => "French Polynesia",
		"TF" => "French Southern Territories",
		"GA" => "Gabon",
		"GM" => "Gambia",
		"GE" => "Georgia",
		"DE" => "Germany",
		"GH" => "Ghana",
		"GI" => "Gibraltar",
		"GR" => "Greece",
		"GL" => "Greenland",
		"GD" => "Grenada",
		"GP" => "Guadeloupe",
		"GU" => "Guam",
		"GT" => "Guatemala",
		"GN" => "Guinea",
		"GW" => "Guinea-Bissau",
		"GY" => "Guyana",
		"HT" => "Haiti",
		"HM" => "Heard Island and Mcdonald Islands",
		"VA" => "Holy See (Vatican City State)",
		"HN" => "Honduras",
		"HK" => "Hong Kong",
		"HU" => "Hungary",
		"IS" => "Iceland",
		"IN" => "India",
		"ID" => "Indonesia",
		"IR" => "Iran, Islamic Republic of",
		"IQ" => "Iraq",
		"IE" => "Ireland",
		"IM" => "Isle Of Man",
		"IL" => "Israel",
		"IT" => "Italy",
		"JM" => "Jamaica",
		"JP" => "Japan",
		"JE" => "Jersey",
		"JO" => "Jordan",
		"KZ" => "Kazakhstan",
		"KE" => "Kenya",
		"KI" => "Kiribati",
		"KP" => "Korea, Democratic People's Republic of",
		"KR" => "Korea, Republic of",
		"KW" => "Kuwait",
		"KG" => "Kyrgyzstan",
		"LA" => "Lao People's Democratic Republic",
		"LV" => "Latvia",
		"LB" => "Lebanon",
		"LS" => "Lesotho",
		"LR" => "Liberia",
		"LY" => "Libyan Arab Jamahiriya",
		"LI" => "Liechtenstein",
		"LT" => "Lithuania",
		"LU" => "Luxembourg",
		"MO" => "Macao",
		"MK" => "Macedonia, the Former Yugoslav Republic of",
		"MG" => "Madagascar",
		"MW" => "Malawi",
		"MY" => "Malaysia",
		"MV" => "Maldives",
		"ML" => "Mali",
		"MT" => "Malta",
		"MH" => "Marshall Islands",
		"MQ" => "Martinique",
		"MR" => "Mauritania",
		"MU" => "Mauritius",
		"YT" => "Mayotte",
		"MX" => "Mexico",
		"FM" => "Micronesia, Federated States of",
		"MD" => "Moldova, Republic of",
		"MC" => "Monaco",
		"MN" => "Mongolia",
		"ME" => "Montenegro",
		"MS" => "Montserrat",
		"MA" => "Morocco",
		"MZ" => "Mozambique",
		"MM" => "Myanmar",
		"NA" => "Namibia",
		"NR" => "Nauru",
		"NP" => "Nepal",
		"NL" => "Netherlands",
		"AN" => "Netherlands Antilles",
		"NC" => "New Caledonia",
		"NZ" => "New Zealand",
		"NI" => "Nicaragua",
		"NE" => "Niger",
		"NG" => "Nigeria",
		"NU" => "Niue",
		"NF" => "Norfolk Island",
		"MP" => "Northern Mariana Islands",
		"NO" => "Norway",
		"OM" => "Oman",
		"PK" => "Pakistan",
		"PW" => "Palau",
		"PS" => "Palestinian Territory, Occupied",
		"PA" => "Panama",
		"PG" => "Papua New Guinea",
		"PY" => "Paraguay",
		"PE" => "Peru",
		"PH" => "Philippines",
		"PN" => "Pitcairn",
		"PL" => "Poland",
		"PT" => "Portugal",
		"PR" => "Puerto Rico",
		"QA" => "Qatar",
		"RE" => "Reunion",
		"RO" => "Romania",
		"RU" => "Russian Federation",
		"RW" => "Rwanda",
		"BL" => "Saint Barthélemy",
		"SH" => "Saint Helena, Ascension And Tristan Da Cunha",
		"KN" => "Saint Kitts and Nevis",
		"LC" => "Saint Lucia",
		"MF" => "Saint Martin",
		"PM" => "Saint Pierre and Miquelon",
		"VC" => "Saint Vincent and the Grenadines",
		"WS" => "Samoa",
		"SM" => "San Marino",
		"ST" => "Sao Tome and Principe",
		"SA" => "Saudi Arabia",
		"SN" => "Senegal",
		"CS" => "Serbia and Montenegro",
		"SC" => "Seychelles",
		"SL" => "Sierra Leone",
		"SG" => "Singapore",
		"SK" => "Slovakia",
		"SI" => "Slovenia",
		"SB" => "Solomon Islands",
		"SO" => "Somalia",
		"ZA" => "South Africa",
		"GS" => "South Georgia and the South Sandwich Islands",
		"ES" => "Spain",
		"LK" => "Sri Lanka",
		"SD" => "Sudan",
		"SR" => "Suriname",
		"SJ" => "Svalbard and Jan Mayen",
		"SZ" => "Swaziland",
		"SE" => "Sweden",
		"CH" => "Switzerland",
		"SY" => "Syrian Arab Republic",
		"TW" => "Taiwan, Province of China",
		"TJ" => "Tajikistan",
		"TZ" => "Tanzania, United Republic of",
		"TH" => "Thailand",
		"TL" => "Timor-Leste",
		"TG" => "Togo",
		"TK" => "Tokelau",
		"TO" => "Tonga",
		"TT" => "Trinidad and Tobago",
		"TN" => "Tunisia",
		"TR" => "Turkey",
		"TM" => "Turkmenistan",
		"TC" => "Turks and Caicos Islands",
		"TV" => "Tuvalu",
		"UG" => "Uganda",
		"UA" => "Ukraine",
		"AE" => "United Arab Emirates",
		"GB" => "United Kingdom",
		"US" => "United States",
		"UM" => "United States Minor Outlying Islands",
		"UY" => "Uruguay",
		"UZ" => "Uzbekistan",
		"VU" => "Vanuatu",
		"VE" => "Venezuela, Bolivarian Republic Of",
		"VN" => "Viet Nam",
		"VG" => "Virgin Islands, British",
		"VI" => "Virgin Islands, U.S.",
		"WF" => "Wallis and Futuna",
		"EH" => "Western Sahara",
		"YE" => "Yemen",
		"ZM" => "Zambia",
		"ZW" => "Zimbabwe",
		"AX" => "Åland Islands",
	);
}

/**
 * Return list of countries
 *
 * @param string $name Select name
 * @param string $value Chosen default
 * @param boolean $blank Add a blank entry at the start of the list
 * @param string $onchange Set to 1 to auto-submit, or an onchange= string
 * @param string $html_id Select HTML ID
 * @return array
 */
function yab_shop_get_countries($name, $value='', $blank=true, $onchange='', $html_id='')
{
	$clist = yab_shop_get_country_list();
	$selist = array();
	$selected = false;
	foreach ($clist as $cc => $cname) {
		if ($value == $cc) $selected = true;
		$selist[] = n.t.t.'<option value="'.$cc.'"'.(($value == $cc) ? ' selected="selected"' : '').'>'.$cname.'</option>';
	}

	return '<select class="list" name="'.$name.'"'
		.($html_id ? ' id="'.$html_id.'"' : '')
		.($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange)
		.'>'
		.($blank ? n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '')
		.join('', $selist)
		.'</select>';
}

/**
 * Return call_user_func()
 *
 * @param string $func
 * @param string $name
 * @param string $val
 * @param integer $size
 * @return mixed
 */
function yab_shop_pref_func($func, $name, $val, $size = '')
{
	if (is_callable('pref_'.$func))
		$func = 'pref_'.$func;
	else
		$func = $func;

	return call_user_func($func, $name, $val, $size);
}

/**
 * Create text input field
 *
 * @param string  $name
 * @param string  $val
 * @param integer $size
 * @param string  $type
 * @return string HTML text input
 */
function yab_shop_text_input($name, $val, $size = '', $type = 'text')
{
	return fInput($type, $name, $val, 'edit', '', '', $size, '', $name);
}

/**
 * Create textarea field
 *
 * @param string $name
 * @param string $val
 * @param integer $size
 * @return string HTML textarea
 */
function yab_shop_text_area($name, $val, $size = '')
{
	return '<textarea name="' . $name . '" cols="' . $size . '" rows="4">' . $val . '</textarea>';
}

/**
 * Create property required checkbox set
 *
 * @param string $name
 * @param string $val
 * @param integer $size
 * @return string HTML textarea
 */
function yab_shop_property_required($name, $val, $size = '')
{
	$vals = do_list($val);
	$items = array('property_1' => '1', 'property_2' => '2', 'property_3' => '3');
	$lclout = array();
	foreach($items as $cb => $item) {
		$checked = in_array($cb, $vals);
		$lclout[] = checkbox($name.'[]', $cb, $checked). $item;
	}

	return join(n, $lclout);
}

/**
 * Save language setting in admin ui
 *
 * @return string Message for pagetop()
 */
function yab_shop_lang_save()
{
	$post = doSlash(stripPost());
	$lang_code = LANG;
	$prefnames = safe_column("name", "yab_shop_lang", "lang = '".doSlash(LANG)."' AND event = 'lang_public'");
	if (!$prefnames)
	{
		$prefnames = safe_column("name", "yab_shop_lang", "lang = 'en-gb' AND event = 'lang_public'");
		$lang_code = 'en-gb';
	}

	foreach($prefnames as $prefname)
	{
		if (isset($post[$prefname]))
		{
			safe_update(
				"yab_shop_lang",
				"val = '".$post[$prefname]."'",
				"name = '".doSlash($prefname)."' AND lang = '".doSlash($lang_code)."'"
			);
		}
  }
	return yab_shop_admin_lang('lang_updated');
}

/**
 * Save prefs setting in admin ui
 *
 * @return string Message for pagetop()
 */
function yab_shop_prefs_save()
{
	$prefnames = safe_column("name", "yab_shop_prefs", "prefs_id = 1 AND type = 1");
	foreach($prefnames as $prefname)
	{
		$val = ps($prefname);
		$val = (is_array($val)) ? join(',', $val) : $val;

		safe_update(
			"yab_shop_prefs",
			"val = '".doSlash($val)."'",
			"name = '".doSlash($prefname)."' and prefs_id = 1"
		);
  }
	return yab_shop_admin_lang('prefs_updated');
}

function yab_shop_get_preflist()
{
	$yab_shop_version = yab_shop_version();
	$yab_shop_preflist = array(
		'shop_common_prefs' => array(
			'currency'                          => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 10, 'default'  => 'EUR'),
			'promocode'                         => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 20, 'size' => '50', 'default'  => ''),
			'promo_discount_percent'            => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 30, 'size' => '30', 'default'  => '10'),
			'order_affirmation_mail'            => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 40, 'default'  => '1'),
			'admin_mail'                        => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 50, 'size' => '30', 'default'  => 'admin@domain.tld'),
			'email_mime_type'                   => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 60, 'size' => '15', 'default'  => 'text/plain'),
			'email_body_form'                   => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 70, 'size' => '30', 'default'  => ''),
		),
		'product_prefs' => array(
			'custom_field_price_name'           => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 10, 'size' => '15', 'default'  => 'Price'),
			'custom_field_weight'               => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 20, 'size' => '15', 'default'  => ''),
			'custom_field_tax_band'             => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 30, 'size' => '15', 'default'  => ''),
			'custom_field_shipping_name'        => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 40, 'size' => '15', 'default'  => ''),
			'use_property_prices'               => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 50, 'default'  => '0'),
			'custom_field_property_1_name'      => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 60, 'size' => '15', 'default'  => 'Size'),
			'custom_field_property_2_name'      => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 70, 'size' => '15', 'default'  => 'Colour'),
			'custom_field_property_3_name'      => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 80, 'size' => '15', 'default'  => 'Variant'),
			'custom_field_property_required'    => array('html' => 'yab_shop_property_required', 'type' => PREF_ADVANCED, 'position' => 90, 'default'  => ''),
		),
		'prefs_payment' => array(
			'payment_method_acc'                => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 10, 'default'  => '1'),
			'payment_method_pod'                => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 20, 'default'  => '1'),
			'payment_method_pre'                => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 30, 'default'  => '1'),
			'payment_method_paypal'             => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 40, 'default'  => '0'),
			'payment_method_google'             => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 50, 'default'  => '0'),
		),
		'prefs_checkout' => array(
			'checkout_section_name'             => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 10, 'size' => '15', 'default'  => 'checkout'),
			'checkout_required_fields'          => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 20, 'size' => '50', 'default'  => 'firstname, surname, street, city, state, postal, country'),
			'checkout_thanks_site'              => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 30, 'size' => '50', 'default'  => doSlash(hu).'shop/thank-you'),
			'back_to_shop_link'                 => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 40, 'size' => '50', 'default'  => doSlash(hu).'shop'),
			'tou_link'                          => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 50, 'size' => '50', 'default'  => doSlash(hu).'terms'),
			'using_tou_checkbox'                => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 60, 'default'  => '1'),
			'use_checkout_images'               => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 70, 'default'  => '0'),
			'using_checkout_state'              => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 80, 'default'  => '0'),
			'using_checkout_country'            => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 90, 'default'  => '0'),
			'tax_inclusive'                     => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 100, 'default'  => '1'),
			'tax_rate'                          => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 110, 'size' => '15', 'default'  => '20'),
			'free_shipping'                     => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 130, 'default'  => '20.00'),
			'shipping_via'                      => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 140, 'default'  => 'UPS'),
			'shipping_costs'                    => array('html' => 'text_area', 'type'  => PREF_ADVANCED, 'position' => 120, 'default'  => '7.50'),
		),
		'paypal_prefs' => array(
			'use_encrypted_paypal_button'       => array('html' => 'yesnoradio', 'type' => PREF_ADVANCED, 'position' => 10, 'default'  => '1'),
			'paypal_prefilled_country'          => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 20, 'default'  => 'en'),
			'paypal_interface_language'         => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 30, 'default'  => 'en'),
			'paypal_business_mail'              => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 40, 'size' => '30', 'default'  => 'admin@domain.tld'),
			'paypal_live_or_sandbox'            => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 50, 'size' => '10', 'default'  => 'sandbox'),
			'paypal_certificate_id'             => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 60, 'size' => '40', 'default'  => 'CERTIFICATEID'),
			'paypal_certificates_path'          => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 70, 'size' => '40', 'default'  => '/path/to/your/certificates'),
			'paypal_public_certificate_name'    => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 80, 'size' => '40', 'default'  => 'paypal_cert.pem'),
			'paypal_my_public_certificate_name' => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 90, 'size' => '40', 'default'  => 'my-public-certificate.pem'),
			'paypal_my_private_key_name'        => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 100, 'size' => '40', 'default'  => 'my-private-key.pem'),
		),
		'google_prefs' => array(
			'google_live_or_sandbox'            => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 10, 'size' => '10', 'default'  => 'sandbox'),
			'google_merchant_id'                => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 20, 'size' => '40', 'default'  => 'your-merchant-id'),
			'google_merchant_key'               => array('html' => 'text_input', 'type' => PREF_ADVANCED, 'position' => 30, 'size' => '40', 'default'  => 'your-merchant-key'),
		),
		'system' => array(
			'yab_shop_version'                  => array('html' => 'text_input', 'type' => PREF_HIDDEN, 'position' => 1, 'default'  => doSlash($yab_shop_version)),
		),
	);

	return $yab_shop_preflist;
}

/**
 * Upgrade yab_shop database tables
 *
 * @return string Message for pagetop()
 */
function yab_shop_update()
{
	// Upgrade yab_shop_prefs val field from VARCHAR to TEXT
	$ret = getRow("SHOW COLUMNS FROM ".PFX."yab_shop_prefs WHERE field='val'");

	if ($ret['Type'] !== 'text')
	{
		safe_alter('yab_shop_prefs', "CHANGE `val` `val` TEXT NOT NULL DEFAULT ''");
	}

	// Add new pref items
	$new_prefs = array(
		'custom_field_weight'            => '',
		'custom_field_tax_band'          => '',
		'custom_field_property_required' => '',
		'checkout_required_fields'       => 'firstname, surname, street, city, state, postal, country',
		'using_checkout_country'         => '0',
		'email_mime_type'                => 'text/plain',
		'email_body_form'                => '',
		'tou_link'                       => hu . 'terms',
	);

	$common_atts = array(
		'prefs_id' => '1',
		'type'     => '1',
		'event'    => 'shop_common_prefs',
		'html'     => 'text_input',
		'position' => '50',
	);

	$entries = safe_column('name', 'yab_shop_prefs', '1=1');
	foreach ($new_prefs as $prefname => $prefval)
	{
		if (!in_array($prefname, $entries))
		{
			$items = array('name' => $prefname, 'val' => $prefval) + $common_atts;
			$bits = array();
			foreach ($items as $idx => $val)
			{
				$bits[] = $idx . '=' . doQuote(doSlash($val));
			}
			$qry = join(', ', $bits);
			safe_insert('yab_shop_prefs', $qry);
		}
	}

	// Make sure the alterations (input type, etc) are done to the existing prefs
	$yab_shop_preflist = yab_shop_get_preflist();
	$update_sql = array();
	foreach ($yab_shop_preflist as $preftype => $items) {
		foreach ($items as $pref_item => $fld) {
			$update_sql[] = "UPDATE `".PFX."yab_shop_prefs` SET event = '" . doSlash($preftype) . "', html = '" . doSlash($fld['html']) . "', position = '" . doSlash($fld['position']) . "' WHERE name = '" . doSlash($pref_item) . "'";
		}
	}

	foreach ($update_sql as $query)
	{
		$result = safe_query($query);
	}

	// Notify the plugin it's been upgraded
	safe_upsert('yab_shop_prefs', "val='" . doSlash(yab_shop_version()) . "'", "name='yab_shop_version'");
}

/**
 * Uninstall yab_shop database tables
 *
 * @return string Message for pagetop()
 */
function yab_shop_uninstall()
{
	$queries = array();

	if (yab_shop_table_exist('yab_shop_prefs') === true)
	{
		$queries[] = 'DROP TABLE `'.PFX.'yab_shop_prefs`';
	}

	foreach ($queries as $query)
	{
		$result = safe_query($query);
		if (!$result)
			return yab_shop_admin_lang('tables_delete_error');
	}
	return yab_shop_admin_lang('tables_delete_success');
}

/**
 * Installation routine
 *
 * @return boolean
 */
function yab_shop_install($table)
{
	global $txpcfg;
	$version = mysql_get_server_info();
	$dbcharset = $txpcfg['dbcharset'];

	if (intval($version[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$version))
		$tabletype = " ENGINE=MyISAM ";
	else
		$tabletype = " TYPE=MyISAM ";

	if (isset($dbcharset) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)))
	{
		$tabletype .= " CHARACTER SET = $dbcharset ";
		if (isset($dbcollate))
			$tabletype .= " COLLATE $dbcollate ";

		mysql_query("SET NAMES ".$dbcharset);
	}

	$create_sql = array();

	switch ($table)
	{
		case 'yab_shop_prefs':
			$create_sql[] = "CREATE TABLE IF NOT EXISTS `".PFX."yab_shop_prefs` (
				`prefs_id` int(11) NOT NULL,
				`name` varchar(255) NOT NULL,
				`val` text NOT NULL default '',
				`type` smallint(5) unsigned NOT NULL default '1',
				`event` varchar(18) NOT NULL default 'shop_common_prefs',
				`html` varchar(64) NOT NULL default 'text_input',
				`position` smallint(5) unsigned NOT NULL default '0',
				UNIQUE KEY `prefs_idx` (`prefs_id`,`name`),
				KEY `name` (`name`)
			) $tabletype ";

			$yab_shop_preflist = yab_shop_get_preflist();

			foreach ($yab_shop_preflist as $pref_event => $items) {
				foreach ($items as $pref_item => $fld) {
					$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES (1, '".$pref_item."', '".$fld['default']."', '".$fld['type']."', '".$pref_event."', '".$fld['html']."', '".$fld['position']."') ON DUPLICATE KEY UPDATE prefs_id=prefs_id";
				}
			}

			break;
		default:
			break;
	}

	foreach ($create_sql as $query)
	{
		$result = safe_query($query);
		if (!$result)
			return false;
	}
	return true;
}