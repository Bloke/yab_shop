<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'yab_shop_core';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.9.1';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'Shopping Cart Plugin (Core)';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * This plugin is released under the GNU General Public License Version 2 and above
 * Version 2: http://www.gnu.org/licenses/gpl-2.0.html
 * Version 3: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * v0.9.0 changes:
 *  -> Added callbacks:
 *     -> yab_shop_cart_add
 *     -> yab_shop_cart_item_update
 *     -> yab_shop_cart_remove
 *     -> yab_shop_on_checkout
 *     -> yab_shop_on_checkout (steps: paypal; google; default,success; default,failure; default,partial; default,no_affirmation)
 *     -> yab_shop_on_checkout_empty_cart
 *  -> Added pluggable_ui callbacks:
 *     -> event: yab_shop, steps:
 *          checkout_cart_preamble
 *          checkout_cart_postamble
 *          checkout_form_preamble
 *          checkout_form_postamble
 *  -> Added multiple promo codes
 *  -> Added multiple tax bands
 *  -> Added shipping by weight
 *  -> Added country select list option
 *  -> Permitted checkout fields to be optional
 *  -> Permitted customisable body admin/affirmation e-mail
 *  -> Affirmation e-mail no longer triggers required e-mail field (if missing, e-mailing is skipped)
 *  -> Fixed yab_shop_cart_quantity for standalone use
 *  -> Fixed 'currency not double' warning
 *  -> Fixed item_number_N being passed in encrypted paypal buttons
 *  -> Swapped merchant_return_link for return IPN var
 *  -> Added RRP support for price custom field. Specify as: price|RRP
 *  -> Added: yab_shop_price attributes 'type' (price/rrp/saving) and 'raw' (to retrieve value without currency prefix)
 * TODO:
 *  -> Remove Google Checkout
 *  -> Add interface for configuring shipping weights
 *  -> Option to hide the optional checkout fields completely
 *  -> Revert the shop_admin strings to their defaults before publish
 */

function yab_shop_cart($atts, $thing = null)
{
	extract(
		lAtts(
			array(
				'output'	=> 'cart'
			),$atts
		)
	);

	global $thisarticle, $prefs;
	$articleid = $thisarticle['thisid'];
	$section = $thisarticle['section'];

	yab_shop_start_session();

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new wfCart();

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

		$product_prices = do_list($product['price'], '|');
		$product_price = $product_prices[0];
		$product_rrp = (isset($product_prices[1])) ? $product_prices[1] : '';
		$product_price = yab_shop_replace_commas($product_price);
		$product_rrp = yab_shop_replace_commas($product_rrp);
		$product_price_saving = ($product_rrp) ? $product_rrp - $product_price : '';

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

			if (!empty($product_ps_1[1]))
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
			if (!empty($product_ps_2[1]))
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
			if (!empty($product_ps_3[1]))
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

		$product_tax_bands = do_list(yab_shop_replace_commas(yab_shop_config('tax_rate')), '|');
		$product_tax = $product_tax_bands[0];
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
			$cart->add_item($product_id, $articleid, $pqty, $product_price, $product_rrp, $product_price_saving, $product['name'], $product_property_1, $product_property_2, $product_property_3, $product_spec_shipping, $product_tax, $product_weight);
			callback_event( 'yab_shop_cart_add', '', 0, $cart, $callback_params );
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
		$cart = new wfCart();

	return yab_shop_build_cart($cart);
}

function yab_shop_cart_subtotal($atts)
{
	extract(
		lAtts(
			array(
				'showalways'	=> '1',
				'break'				=> br,
				'label'				=> gTxt('yab_shop_sub_total'),
				'wraptag'			=> '',
				'class'				=> '',
				'sep'					=> ': ',
			),$atts
		)
	);

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new wfCart();

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
	extract(
		lAtts(
			array(
				'output'	=> 'single',
				'showalways'	=> '1',
				'break'				=> br,
				'label'				=> gTxt('yab_shop_quantity'),
				'wraptag'			=> '',
				'class'				=> '',
				'sep'					=> ': ',
			),$atts
		)
	);

	yab_shop_start_session();

	if ($label)
		$label = htmlspecialchars($label.$sep);

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new wfCart();

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
	extract(
		lAtts(
			array(
				'add'		=> gTxt('yab_shop_cart_message_add'),
				'edit'	=> gTxt('yab_shop_cart_message_edit'),
				'del'		=> gTxt('yab_shop_cart_message_del'),
				'break'				=> br,
				'wraptag'			=> '',
				'class'				=> ''
			),$atts
		)
	);

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
	extract(
		lAtts(
			array(
				'label'				=> gTxt('yab_shop_to_checkout'),
				'break'				=> br,
				'showalways'	=> '1',
				'wraptag'			=> '',
				'class'				=> ''
			),$atts
		)
	);

	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new wfCart();

	$url = pagelinkurl(array('s' => yab_shop_config('checkout_section_name')));
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

	extract(
		lAtts(
			array(
				'summary'	=> gTxt('yab_shop_checkout_summary'),
			),$atts
		)
	);

	yab_shop_start_session();
	$cart =& $_SESSION[ yab_shop_cartname() ];
	if (!is_object($cart))
		$cart = new wfCart();

	yab_promocode();

	$checkout_err = false;

	if ($cart->itemcount > 0)
	{
		$affirmation = yab_shop_config('order_affirmation_mail');
		$to_shop = graf(tag(gTxt('yab_shop_checkout_history_back'), 'a', ' href="'.yab_shop_config('back_to_shop_link').'"'), ' class="history-back"');
		$checkout_display = pluggable_ui('yab_shop', 'checkout_cart_preamble', '', $cart, $yab_shop_prefs);
		$checkout_display .= yab_shop_build_checkout_table($cart, $summary);

		$block_action = callback_event( 'yab_shop_on_checkout', '', 1, $cart, $yab_shop_prefs );
		if( '' !== $block_action )
			return $checkout_display.$block_action;	# The returned string will be displayed in the UI.

		$checkout_display .= yab_build_promo_input($cart);
		$checkout_display .= pluggable_ui('yab_shop', 'checkout_cart_postamble', '', $cart, $yab_shop_prefs);
		$checkout_message = graf(gTxt('yab_shop_checkout_required_field_notice'), ' class="yab-shop-notice"');
		$checkout_form = yab_shop_build_checkout_form();

		// Verify the shipping method is appropriate
		$ship_data = yab_shop_available_shipping_options();
		if (!$ship_data['method_available']) {
			$checkout_err = true;
			$checkout_message = graf(gTxt('yab_shop_checkout_shipping_unavailable'), ' class="yab-shop-notice"');
			$checkout_form = '';
		}

		// Covers eventuality that 'order' input type is an image or a <button>
		$is_order = ( (ps('order') != '') || (ps('order_x') != '') );

		if (!$checkout_err) {
			if ($is_order)
			{
				$ps_order = array();
				$ps_order = yab_shop_clean_input($_POST);
				$checkout_message = graf(gTxt('yab_shop_checkout_mail_field_error'), ' class="yab-shop-required-notice" id="yab-shop-checkout-anchor"');
	
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
						case gTxt('yab_shop_checkout_payment_paypal'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							if (yab_shop_config('use_encrypted_paypal_button') == '1')
								$checkout_form = yab_shop_build_paypal_encrypted_form($cart);
							else
								$checkout_form = yab_shop_build_paypal_form($cart);

							callback_event( 'yab_shop_on_checkout', 'paypal', 0, $cart );
							$cart->empty_cart();
							break;
						case gTxt('yab_shop_checkout_payment_google'):
							$checkout_display = yab_shop_build_checkout_table($cart, $summary, $no_change = '1');
							$checkout_message = '';
							$checkout_form = yab_shop_build_google_form($cart);
							callback_event( 'yab_shop_on_checkout', 'google', 0, $cart );
							$cart->empty_cart();
							break;
						default:
							$checkout_message = graf(gTxt('yab_shop_checkout_mail_error'), ' class="yab-shop-message"');
							if (yab_shop_shop_mail(yab_shop_config('admin_mail'), gTxt('yab_shop_admin_mail_subject'), yab_shop_build_mail_body($cart, $ps_order)))
							{
								if ($affirmation == '1') {
									$checkout_message = graf(gTxt('yab_shop_checkout_mail_affirmation_error'), ' class="yab-shop-message"');
									if (isset($ps_order['email']) and $ps_order['email'] != '') {
										if (yab_shop_shop_mail($ps_order['email'], gTxt('yab_shop_affirmation_mail_subject'), yab_shop_build_mail_body($cart, $ps_order, '1')))
										{
											yab_shop_redirect(yab_shop_config('checkout_thanks_site'));
											$checkout_message = graf(gTxt('yab_shop_checkout_mail_affirmation_success'), ' class="yab-shop-message"').$to_shop;
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
									$checkout_message = graf(gTxt('yab_shop_checkout_mail_success'), ' class="yab-shop-message"').$to_shop;
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
			$checkout_display = graf(gTxt('yab_shop_paypal_return_message'), ' class="yab-shop-message"').graf(tag(gTxt('yab_shop_checkout_history_back'), 'a', ' href="'.yab_shop_config('back_to_shop_link').'"'), ' class="history-back"');
		}
		else
		  $checkout_display = callback_event( 'yab_shop_on_checkout_empty_cart' );

		if( '' === $checkout_display )
			$checkout_display = graf(gTxt('yab_shop_empty_cart'), ' class="yab-empty"');

		return $checkout_display;
	}
}

function yab_shop_add( $atts )
{
	extract(
		lAtts(
			array(
				'hide_options'	=> '',
				'options_hint_class' => 'yab_shop_option_hint',
				'options_hint_wraptag' => 'p',
				'options_hint' => gTxt('yab_shop_has_options_hint'),
			),$atts
		)
	);

	global $thisarticle, $is_article_list;

	$hide_options = ((int)$hide_options != 0);

	$id = $thisarticle['thisid'];
	$property_1_name = yab_shop_config('custom_field_property_1_name');
	$property_2_name = yab_shop_config('custom_field_property_2_name');
	$property_3_name = yab_shop_config('custom_field_property_3_name');
	$hinput = '';
	$purl = permlinkurl_id($id);
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
	  yab_shop_build_custom_select_tag($property_1_name, gTxt('yab_shop_custom_field_property_1'), $hide_options).
		yab_shop_build_custom_select_tag($property_2_name, gTxt('yab_shop_custom_field_property_2'), $hide_options).
		yab_shop_build_custom_select_tag($property_3_name, gTxt('yab_shop_custom_field_property_3'), $hide_options);

	$add_form = tag(
		$hinput.
		$options.
		graf(
			fInput('text','qty','1','','','','1').
			fInput('submit','add',gTxt('yab_shop_add_to_cart'),'submit'),
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
	extract(
		lAtts(
			array(
				'type'    => 'price', // price, rrp, saving
				'raw'     => '0', // Whether to get the raw price minus currency symbol
				'wraptag' => 'span',
				'class'   => 'yab-shop-price'
			),$atts
		)
	);

	global $thisarticle;
	$id = $thisarticle['thisid'];

	$custom_field = yab_shop_config('custom_field_price_name');
	$out = yab_shop_custom_field(array('name' => $custom_field, 'type' => $type, 'raw' => $raw));
	$out = tag($out, $wraptag, ' id="yab-shop-price-'.$id.'" class="'.$class.'"');

	return $out;
}

function yab_shop_show_config($atts)
{
	extract(
		lAtts(
			array(
				'name'	=> ''
			),$atts
		)
	);

	$config_value = yab_shop_config($name);

	if ($config_value)
		return $config_value;

	return 'No config value with this name available.';
}

function yab_shop_custom_field($atts)
{
	global $thisarticle, $prefs;
	assert_article();

	extract(
		lAtts(
			array(
				'name'    => @$prefs['custom_1_set'],
            'type'    => 'price',
            'raw'     => '0',
				'default' => '',
				'hide'    => false,
			),$atts
		)
	);

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
			$out = yab_shop_type_select_custom($out, $name, $hide);
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
			$notice .= tag(gTxt('yab_shop_checkout_'.preg_replace('/\|r$/', '', $key).''), 'li');
	}

	if (isset($ps_order['email']) and $ps_order['email'] != '') {
		if (!is_valid_email($ps_order['email'])) {
			$notice .= tag(gTxt('yab_shop_checkout_mail_email_error'), 'li');
		}
	}

	return $notice;
}

function yab_shop_required_fields() {
	// Permitted fields that can be optional/required
	$opt_fields = do_list('firstname, surname, street, city, state, postal, country, email');

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
	$message = gTxt('yab_shop_checkout_paypal_no_forward');
	$message2 = gTxt('yab_shop_checkout_paypal_forward');
	$business_email = yab_shop_config('paypal_business_mail');
	$country = yab_shop_return_input('country'.$req_matrix['country']['mod']);
	if (!$country) {
		$country = yab_shop_config('paypal_prefilled_country');
	}
	$lc = yab_shop_config('paypal_interface_language');
	$thanks = yab_shop_config('checkout_thanks_site');
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
		if (!empty($item['property_1']))
		{
			$properties .=	hInput('on0_'.$i, gTxt('yab_shop_custom_field_property_1')).n.
											hInput('os0_'.$i, $item['property_1']).n;
		}
		if (!empty($item['property_2']))
		{
			if (!empty($item['property_3']))
			{
				$properties .=	hInput('on1_'.$i, gTxt('yab_shop_custom_field_property_2').'/'.gTxt('yab_shop_custom_field_property_3')).n.
												hInput('os1_'.$i, $item['property_2'].'/'.$item['property_3']).n;
			}
			else
			{
				$properties .=	hInput('on1_'.$i, gTxt('yab_shop_custom_field_property_2')).n.
												hInput('os1_'.$i, $item['property_2']).n;
			}
		}
		else
		{
			if (!empty($item['property_3']))
			{
				$properties .=	hInput('on1_'.$i, gTxt('yab_shop_custom_field_property_3')).n.
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
		fInput('submit','paypal', gTxt('yab_shop_checkout_paypal_button'), 'submit', '', '', '', '', 'yabpaypalsubmit').n,'form', ' method="post" action="'.$action.'" id="yab-paypal-form"'
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
	$message = gTxt('yab_shop_checkout_paypal_no_forward');
	$message2 = gTxt('yab_shop_checkout_paypal_forward');
	$business_email = yab_shop_config('paypal_business_mail');
	$country = yab_shop_return_input('country'.$req_matrix['country']['mod']);
	if (!$country) {
		$country = yab_shop_config('paypal_prefilled_country');
	}
	$lc = yab_shop_config('paypal_interface_language');
	$thanks = yab_shop_config('checkout_thanks_site');
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

		if (!empty($item['property_1']))
		{
			$parameters['on0_'.$i] = gTxt('yab_shop_custom_field_property_1');
			$parameters['os0_'.$i] = $item['property_1'];
		}
		if (!empty($item['property_2']))
		{
			if (!empty($item['property_3']))
			{
				$parameters['on1_'.$i] = gTxt('yab_shop_custom_field_property_2').'/'.gTxt('yab_shop_custom_field_property_3');
				$parameters['os1_'.$i] = $item['property_2'].'/'.$item['property_3'];
			}
			else
			{
				$parameters['on1_'.$i] = gTxt('yab_shop_custom_field_property_2');
				$parameters['os1_'.$i] = $item['property_2'];
			}
		}
		else
		{
			if (!empty($item['property_3']))
			{
				$parameters['on1_'.$i] = gTxt('yab_shop_custom_field_property_3');
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
		fInput('submit','paypal', gTxt('yab_shop_checkout_paypal_button'), 'submit', '', '', '', '', 'yabpaypalsubmit').n,'form', ' method="post" action="'.$action.'" id="yab-paypal-form"'
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
	$message = gTxt('yab_shop_checkout_google_no_forward');
	$message2 = gTxt('yab_shop_checkout_google_forward');
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
		if (!empty($item['property_1']))
		{
			$gitem_property_1 = gTxt('yab_shop_custom_field_property_1').': '.$item['property_1'];
			$gi++;
		}
		if (!empty($item['property_2']))
		{
			$gitem_property_2 = ': '.gTxt('yab_shop_custom_field_property_2').': '.$item['property_2'];
			$gi++;
		}
		if (!empty($item['property_3']))
		{
			$gitem_property_3 = ': '.gTxt('yab_shop_custom_field_property_3').': '.$item['property_3'];
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
			tag(gTxt('yab_shop_checkout_state'), 'label', ' for="state"').
			fInput('text', 'state'.$req_matrix['state']['mod'], yab_shop_return_input('state'.$req_matrix['state']['mod']), '', '', '', '', '', 'state'), ' class="yab-shop-state'.$req_matrix['state']['cls'].'"'
		);
	}

	if (yab_shop_config('using_checkout_country') == '1')
	{
		$key = yab_shop_return_input('country'.$req_matrix['country']['mod']);
		$country = graf(
			tag(gTxt('yab_shop_checkout_country'), 'label', ' for="country"').
			yab_shop_get_countries('country'.$req_matrix['country']['mod'], $key, true, '', 'country'), ' class="yab-shop-country'.$req_matrix['country']['cls'].'"'
		);
	}

	if (yab_shop_config('using_tou_checkbox') == '1')
	{
		$tou = graf(
			checkbox('tou', '1', '0', '', 'yab-tou').
			tag(gTxt('yab_shop_checkout_terms_of_use'), 'label', ' for="yab-tou"'),
			' class="yab-shop-required tou"'
			);
	}

	$form = tag(
		fieldset(
			pluggable_ui('yab_shop', 'checkout_form_preamble', '', $yab_shop_prefs).
			graf(
				tag(gTxt('yab_shop_checkout_firstname'), 'label', ' for="firstname"').
				fInput('text', 'firstname'.$req_matrix['firstname']['mod'], yab_shop_return_input('firstname'.$req_matrix['firstname']['mod']), '', '', '', '', '', 'firstname'), ' class="yab-shop-firstname'.$req_matrix['firstname']['cls'].'"'
			).
			graf(
				tag(gTxt('yab_shop_checkout_surname'), 'label', ' for="surname"').
				fInput('text', 'surname'.$req_matrix['surname']['mod'], yab_shop_return_input('surname'.$req_matrix['surname']['mod']), '', '', '', '', '', 'surname'), ' class="yab-shop-surname'.$req_matrix['surname']['cls'].'"'
			).
			graf(
				tag(gTxt('yab_shop_checkout_street'), 'label', ' for="street"').
				fInput('text', 'street'.$req_matrix['street']['mod'], yab_shop_return_input('street'.$req_matrix['street']['mod']), '', '', '', '', '', 'street'), ' class="yab-shop-street'.$req_matrix['street']['cls'].'"'
			).
			graf(
				tag(gTxt('yab_shop_checkout_city'), 'label', ' for="city" class="city"').
				fInput('text', 'city'.$req_matrix['city']['mod'], yab_shop_return_input('city'.$req_matrix['city']['mod']), '', '', '', '', '', 'city'), ' class="yab-shop-city'.$req_matrix['city']['cls'].'"'
			).
			graf(
				tag(gTxt('yab_shop_checkout_postal'), 'label', ' for="postal"').
				fInput('text', 'postal'.$req_matrix['postal']['mod'], yab_shop_return_input('postal'.$req_matrix['postal']['mod']), '', '', '', '', '', 'postal'), ' class="yab-shop-zip'.$req_matrix['postal']['cls'].'"'
			).
			$state.$country.
			graf(
				tag(gTxt('yab_shop_checkout_phone'), 'label', ' for="phone"').
				fInput('text', 'phone'.$req_matrix['phone']['mod'], yab_shop_return_input('phone'.$req_matrix['phone']['mod']), '', '', '', '', '', 'phone'), ' class="yab-shop-phone'.$req_matrix['phone']['cls'].'"'
			).
			graf(
				tag(gTxt('yab_shop_checkout_email'), 'label', ' for="email"').
				fInput('text', 'email'.$req_matrix['email']['mod'], yab_shop_return_input('email'.$req_matrix['email']['mod']), '', '', '', '', '', 'email'), ' class="yab-shop-email'.$req_matrix['email']['cls'].'"'
			).
			yab_shop_checkout_payment_methods().
			graf(
				tag(gTxt('yab_shop_checkout_message'), 'label', ' for="message"').
				'<textarea cols="40" rows="5" name="message" id="message">'.yab_shop_return_input('message').'</textarea>', ' class="yab-shop-text"'
			).
			$tou.
			graf(yab_remember_checkbox(), ' class="tou remember"').
			pluggable_ui('yab_shop', 'checkout_form_postamble', '', $yab_shop_prefs).
			pluggable_ui('yab_shop', 'checkout_form_order_button', graf(
				fInput('submit', 'order', gTxt('yab_shop_checkout_order'), 'submit'),
			' class="submit"'
			), $yab_shop_prefs)
		),'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'#yab-shop-checkout-anchor" id="yab-checkout-form"'
	);

	return $form;
}

function yab_shop_build_checkout_table($cart, $summary, $no_change = false)
{
	$tax_inclusive = yab_shop_config('tax_inclusive');

	$checkout_display = tr(
		tag(gTxt('yab_shop_table_caption_content'), 'th').
		tag(gTxt('yab_shop_table_caption_change'), 'th', ' class="yab-checkout-change"').
		tag(gTxt('yab_shop_table_caption_price'), 'th', ' class="yab-checkout-price"')
	).n;

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
				yab_shop_build_checkout_customs($item['property_1'], gTxt('yab_shop_custom_field_property_1'), yab_shop_config('custom_field_property_1_name')).
				yab_shop_build_checkout_customs($item['property_2'], gTxt('yab_shop_custom_field_property_2'), yab_shop_config('custom_field_property_2_name')).
				yab_shop_build_checkout_customs($item['property_3'], gTxt('yab_shop_custom_field_property_3'), yab_shop_config('custom_field_property_3_name')).
				yab_shop_build_checkout_customs($item_price, gTxt('yab_shop_price'), yab_shop_config('custom_field_price_name'))
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
			tda(gTxt('yab_shop_sub_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), ' class="yab-checkout-sum"'),
			' class="yab-checkout-subtotal"'
		).n;
		$checkout_display .= tr(
			tda(gTxt('yab_shop_checkout_tax_exclusive'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('tax')), ' class="yab-checkout-sum"'),
			' class="yab-checkout-tax"'
		).n;
		$checkout_display .= tr(
			tda(gTxt('yab_shop_shipping_costs') . (($ship_data['shipping_options']) ? sp. $ship_data['shipping_options'] : ''), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-shipping"'
		).n;
		$checkout_display .= tr(
			tda(gTxt('yab_shop_grand_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('brutto') + $tax_shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-total"'
		);
	}
	else
	{
		$checkout_display .= tr(
			tda(gTxt('yab_shop_sub_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), ' class="yab-checkout-sum"'),
			' class="yab-checkout-subtotal"'
		).n;
		$checkout_display .= tr(
			tda(gTxt('yab_shop_shipping_costs') . (($ship_data['shipping_options']) ? sp . $ship_data['shipping_options'] : ''), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-shipping"'
		).n;
		$checkout_display .= tr(
			tda(gTxt('yab_shop_grand_total'), ' colspan="2"').
			tda(yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total + $tax_shipping_costs), ' class="yab-checkout-sum"'),
			' class="yab-checkout-total"'
		).n;
		$checkout_display .= tr(
			tda(gTxt('yab_shop_checkout_tax_inclusive'), ' colspan="2"').
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
				href($item['name'], permlinkurl_id($item['txpid'])).
				tag(
					tag(gTxt('yab_shop_price').':&nbsp;'.yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $item['price']), 'li', ' class="yab-price"').
						tag(gTxt('yab_shop_quantity').':&nbsp;'.$item['qty'], 'li', ' class="yab-qty"'),
				'ul'),
			'li', ' class="yab-item"');
		}
		$cart_display = tag($cart_display, 'ul', ' class="yab-cart"');
		$cart_display .= tag(gTxt('yab_shop_sub_total').':&nbsp;'.yab_shop_currency_out(yab_shop_config('currency'), 'cur').yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total), 'span', ' class="yab-subtotal"');
		$cart_display .= tag(gTxt('yab_shop_to_checkout'), 'a', ' href="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'" title="'.gTxt('yab_shop_to_checkout').'" class="yab-to-checkout"');
	}
	else
	{
		$cart_display = tag(gTxt('yab_shop_empty_cart'), 'span', ' class="yab-empty"');
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
		$promo_admin = gTxt('yab_shop_admin_mail_promocode');
		$promo_client = gTxt('yab_shop_affirmation_mail_promocode');
	}

	$yab_shop_mail_info['body']['pre_products'] = ($affirmation == '1') ? gTxt('yab_shop_affirmation_mail_pre_products') : gTxt('yab_shop_admin_mail_pre_products');

	if (!$body_form) {
		$body .= $yab_shop_mail_info['body']['pre_products'].$eol;
	}

	$state = $country = '';
	if (yab_shop_config('using_checkout_state') == '1')
		$state = gTxt('yab_shop_checkout_state').': '.$ps_order['state'.$req_matrix['state']['mod']];

	if (yab_shop_config('using_checkout_country') == '1')
		$country = gTxt('yab_shop_checkout_country').': '.$ps_order['country'.$req_matrix['country']['mod']];

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

	$yab_shop_mail_info['label']['state'] = gTxt('yab_shop_checkout_state');
	$yab_shop_mail_info['label']['country'] = gTxt('yab_shop_checkout_country');
	$yab_shop_mail_info['label']['firstname'] = gTxt('yab_shop_checkout_firstname');
	$yab_shop_mail_info['label']['surname'] = gTxt('yab_shop_checkout_surname');
	$yab_shop_mail_info['label']['street'] = gTxt('yab_shop_checkout_street');
	$yab_shop_mail_info['label']['city'] = gTxt('yab_shop_checkout_city');
	$yab_shop_mail_info['label']['postal'] = gTxt('yab_shop_checkout_postal');
	$yab_shop_mail_info['label']['phone'] = gTxt('yab_shop_checkout_phone');
	$yab_shop_mail_info['label']['email'] = gTxt('yab_shop_checkout_email');
	$yab_shop_mail_info['label']['payment'] = gTxt('yab_shop_checkout_payment');
	$yab_shop_mail_info['label']['message'] = gTxt('yab_shop_checkout_message');
	$yab_shop_mail_info['label']['property_1'] = gTxt('yab_shop_custom_field_property_1');
	$yab_shop_mail_info['label']['property_2'] = gTxt('yab_shop_custom_field_property_2');
	$yab_shop_mail_info['label']['property_3'] = gTxt('yab_shop_custom_field_property_3');

	if (!$body_form) {
		$body .=
			$eol.gTxt('yab_shop_checkout_firstname').': '.$yab_shop_mail_info['body']['firstname'].
			$eol.gTxt('yab_shop_checkout_surname').': '.$yab_shop_mail_info['body']['surname'].
			$eol.gTxt('yab_shop_checkout_street').': '.$yab_shop_mail_info['body']['street'].
			$eol.gTxt('yab_shop_checkout_city').': '.$yab_shop_mail_info['body']['city'].
			$eol.gTxt('yab_shop_checkout_postal').': '.$yab_shop_mail_info['body']['postal'].
			(($state) ? $eol.$state : '') . (($country) ? $eol.$country : '').
			$eol.gTxt('yab_shop_checkout_phone').': '.$yab_shop_mail_info['body']['phone'].
			$eol.gTxt('yab_shop_checkout_email').': '.$yab_shop_mail_info['body']['email'].
			$eol.gTxt('yab_shop_checkout_payment').': '.$yab_shop_mail_info['body']['payment'].
			$eol.gTxt('yab_shop_checkout_message').': '.$yab_shop_mail_info['body']['message'].$eol;
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
				yab_shop_build_mail_customs($item['property_1'], gTxt('yab_shop_custom_field_property_1'), $eol).
				yab_shop_build_mail_customs($item['property_2'], gTxt('yab_shop_custom_field_property_2'), $eol).
				yab_shop_build_mail_customs($item['property_3'], gTxt('yab_shop_custom_field_property_3'), $eol);
		}
	}

	$yab_shop_mail_info['body']['items'] = join('', $items);
	if (!$body_form) {
		$body .= $yab_shop_mail_info['body']['items'];
	}

	$yab_shop_mail_info['body']['sub_total'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $cart->total);
	$yab_shop_mail_info['body']['shipping'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', $shipping);

	$yab_shop_mail_info['label']['grand_total'] = gTxt('yab_shop_grand_total');
	$yab_shop_mail_info['label']['shipping_costs'] = gTxt('yab_shop_shipping_costs');
	$yab_shop_mail_info['label']['sub_total'] = gTxt('yab_shop_sub_total');

	if (yab_shop_config('tax_inclusive') == '0')
	{
		$yab_shop_mail_info['body']['grand_total'] = yab_shop_config('currency').' '.yab_shop_currency_out(yab_shop_config('currency'), 'toform', yab_shop_calculate_sum('brutto') + $tax_shipping);
		$yab_shop_mail_info['body']['tax'] = 0;
		$yab_shop_mail_info['label']['tax_system'] = gTxt('yab_shop_checkout_tax_exclusive');

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
		$yab_shop_mail_info['label']['tax_system'] = gTxt('yab_shop_checkout_tax_inclusive');

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

	$yab_shop_mail_info['body']['after_products'] = ($affirmation == '1') ? gTxt('yab_shop_affirmation_mail_after_products') : gTxt('yab_shop_admin_mail_after_products');
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
	if (!empty($item))
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

	extract(
		lAtts(
			array(
				'type'    => 'body',
				'item'    => '',
				'wraptag' => '',
				'class'   => '',
				'break'   => '',
			),$atts
		)
	);

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
	if (empty($input))
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
	$label = tag(gTxt('yab_shop_checkout_payment'), 'label', ' for="payment"');
	$b = 0;

	if (yab_shop_config('payment_method_acc') == '1')
	{
		$b++;
		$hidden_value .= gTxt('yab_shop_checkout_payment_acc');
		$option .= tag(gTxt('yab_shop_checkout_payment_acc'), 'option', ' value="'.gTxt('yab_shop_checkout_payment_acc').'"');
	}
	if (yab_shop_config('payment_method_pod') == '1')
	{
		$b++;
		$hidden_value .= gTxt('yab_shop_checkout_payment_pod');
		$option .= tag(gTxt('yab_shop_checkout_payment_pod'), 'option', ' value="'.gTxt('yab_shop_checkout_payment_pod').'"');
	}
	if (yab_shop_config('payment_method_pre') == '1')
	{
		$b++;
		$hidden_value .= gTxt('yab_shop_checkout_payment_pre');
		$option .= tag(gTxt('yab_shop_checkout_payment_pre'), 'option', ' value="'.gTxt('yab_shop_checkout_payment_pre').'"');
	}
	if (yab_shop_config('payment_method_paypal') == '1')
	{
		$b++;
		$hidden_value .= gTxt('yab_shop_checkout_payment_paypal');
		$option .= tag(gTxt('yab_shop_checkout_payment_paypal'), 'option', ' value="'.gTxt('yab_shop_checkout_payment_paypal').'"');
	}
	if (yab_shop_config('payment_method_google') == '1')
	{
		$b++;
		$hidden_value .= gTxt('yab_shop_checkout_payment_google');
		$option .= tag(gTxt('yab_shop_checkout_payment_google'), 'option', ' value="'.gTxt('yab_shop_checkout_payment_google').'"');
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
				fInput('text','editqty',$qty,'','','','1').
				fInput('submit','edit',gTxt('yab_shop_checkout_edit'), 'submit-edit').
				fInput('submit','del',gTxt('yab_shop_checkout_delete'), 'submit-del'),
			'div'),
	'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'"'
	);
	return $edit_form;
}

function yab_shop_build_checkout_customs($item, $lang, $conf)
{
	$conf = yab_shop_ascii($conf);
	$out = '';
	if (!empty($item))
	{
		$item = htmlspecialchars($item);
		$out = tag($lang.': '.$item, 'li', ' class="yab-checkout-item-'.$conf.'"');
	}
	return $out;
}

function yab_shop_build_custom_select_tag($custom_field, $label_name, $hide = false )
{
	global $thisarticle;
	$custom_field_low = strtolower($custom_field);
	$out = '';
	$id = $thisarticle['thisid'];

	if (!empty($thisarticle[$custom_field_low]))
	{
		$custom_field_ascii = yab_shop_ascii($custom_field);

		if( $hide )
			$out = yab_shop_custom_field(array('name' => $custom_field, 'hide'=>true ));
		else
		{
			$out =	graf(
				tag($label_name.': ', 'label', ' for="select-'.$custom_field_ascii.'-'.$id.'"').
				yab_shop_custom_field(array('name' => $custom_field)),' class="yab-add-select-'.$custom_field_ascii.'"'
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

	$out['weight'] = $weight;

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
		$shipping_options = '<form method="post" id="yab-ship-method" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'" name="edit_ship_method"><select name="shipping_band" onchange="this.form.submit()">' . $shipping_options .'</select></form>';
	}

	if (yab_shop_config('custom_field_shipping_name') != '')
	{
		$special_cost = 0.00;
		foreach ($cart->get_contents() as $item)
			$special_cost += yab_shop_replace_commas($item['spec_shipping']);

		$shipping_costs += $special_cost;
	}

	// Current shipping method unavailable? Say so
	if (in_array($method, $out['unavailable']['method'])) {
		$out['method_available'] = false;
		$shipping_costs = 'NA';
	} else {
		$out['method_available'] = true;
	}

	// No valid shipping options available at all? Say so
	if ($disabled_methods >= $num_shipping_bands) {
		$shipping_options = gTxt('yab_shop_please_call');
		$shipping_costs = 'NA';
	}

	// Still honour free shipping even if weight has exceeded carrier's limit, because
	// the shipping costs are loaded into the price meaning the merchant can ship by any
	// method they choose
	if ($sub_total >= $free_shipping) {
		$shipping_costs = 0.00;
		$shipping_options = gTxt('yab_shop_checkout_free_shipping');
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
				'toform' => gTxt('yab_shop_value_unavailable'),
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

function yab_shop_type_select_custom($array, $name = 'type', $hide=false)
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
		$out = '<select name="'.$name_ascii.'" id="select-'.$name_ascii.'-'.$id.'">'.n;
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
		if ($pcode != '')
		{
			if (in_array($pcode, do_list(yab_shop_config('promocode'))))
				$out .= graf(gTxt('yab_shop_promocode_success'), ' class="yab-shop-notice yab-promo-success"');
			else
			{
				$out .= graf(gTxt('yab_shop_promocode_error'), ' class="yab-shop-notice yab-promo-error"').
								tag(
									graf(
										tag(gTxt('yab_shop_promocode_label'), 'label', ' for="yab-promo"').
											fInput('text','yab-promo','','','','','','','yab-promo').
											fInput('submit','',gTxt('yab_shop_promocode_button'), 'yab-promo-submit'),
										' class="yab-promocode"'),
									'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'"'
								);
			}
		}
		else
		{
			if ($cart->get_promocode() != NULL)
				$out .= graf(gTxt('yab_shop_promocode_success'), ' class="yab-shop-notice yab-promo-success"');
			else
			{
				$out .= tag(
					graf(
						tag(gTxt('yab_shop_promocode_label'), 'label', ' for="yab-promo"').
						fInput('text','yab-promo','','','','','','','yab-promo').
						fInput('submit','',gTxt('yab_shop_promocode_button'), 'yab-promo-submit'),
					' class="yab-promocode"'),
				'form', ' method="post" action="'.pagelinkurl(array('s' => yab_shop_config('checkout_section_name'))).'"'
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

		$checkbox = checkbox('forget', 1, $forget, '', 'yab-remember').tag(gTxt('yab_shop_forget_me'), 'label', ' for="yab-remember"');
	}
	else
	{
		if ($remember != 1)
			yab_shop_destroyCookies();

		$checkbox = checkbox('remember', 1, $remember, '', 'yab-remember').tag(gTxt('yab_shop_remember_me'), 'label', ' for="yab-remember"');
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
		txp_status_header('303 See Other');
		header('Location: '.$uri);
		header('Connection: close');
		header('Content-Length: 0');
	}
	else
		return false;
}

function yab_shop_cartname()
{
	return 'wfcart' ;
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
	h1, h2, h3,
	h1 code, h2 code, h3 code {
		margin-bottom: 0.6em;
		font-weight: bold
	}
	h1 {
		font-size: 1.4em
	}
	h2 {
		font-size: 1.25em
	}
	h3 {
		margin-bottom: 0;
		font-size: 1.1em
	}
	h4 {
		margin-bottom: 0;
		font-size: 1em
	}
</style>
# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
<h1>yab_shop help</h1>

	<p><strong style="color: #75111B;">This plugin requires the admin ui plugin (yab_shop_admin) and the 3rd-party plugin (yab_shop_3rd_party)</strong></p>

	<ul>
		<li><a href="#install">Installation</a></li>
		<li><a href="#update">Update</a></li>
		<li><a href="#setup">Setup</a></li>
		<li><a href="#tags">Tags for output</a></li>
		<li><a href="#notes">Important notes on setup and maintaining</a></li>
		<li><a href="#uninstall">Uninstallation</a></li>
		<li><a href="#stuff">Other stuff</a></li>
	</ul>

	<h2 id="install">Installation</h2>

	<p>If you see this plugin help, so you have the yab_shop_core plugin. Grab also the yab_shop_admin and the yab_shop_3rd_party plugin.</p>

	<ol>
		<li>Install and activate these plugins.</li>
		<li>Go <a href="?event=yab_shop_prefs">»Extensions-&gt;Yab_Shop Preferences«</a> and install the needed database tables.</li>
		<li>(optional): Install a prepared and prefilled language/localisation plugin (yab_shop_add_language_xx-xx)</li>
		<li>Set your preferences and language/localisation</li>
	</ol>

	<h2 id="update">Update</h2>

	<p>Mostly you can seemlessly update the plugin. With version 0.8.0 config and language strings will saved in additional database tables.</p>

	<h3>Updating from a version before v0.8.0</h3>

	<ol>
		<li>Make a copy of your settings and language/localisation strings.</li>
		<li>Remove or disable the yab_shop_config plugin</li>
		<li>Install the ones (yab_shop_core, yab_shop_admin, yab_shop_3rd_party)</li>
		<li>Go <a href="?event=yab_shop_prefs">»Extensions-&gt;Yab_Shop Preferences«</a> and install the needed database tables.</li>
		<li>(optional): Install a prepared and prefilled language/localisation plugin (yab_shop_add_language_xx-xx)</li>
		<li>Set your preferences and language/localisation</li>
	</ol>

	<h3>Updating from a version before v0.7.0</h3>

	<p>For an easy usage to newcomers and by the reasons of new features some tags has been removed or renamed.</p>

	<ul>
		<li><code>&lt;txp:yab_shop_cart output=&quot;message&quot; /&gt;</code><br />
Attribute value <code>output=&quot;message&quot;</code> doesn&#8217;t exists any more. See below the for changes.<br />
And now you have to place it in checkout section to (f.i. with <code>&lt;txp:yab_shop_cart output=&quot;none&quot; /&gt;</code>)!</li>
		<li><code>&lt;txp:yab_shop_add_message message=&quot;your message here&quot; output=&quot;message&quot; /&gt;</code><br />
Removed.<br />
Now use <code>&lt;txp:yab_shop_cart_message /&gt;</code> instead.</li>
		<li><code>&lt;txp:yab_shop_custom_field name=&quot;price custom field&quot; /&gt;</code><br />
Renamed to <code>&lt;txp:yab_shop_price /&gt;</code> with the attributes wraptag and class.</li>
		<li><code>&lt;txp:yab_shop_property_prices /&gt;</code><br />
Removed. Now load the jquery.js manually please!</li>
	</ul>

	<h2 id="setup">Setup</h2>

	<p>Note: I&#8217;ve created a page with a simple plugin HowTo (beginner) and a <span class="caps">FAQ</span>: <a href="http://www.yablo.de/article/404/howto-an-faq-about-the-textpattern-shopping-cart-plugin-yab_shop">See here</a></p>

	<p>You have to create one additional section. This section will be used for the checkout (table and form).</p>

	<p>Further you have to create at least one additional custom field, where you can store the price for the products. So create one and name it.<br />
Place the used name in Yab_Shop Preferences. Now you can create up to three addtional custom fields if you want multiple product properties.</p>

	<p>Next you have to configure your shop. So go <a href="?event=yab_shop_prefs">Yab_Shop Preferences</a> which contains the configuration. See the <a href="?event=plugin&amp;step=plugin_help&amp;name=yab_shop_admin">yab_shop_admin plugin help</a> for further information.</p>

	<p>For paypal and google checkout setup see <a href="?event=plugin&amp;step=plugin_help&amp;name=yab_shop_admin">plugin help for yab_shop_config</a> or a <a href="http://forum.textpattern.com/viewtopic.php?pid=205495#p205495">thread in the forum</a></p>

	<h2 id="tags">Tags for output</h2>

	<h3><code>&lt;txp:yab_shop_add /&gt;</code></h3>

	<p>This tag outputs the add-to-cart form for the specific product. You have to place it into the individual product/article form (maybe <code>&quot;default&quot;</code>). Since yab_shop_v0.7.0 you can place it in article listings too.</p>

	<h3><code>&lt;txp:yab_shop_cart /&gt;</code></h3>

	<p>This tag is used for adding, editing and deleting products and it&#8217;s outputs the little cart. It <strong>must</strong> be placed somewhere in the shop sections <strong>and</strong> in the your checkout section. Since yab_shop_v0.7.0 it <strong>can</strong> be used as a container tag. You can change the output by the following attribute:</p>

	<ul>
		<li><code>output=&quot;cart&quot;</code> &#8211; default, outputs the little cart</li>
		<li><code>output=&quot;none&quot;</code> &#8211; no output, so you can use it checkout section without any output</li>
	</ul>

	<h4>Usage as container tag</h4>

<pre><code>&lt;txp:yab_shop_cart&gt;
  &lt;txp:yab_shop_cart_items /&gt;
  &lt;txp:yab_shop_cart_quantity /&gt;
  &lt;txp:yab_shop_cart_subtotal /&gt;
  &lt;txp:yab_shop_cart_link /&gt;
  &lt;txp:yab_shop_cart_message /&gt;
&lt;/txp:yab_shop_cart&gt;
</code></pre>

	<h3><code>&lt;txp:yab_shop_cart_items /&gt;</code></h3>

	<p>Outputs the items in the cart al a list. Can only be used inside the container tag <code>&lt;txp:yab_shop_cart&gt;</code>. No attributes.</p>

	<h3><code>&lt;txp:yab_shop_cart_quantity /&gt;</code></h3>

	<p>Shows the quantity of the items in the cart. Can be used standalone or inside the container tag <code>&lt;txp:yab_shop_cart&gt;</code>. The following attributes are available:</p>

	<ul>
		<li><strong>output=&#8220;single&#8221;</strong><br />
Choose your itemcount. &#8216;single&#8217; for different products. &#8216;all&#8217; for all product items (default &#8216;single&#8217;).</li>
		<li><strong>showalways=&#8220;1&#8221;</strong><br />
Displaying it even if cart is empty (default &#8216;1&#8217;).</li>
		<li><strong>break=&#8220;br&#8221;</strong><br />
Break after output (default &#8216;br&#8217;).</li>
		<li><strong>label=&#8220;Quantity: &#8220;</strong><br />
Label or name before itemcount output (default &#8216;Quantity: &#8216;).</li>
		<li><strong>wraptag=&#8220;span&#8221;</strong><br />
Wraptag around the output (default blank).</li>
		<li><strong>class=&#8220;someclass&#8221;</strong><br />
Class for wraptag (default blank).</li>
	</ul>

	<h3><code>&lt;txp:yab_shop_cart_subtotal /&gt;</code></h3>

	<p>Shows the cart subtotal. Can be used standalone or inside the container tag <code>&lt;txp:yab_shop_cart&gt;</code>. The following attributes are available:</p>

	<ul>
		<li><strong>showalways=&#8220;1&#8221;</strong><br />
Displaying it even if cart is empty (default &#8216;1&#8217;).</li>
		<li><strong>break=&#8220;br&#8221;</strong><br />
Break after output (default &#8216;br&#8217;).</li>
		<li><strong>label=&#8220;Subtotal: &#8220;</strong><br />
Label or name before itemcount output (default &#8216;Subtotal: &#8216;).</li>
		<li><strong>wraptag=&#8220;span&#8221;</strong><br />
Wraptag around the output (default blank).</li>
		<li><strong>class=&#8220;someclass&#8221;</strong><br />
Class for wraptag (default blank).</li>
	</ul>

	<h3><code>&lt;txp:yab_shop_cart_link /&gt;</code></h3>

	<p>Shows a link to your checkout page. Can be used standalone or inside the container tag <code>&lt;txp:yab_shop_cart&gt;</code>. The following attributes are available:</p>

	<ul>
		<li><strong>showalways=&#8220;1&#8221;</strong><br />
Displaying it even if cart is empty (default &#8216;1&#8217;).</li>
		<li><strong>break=&#8220;br&#8221;</strong><br />
Break after output (default &#8216;br&#8217;).</li>
		<li><strong>label=&#8220;proceed to checkout&#8221;</strong><br />
Label or name before itemcount output (default &#8216;to_checkout&#8217; from yab_shop_config).</li>
		<li><strong>wraptag=&#8220;span&#8221;</strong><br />
Wraptag around the output (default blank).</li>
		<li><strong>class=&#8220;someclass&#8221;</strong><br />
Class for wraptag or link, if no wraptag is set (default blank).</li>
	</ul>

	<h3><code>&lt;txp:yab_shop_cart_message /&gt;</code></h3>

	<p>Shows a message depending on a done action. Can be used standalone or inside the container tag <code>&lt;txp:yab_shop_cart&gt;</code>. The following attributes are available:</p>

	<ul>
		<li><strong>add=&#8220;Product has been added&#8221;</strong><br />
Shows a message when a products has been added to cart (default &#8216;Product has been added&#8217;).</li>
		<li><strong>edit=&#8220;Cart has been updated&#8221;</strong><br />
Shows a message when a product count has been changed in checkout page (default &#8216;Cart has been updated&#8217;).</li>
		<li><strong>del=&#8220;Product has been deleted&#8221;</strong><br />
Shows a message when a product has been deleted from cart in checkout page (default &#8216;Product has been deleted&#8217;).</li>
		<li><strong>break=&#8220;br&#8221;</strong><br />
Break after output (default &#8216;br&#8217;).</li>
		<li><strong>wraptag=&#8220;span&#8221;</strong><br />
Wraptag around the output (default blank).</li>
		<li><strong>class=&#8220;someclass&#8221;</strong><br />
Class for wraptag (default blank).</li>
	</ul>

	<h3><code>&lt;txp:yab_shop_price /&gt;</code></h3>

	<p>It outputs the price. It can be placed in all article/product forms (individual and listings).<br />
The following attributes are available:</p>

	<ul>
		<li><strong>wraptag=&#8220;span&#8221;</strong><br />
Wraptag surrounded the Price (default &#8216;span&#8217;).</li>
		<li><strong>class=&#8220;yab-shop-price&#8221;</strong><br />
Class for the wraptag (default &#8216;yab-shop-price&#8217;).</li>
	</ul>

	<h3><code>&lt;txp:yab_shop_checkout /&gt;</code></h3>

	<p>This tag outputs the checkout table, where you can edit product quantities. And it outputs the checkout form, where you can finally submit your order.<br />
The following attributes are available:</p>

	<ul>
		<li><strong>summary=&#8220;your summary here&#8221;</strong><br />
Summary attribute of the <span class="caps">HTML</span> table element.</li>
	</ul>

	<h3><code>&lt;txp:yab_shop_show_config /&gt;</code></h3>

	<p>Outputs a value of the current yab_shop_config, so it can be used for weird things (<code>&lt;txp:if ... /&gt;</code>, <code>&lt;txp:variable ... /&gt;</code> etc. pp.).<br />
The following attributes are available:</p>

	<ul>
		<li><strong>name=&#8220;config value here&#8221;</strong><br />
The value of the config.</li>
	</ul>

	<h2 id="notes">Important notes on setup and maintaining</h2>

	<p>All numbers for prices in custom field or shipping costs in config can be written with comma or dot as decimal delimter. But beware: Do not use any thousand delimiter!<br />
The output format in <span class="caps">HTML</span> or mail depends on the selected currency in the config.</p>

	<h3>How do I input properties?</h3>

	<p>If you use one, two or all three custom fields for different product properties you have to fill the input fields with values separated by a semicolon, followed by a whitespace (you can leave the whitespace out, it will work both ways).<br />
E.g. for custom field &raquo;Size&laquo;: <code>0.2mm; 3m; 5km; 100pc</code></p>

	<h3>And how do I input prices for a property?</h3>

	<p>Note: You can only assign <strong>one</strong> property with a price.</p>

	<p>First go in <a href="?event=yab_shop_prefs">»Yab_Shop Preferences«</a> and change the use_property_prices to <code>&#39;1&#39;</code>.</p>

	<p>Then, if not yet done, load the jquery.js in your shop sections. Add the following line in your form or site template between the <code>&lt;head&gt;</code> and <code>&lt;/head&gt;</code>:</p>

	<p><code>&lt;script type=&quot;text/javascript&quot; src=&quot;&lt;txp:site_url /&gt;textpattern/jquery.js&quot;&gt;&lt;/script&gt;</code></p>

	<p><strong>Input the Prices:</strong><br />
If you want use a property price you must give an price in your price field (custom field) even so. You can use it as an base price.<br />
Now you can give prices to the properties in <strong>one</strong> property field (custom field). Use double minus as delimter between property and price. E.g for the property field color:</p>

	<p><code>red; white--2,50; black--12,00; green--0,55</code></p>

	<p>The properties with no price declaration will use the base price of the price field (custom field). The first property price should be the same as the base price. That&#8217;s all!</p>

	<h3>How do I use promo-codes, coupons etc.?</h3>

	<p>Go in  <a href="?event=yab_shop_prefs">Yab_Shop Preferences</a> and set the <code>Promocode key</code> with a key, which a customer have to insert on the checkout page to get the promotional discount (E.g. <code>&#39;XFHTE&#39;</code> or another value). With <code>Given promo discount (%)</code> you can set the promotional discount in percent (E.g. <code>&#39;5&#39;</code>). Absolute discounts like 5€ on all products are not supported due the lack of support by paypal and google checkout.</p>

	<h2 id="uninstall">Uninstallation</h2>

	<ol>
		<li>For an uninstallation <a href="?event=yab_shop_prefs&amp;step=yab_shop_uninstall">klick here</a> (<strong>All Yab_Shop setting will be removed immediately</strong>)</li>
		<li>Disable or delete the yab_shop_xxx plugins</li>
	</ol>

	<h2 id="stuff">Other stuff</h2>

	<p>You can see a live demo on <a href="http://demoshop.yablo.de/">demoshop.yablo.de</a><br />
For help use the <a href="http://forum.textpattern.com/viewtopic.php?id=26300">forum</a> please!</p>
# --- END PLUGIN HELP ---
-->
<?php
}
?>