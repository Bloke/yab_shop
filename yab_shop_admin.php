<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'yab_shop_admin';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.9.1';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'Shopping Cart Plugin (Admin UI)';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '4';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '1';

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

$plugin['textpack'] = <<< EOT
#@language en, en-gb, en-us
#@owner yab_shop
#@yab_shop_prefs
yab_shop_common_prefs => Yab_Shop common preferences
yab_shop_tax_rate => Tax bands (in %, pipe-delimited)
yab_shop_shipping_costs => Shipping costs (fixed value or weight-table)
yab_shop_shipping_via => Shipping via (Used by Google Checkout)
yab_shop_free_shipping => Free shipping at
yab_shop_currency => Currency (ISO 4717)
yab_shop_promocode => Promocode key(s) (comma-delimited)
yab_shop_promo_discount_percent => Promo discounts (%, comma-delimited: one per promocode)
yab_shop_tax_inclusive => Tax inclusive (otherwise exclusive)
yab_shop_payment_method_acc => Use payment method: Purchase on account
yab_shop_payment_method_pod => Use payment method: Purchase on delivery
yab_shop_payment_method_pre => Use payment method: Purchase against prepayment
yab_shop_payment_method_paypal => Use payment method: Paypal checkout
yab_shop_payment_method_google => Use payment method: Google checkout
yab_shop_using_checkout_state => Use state field in checkout form
yab_shop_using_checkout_country => Use country field in checkout form
yab_shop_using_tou_checkbox => Use TOU checkbox in checkout form
yab_shop_checkout_section_name => Name of the checkout section
yab_shop_checkout_thanks_site => Checkout thank-you-site (Full URI)
yab_shop_checkout_required_fields => List of required fields in checkout form
yab_shop_back_to_shop_link => Back-to-shop-link (Full URI)
yab_shop_custom_field_price_name => Name of the custom field price
yab_shop_custom_field_property_1_name => Name of the custom field property 1
yab_shop_custom_field_property_2_name => Name of the custom field property 2
yab_shop_custom_field_property_3_name => Name of the custom field property 3
yab_shop_custom_field_shipping_name => Name of the custom field special shipping costs
yab_shop_custom_field_tax_band => Name of the custom field tax bands
yab_shop_custom_field_weight => Name of the custom field product weight
yab_shop_admin_mail => Admin Mail (Receives the orders)
yab_shop_order_affirmation_mail => Send affirmation mail to buyers
yab_shop_email_mime_type => MIME type of the affirmation / admin e-mail
yab_shop_email_body_form => Textpattern form(s) for e-mail body layout
yab_shop_use_property_prices => Use property prices
yab_shop_use_checkout_images => Use images in checkout form
yab_shop_paypal_prefs => Preferences for Paypal checkout
yab_shop_use_encrypted_paypal_button => Use an encrypted Paypal button
yab_shop_paypal_prefilled_country => Prefilled country in Paypal interface
yab_shop_paypal_interface_language => Paypal interface language
yab_shop_paypal_business_mail => Email of the Paypal business account
yab_shop_paypal_live_or_sandbox => Live or sandbox
yab_shop_paypal_certificate_id => Paypal certificate ID
yab_shop_paypal_certificates_path => Path to Paypal certificate (absolute)
yab_shop_paypal_public_certificate_name => Name of the public Paypal certificate
yab_shop_paypal_my_public_certificate_name => Name of your public certificate
yab_shop_paypal_my_private_key_name => Name of your private key
yab_shop_google_prefs => Preferences for Google checkout
yab_shop_google_live_or_sandbox => Live or sandbox
yab_shop_google_merchant_id => Google merchant ID
yab_shop_google_merchant_key => Google merchant key
yab_shop_prefs => Yab_Shop Preferences
yab_shop_prefs_updated => Yab_Shop preferences saved.
yab_shop_tables_delete_error => Could not delete Yab_Shop database tables.
yab_shop_tables_delete_success => Yab_Shop database tables deleted.
yab_shop_klick_to_update => Click to upgrade Yab_Shop:
#@public
yab_shop_price => Price
yab_shop_quantity => Quantity
yab_shop_sub_total => Subtotal
yab_shop_to_checkout => Proceed to Checkout
yab_shop_empty_cart => No Items in Cart
yab_shop_add_to_cart => Add to Cart
yab_shop_table_caption_content => Content
yab_shop_table_caption_change => Quantity
yab_shop_table_caption_price => Price Sum
yab_shop_custom_field_property_1 => Size
yab_shop_custom_field_property_2 => Color
yab_shop_custom_field_property_3 => Variant
yab_shop_checkout_tax_exclusive => 20% Tax exclusive
yab_shop_checkout_tax_inclusive => 20% Tax inclusive (where applicable)
yab_shop_checkout_shipping_unavailable => Please choose a different shipping method.
yab_shop_checkout_free_shipping => Free!
yab_shop_shipping_costs => Shipping Costs
yab_shop_grand_total => Total
yab_shop_checkout_edit => Change Qty
yab_shop_checkout_delete => x
yab_shop_promocode_label => Promo code
yab_shop_promocode_button => Apply
yab_shop_promocode_error => Sorry, wrong promo code
yab_shop_promocode_success => Promo code applied.
yab_shop_checkout_required_field_notice => <p>If you do not already have an account, you may create one by choosing a login name in the form yab_shop_below. Having an account will entitle you to discount special offers and promotions, as well as being the first to get hold of the yab_shop_latest news and offers.</p><p>You can either enter your details below to save them with your account, or skip it and go straight to yab_shop_PayPal. Note that PayPal accept credit/debit cards too. If you have problems with the shop, please <a href="/contact">get in touch yab_shop_by e-mail</a> or call <txp:output_form form="phone_number" />.</p>
yab_shop_checkout_firstname => First Name
yab_shop_checkout_surname => Last Name
yab_shop_checkout_street => Street
yab_shop_checkout_postal => ZIP Code
yab_shop_checkout_city => City
yab_shop_checkout_state => State
yab_shop_checkout_country => Country
yab_shop_checkout_phone => Phone
yab_shop_checkout_email => Email
yab_shop_checkout_message => Message
yab_shop_checkout_tou => Terms Of Use
yab_shop_checkout_terms_of_use => I have read the <a href="/terms" title="Terms of Use">Terms of Use</a>
yab_shop_checkout_summary => This table shows the cart with selected products and the total sum of the products.
yab_shop_remember_me => Remember my data for next visit (cookie)
yab_shop_forget_me => Forget my data
yab_shop_checkout_order => Purchase/Order
yab_shop_checkout_legend => Purchase Form
yab_shop_checkout_payment_acc => Purchase on Account
yab_shop_checkout_payment_pod => Purchase on Delivery
yab_shop_checkout_payment_pre => Purchase against Prepayment
yab_shop_checkout_payment_paypal => Purchase via Paypal
yab_shop_checkout_payment_google => Purchase via Google Checkout
yab_shop_checkout_payment => Payment Method
yab_shop_checkout_paypal_forward => You will be forwarded to Paypal. Please wait&hellip;
yab_shop_checkout_paypal_button => Go to paypal
yab_shop_checkout_paypal_no_forward => Please click the button to proceed.
yab_shop_paypal_return_message => Thank you for purchasing.
yab_shop_checkout_google_forward => You will be forwarded to Google Checkout. Please wait&hellip;
yab_shop_checkout_google_no_forward => Please click the button to proceed.
yab_shop_checkout_history_back => Back to Shop
yab_shop_checkout_mail_error => Your order could not be sent
yab_shop_checkout_mail_success => Your order was successfully sent
yab_shop_checkout_mail_email_error => Email is invalid
yab_shop_checkout_mail_affirmation_error => Your order was successfully sent but a confirmation e-mail could not be delivered to you (perhaps you yab_shop_did not supply an e-mail address?)
yab_shop_checkout_mail_affirmation_success => Your order and confirmation were successfully sent.
yab_shop_checkout_mail_field_error => Please correctly fill out the following required fields:
yab_shop_admin_mail_subject => Shop Order
yab_shop_admin_mail_pre_products => The following was ordered:
yab_shop_admin_mail_after_products => This text will be on the end of the admin mail
yab_shop_admin_mail_promocode => The order is already calculated with promo discount
yab_shop_affirmation_mail_subject => Your Shop Order
yab_shop_affirmation_mail_pre_products => Thank you for shopping. Your order summary follows:
yab_shop_affirmation_mail_after_products =>
yab_shop_affirmation_mail_promocode => Your order is already calculated with promo discount
yab_shop_cart_message_add => Product has been added
yab_shop_cart_message_edit => Cart has been updated
yab_shop_cart_message_del => Product has been deleted
yab_shop_has_options_hint => More options available
yab_shop_please_call => <strong>Please call</strong>
yab_shop_value_unavailable => Unavailable
EOT;
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
if (txpinterface === 'admin') {
	add_privs('yab_shop_prefs','1');
	register_tab('extensions', 'yab_shop_prefs', gTxt('yab_shop_prefs'));
	register_callback('yab_shop_prefs', 'yab_shop_prefs');
}

// define some prefs as globals
global $yab_shop_prefs;
if (yab_shop_table_exist('yab_shop_prefs') === true)
{
	$yab_shop_prefs = yab_shop_get_prefs();
}

/**
 * Draw initialise and draw shop prefs
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
		'yab_shop_prefs_save'    => false,
		'yab_shop_uninstall'     => false,
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
			$message = 'Cannot run ' . $stp;
		}
		if (yab_shop_table_exist('yab_shop_prefs') === true)
			$content = yab_shop_display_prefs();
	}
	echo pagetop(gTxt('yab_shop_prefs'), $message).$content;
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
		$text = 'Click to install Yab_Shop:';
	}
	else
	{
		$sInput = sInput('yab_shop_update');
		$button = gTxt('update');
		$text = gTxt('yab_shop_klick_to_update');
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
		yab_shop_update();
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
	$tbl = PFX.$tbl;
	$r = mysqli_num_rows(safe_query("SHOW TABLES LIKE '".$tbl."'"));
	if ($r) {
		$current_ver = safe_field('val', 'yab_shop_prefs', "name='yab_shop_version'");
		if (yab_shop_version() != $current_ver)
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
 * Yab_Shop installed version info
 *
 * @return string Yab_Shop installed version number
 */
function yab_shop_version()
{
	global $plugins_ver;
	return $plugins_ver['yab_shop_admin'];
}

/**
 * Get Yab_Shop prefs or language and display it
 *
 * @param string Table without prefix
 * @return string
 */
function yab_shop_display_prefs($table = 'yab_shop_prefs')
{
	// choose step and event
	$submit = sInput('yab_shop_prefs_save').eInput('yab_shop_prefs');

  $out = '<form method="post" action="index.php">'.startTable('list');

	$rs = safe_rows_start('*', 'yab_shop_prefs', "type = 1 ORDER BY event DESC, position ");

	// now make a html table from the database table
	$cur_evt = '';
	while ($a = nextRow($rs))
	{
		if ($a['event']!= $cur_evt)
		{
			$cur_evt = $a['event'];
			$out .= n.tr(
				n.tdcs(
					hed(gTxt($a['event']), 2, ' class="pref-heading"')
				, 2)
			);
		}

		if ($a['html'] != 'yesnoradio')
			$label = '<label for="'.$a['name'].'">'.gTxt($a['name']).'</label>';
		else
			$label = gTxt($a['name']);

		if ($a['html'] == 'text_input')
		{
			// choose different text_input sizes for these fields
			$look_for = array(
				'yab_shop_promocode',
				'yab_shop_promo_discount_percent',
				'yab_shop_shipping_costs',
				'yab_shop_checkout_section_name',
				'yab_shop_checkout_thanks_site',
				'yab_shop_checkout_required_fields',
				'yab_shop_back_to_shop_link',
				'yab_shop_custom_field_price_name',
				'yab_shop_custom_field_property_1_name',
				'yab_shop_custom_field_property_2_name',
				'yab_shop_custom_field_property_3_name',
				'yab_shop_custom_field_shipping_name',
				'yab_shop_custom_field_tax_band',
				'yab_shop_custom_field_weight',
				'yab_shop_admin_mail',
				'yab_shop_email_mime_type',
				'yab_shop_email_body_form',
				'yab_shop_paypal_business_mail',
				'yab_shop_paypal_live_or_sandbox',
				'yab_shop_paypal_certificate_id',
				'yab_shop_paypal_certificates_path',
				'yab_shop_paypal_public_certificate_name',
				'yab_shop_paypal_my_public_certificate_name',
				'yab_shop_paypal_my_private_key_name',
				'yab_shop_google_live_or_sandbox',
				'yab_shop_google_merchant_id',
				'yab_shop_google_merchant_key',
			);
			if (in_array($a['name'], $look_for))
				$size = 50;
			else
				$size = 6;

			$out_tr = td(
				yab_shop_pref_func('yab_shop_text_input', $a['name'], $a['val'], $size)
			);
		}
		elseif ($a['html'] == 'text_area')
		{
			$size = 17;

			$out_tr = td(
				yab_shop_pref_func('yab_shop_text_area', $a['name'], $a['val'], $size)
			);
		}
		else
		{
			if (is_callable($a['html']))
			{
				$out_tr = td(
					yab_shop_pref_func($a['html'], $a['name'], $a['val'])
				);
			}
			else
			{
				$out.= n.td($a['val']);
			}
		}
		$out .= n.tr(
			n.tda($label, ' style="text-align: right; vertical-align: middle;"').
			n.$out_tr
		);
	}

	$out .= n.tr(
		n.tda(
			fInput('submit', 'Submit', gTxt('save'), 'publish').
			$submit
		, ' colspan="2" class="noline"')
	).
	n.n.endTable().
	n.n.'</form>';
	return $out;
}

/**
 * Return config values
 *
 * @param string $what
 * @return string
 */
function yab_shop_config($what)
{
	global $yab_shop_prefs;
	return $yab_shop_prefs['yab_shop_'.$what];
}

/**
 * Return prefs array from database
 *
 * @return array Prefs
 */
function yab_shop_get_prefs()
{
	$r = safe_rows_start('name, val', 'yab_shop_prefs', '1=1');

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
		"IL" => "Israel",
		"IM" => "Isle Of Man",
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
		"XK" => "Kosovo, Republic of",
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
 * @param string $name
 * @param string $val
 * @param integer $size
 * @return string HTML text input
 */
function yab_shop_text_input($name, $val, $size = '')
{
	return fInput('text', $name, $val, 'edit', '', '', $size, '', $name);
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
	return tag($val, 'textarea', ' name="'.$name.'" cols="'.$size.'" rows="4"');
}

/**
 * Save prefs setting in admin ui
 *
 * @return string Message for pagetop()
 */
function yab_shop_prefs_save()
{
	$post = doSlash(stripPost());
	$prefnames = safe_column("name", "yab_shop_prefs", "type = 1");

	foreach($prefnames as $prefname)
	{
		if (isset($post[$prefname]))
		{
			safe_update(
				"yab_shop_prefs",
				"val = '".$post[$prefname]."'",
				"name = '".doSlash($prefname)."'"
			);
		}
  }
	return gTxt('yab_shop_prefs_updated');
}


/**
 * Upgrade Yab_Shop database tables
 *
 * @return string Message for pagetop()
 * TODO: convert shipping_costs html column to text_area
 */
function yab_shop_update()
{
	global $DB;

	// Upgrade yab_shop_prefs val field from VARCHAR to TEXT
	$ret = @safe_field("DATA_TYPE", "INFORMATION_SCHEMA.COLUMNS", "table_name = '" . PFX . "yab_shop_prefs' AND table_schema = '" . $DB->db . "' AND column_name = 'val'");

	if ($ret != 'text') {
		safe_alter('yab_shop_prefs', "CHANGE `val` `val` TEXT NOT NULL DEFAULT ''");
	}

	$cols = getThings('describe `'.PFX.'yab_shop_prefs`');

	if (in_array('prefs_id', $cols)) {
	    safe_alter('yab_shop_prefs', "DROP COLUMN prefs_id");
	}

	$common_atts = array(
		'type'     => '1',
		'event'    => 'shop_common_prefs',
		'html'     => 'text_input',
		'position' => '50',
	);

	$new_prefs = array(
		'yab_shop_custom_field_weight'      => '',
		'yab_shop_custom_field_tax_band'    => '',
		'yab_shop_checkout_required_fields' => 'firstname, surname, street, city, state, postal, country',
		'yab_shop_email_mime_type'          => 'text/plain',
		'yab_shop_email_body_form'          => '',
	);

	$entries = safe_column('name', 'yab_shop_prefs', '1=1');

	foreach ($new_prefs as $prefname => $prefval) {
		if (!in_array($prefname, $entries)) {
			$items = array('name' => $prefname, 'val' => $prefval) + $common_atts;
			$bits = array();

			foreach ($items as $idx => $val) {
				$bits[] = $idx . '=' . doQuote(doSlash($val));
			}

			$qry = join(', ', $bits);
			safe_insert('yab_shop_prefs', $qry);
		}
	}

	// Notify the plugin it's been upgraded
	safe_update('yab_shop_prefs', "val='" . doSlash(yab_shop_version()) . "'", "name='yab_shop_version'");
}

/**
 * Uninstall Yab_Shop database tables
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
			return gTxt('yab_shop_tables_delete_error');
	}
	return gTxt('yab_shop_tables_delete_success');
}

/**
 * Installation routine
 *
 * @return boolean
 */
function yab_shop_install($table)
{
	global $txpcfg, $DB;
	$yab_shop_version = yab_shop_version();
	$version = mysqli_get_server_info($DB->link);
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

		mysqli_query($DB->link, "SET NAMES ".$dbcharset);
	}

	$create_sql = array();

	switch ($table)
	{
		case 'yab_shop_prefs':
			$create_sql[] = "CREATE TABLE `".PFX."yab_shop_prefs` (
				`name` varchar(255) NOT NULL,
				`val` text NOT NULL default '',
				`type` smallint(5) unsigned NOT NULL default '1',
				`event` varchar(18) NOT NULL default 'shop_prefs',
				`html` varchar(64) NOT NULL default 'text_input',
				`position` smallint(5) unsigned NOT NULL default '0',
				UNIQUE KEY `name` (`name`(50))
			) $tabletype ";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_tax_rate', '19', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_shipping_costs', '7.50', 1, 'shop_common_prefs', 'text_area', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_shipping_via', 'UPS', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_free_shipping', '20.00', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_currency', 'EUR', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_promocode', '', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_promo_discount_percent', '10', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_tax_inclusive', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_payment_method_acc', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_payment_method_pod', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_payment_method_pre', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_payment_method_paypal', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_payment_method_google', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_using_checkout_state', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_using_checkout_country', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_using_tou_checkbox', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_checkout_section_name', 'checkout', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_checkout_thanks_site', 'http://domain/shop/thank-you', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_checkout_required_fields', 'firstname, surname, street, city, state, postal, country', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_back_to_shop_link', 'http://domain/shop/', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_custom_field_price_name', 'Price', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_custom_field_property_1_name', 'Size', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_custom_field_property_2_name', 'Color', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_custom_field_property_3_name', 'Variant', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_custom_field_shipping_name', '', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_custom_field_tax_band', '', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_custom_field_weight', '', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_admin_mail', 'admin@domain.tld', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_order_affirmation_mail', '1', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_email_mime_type', 'text/plain', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_email_body_form', '', 1, 'shop_common_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_use_property_prices', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_use_checkout_images', '0', 1, 'shop_common_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_use_encrypted_paypal_button', '1', 1, 'paypal_prefs', 'yesnoradio', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_prefilled_country', 'en', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_interface_language', 'en', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_business_mail', 'admin@domain.tld', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_live_or_sandbox', 'sandbox', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_certificate_id', 'CERTIFICATEID', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_certificates_path', '/path/to/your/certificates', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_public_certificate_name', 'paypal_cert.pem', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_my_public_certificate_name', 'my-public-certificate.pem', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_paypal_my_private_key_name', 'my-private-key.pem', 1, 'paypal_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_google_live_or_sandbox', 'sandbox', 1, 'google_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_google_merchant_id', 'your-merchant-id', 1, 'google_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_google_merchant_key', 'your-merchant-id', 1, 'google_prefs', 'text_input', 50)";
			$create_sql[] = "INSERT INTO `".PFX."yab_shop_prefs` VALUES ('yab_shop_version', '".doSlash($yab_shop_version)."', 2, 'version', '', 50)";
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
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
  h1, h2, h3
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
  table {
    margin-bottom: 1em
  }
	td table td {
		padding: 3px 0;
		border-bottom: 1px solid #000000
	}
</style>
# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
<p><strong style="color: #75111B;">This is admin ui plugin for yab_shop, which required this plugin.</strong></p>

	<h1>Some help for configuration</h1>

	<ol>
		<li><a href="#1">Yab_Shop common preferences</a></li>
		<li><a href="#2">Preferences for Paypal checkout</a></li>
		<li><a href="#3">Preferences for Google checkout</a></li>
		<li><a href="#4">Yab_Shop public language and localisation</a></li>
	</ol>

	<h2 id="1">1. Yab_Shop common preferences</h2>

	<p>This stores the core config for the yab_shop plugin.</p>

	<table>
		<tr>
			<td>Tax rate (%)</td>
			<td>your tax rate in percent</td>
		</tr>
		<tr>
			<td>Shipping costs</td>
			<td>write with dot or comma as decimal delimiter</td>
		</tr>
		<tr>
			<td>Shipping via (Used by Google Checkout)</td>
			<td>shipping method; this time only relevant for google checkout</td>
		</tr>
		<tr>
			<td>Free shipping at</td>
			<td>free shipping limit</td>
		</tr>
		<tr>
			<td>Currency (<span class="caps"><span class="caps">ISO</span></span> 4717)</td>
			<td>Paypal supported <span class="caps"><span class="caps">ISO</span></span> 4217 currency codes (see <a href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_batch-payment-format-outside">here</a>) and additional support for <span class="caps"><span class="caps">EEK</span></span> (Ask for more!)</td>
		</tr>
		<tr>
			<td>Promocode key</td>
			<td>If you want a promo-code support ad a promo-key here (E.g: <code>&#39;XFHDB&#39;</code>) otherwise leave it blank</td>
		</tr>
		<tr>
			<td>Given promo discount (%)</td>
			<td>Discount for accepted promo-codes in percent (absolute discounts are not supported!)</td>
		</tr>
		<tr>
			<td>Tax inclusive (otherwise exclusive)</td>
			<td>if <code>&#39;Yes&#39;</code> sums and output calculated with tax inclusive, otherwise exclusive</td>
		</tr>
		<tr>
			<td>Use payment method: Purchase on account</td>
			<td><code>&#39;Yes&#39;</code> for &#8220;purchase on account&#8221;</td>
		</tr>
		<tr>
			<td>Use payment method: Purchase on delivery</td>
			<td><code>&#39;Yes&#39;</code> for &#8220;purchase on delivery&#8221;</td>
		</tr>
		<tr>
			<td>Use payment method: Purchase against prepayment</td>
			<td><code>&#39;Yes&#39;</code> for &#8220;purchase against prepaiment&#8221;</td>
		</tr>
		<tr>
			<td>Use payment method: Paypal checkout</td>
			<td><code>&#39;Yes&#39;</code> for &#8220;paypal as payment method&#8221;</td>
		</tr>
		<tr>
			<td>Use payment method: Google checkout</td>
			<td><code>&#39;Yes&#39;</code> for &#8220;google checkout as payment method&#8221;</td>
		</tr>
		<tr>
			<td>Use state field in checkout form</td>
			<td><code>&#39;Yes&#39;</code> displays an additional form for state (useful for US and Canada)</td>
		</tr>
		<tr>
			<td>Use <span class="caps"><span class="caps">TOU</span></span> checkbox in checkout form</td>
			<td><code>&#39;Yes&#39;</code>, if you want an required Terms-of-use-checkbox in Checkout</td>
		</tr>
		<tr>
			<td>Name of the checkout section</td>
			<td>name for the created checkout section</td>
		</tr>
		<tr>
			<td>Checkout thank-you-site (Full <span class="caps"><span class="caps">URI</span></span>)</td>
			<td>redirect to a special thanks site after a successful order so you can use site and/or conversion tracking (leave it blank if you don&#8217;t use it)</td>
		</tr>
		<tr>
			<td>Back-to-shop-link (Full <span class="caps"><span class="caps">URI</span></span>)</td>
			<td>link for the &#8220;back top shoppping&#8221; after an order</td>
		</tr>
		<tr>
			<td>Name of the custom field price</td>
			<td>name for the created <code>custom_field</code> for the product price (must be the same)</td>
		</tr>
		<tr>
			<td>Name of the custom field property 1</td>
			<td>name for first product property</td>
		</tr>
		<tr>
			<td>Name of the custom field property 2</td>
			<td>name for second product property</td>
		</tr>
		<tr>
			<td>Name of the custom field property 3</td>
			<td>name for third product property</td>
		</tr>
		<tr>
			<td>Name of the custom field special shipping costs</td>
			<td>name for an extra special shipping custom_field. By using this you can set an product specific shipping cost, which will add to the base shipping cost at checkout (leave it blank if you don&#8217;t use it).</td>
		</tr>
		<tr>
			<td>Admin Mail (Receives the orders)</td>
			<td>shop mail address, which will receive the orders</td>
		</tr>
		<tr>
			<td>Send affirmation mail to buyers</td>
			<td>if <code>&#39;Yes&#39;</code> an order affirmation mail will be sent to customer and the form email field will be marked as required</td>
		</tr>
		<tr>
			<td>Use property prices</td>
			<td><code>&#39;Yes&#39;</code> for usage of extra prices for one product property</td>
		</tr>
		<tr>
			<td>Use images in checkout form</td>
			<td>use of article images (existing thumbnails) in checkout table</td>
		</tr>
	</table>

	<h2 id="2">2. Preferences for Paypal checkout</h2>

	<table>
		<tr>
			<td>Use an encrypted Paypal button</td>
			<td>If you are using Paypal it&#8217;s strongly recommended using an encrypted button</td>
		</tr>
		<tr>
			<td>Prefilled country in Paypal interface</td>
			<td>the country, which should prefilled in the paypal form</td>
		</tr>
		<tr>
			<td>Paypal interface language</td>
			<td>en, fr, es or de (maybe more, see paypal site)</td>
		</tr>
		<tr>
			<td>Email of the Paypal business account</td>
			<td>it&#8217;s your paypal business account  mail</td>
		</tr>
		<tr>
			<td>Live or sandbox</td>
			<td>is this shop in testing use <code>&#39;sandbox&#39;</code> otherwise <code>&#39;live&#39;</code></td>
		</tr>
	</table>

	<p><strong>If you are using Paypal it&#8217;s strongly recommended using an encrypted button</strong></p>

	<p>The button encryption will only working with a php openssl support. In doubt ask your hoster or simple test it. It should output an error if php openssl functions don&#8217;t exists. For setup a certificate for your paypal account follow the instructions at <a href="https://www.paypal.com/IntegrationCenter/ic_button-encryption.html#Encryptbuttonsdynamically">paypal</a> or have look in this <a href="http://forum.textpattern.com/viewtopic.php?pid=210899#p210899">forum thread</a> and block all non-encrypted website payments (info on same site). After setting up your account with the certificates, you will have two certificates files, one private key file and a paypal certificate id.These three files (a public paypal certificate, your public certificate and your private key) you have to copy on your server.</p>

	<p>But you <strong><span class="caps">MUST</span> copy these in a directory which is outside of DocumentRoot</strong>, nobody should get access on your own private key.</p>

	<table>
		<tr>
			<td>Paypal certificate ID</td>
			<td>generated ID from paypal for your uploaded public certificate</td>
		</tr>
		<tr>
			<td>ath to Paypal certificate (absolute)</td>
			<td>absolute path to your certificate files<br />
(f.i. <code>/home/user/certificates</code>)</td>
		</tr>
		<tr>
			<td>Name of the public Paypal certificate</td>
			<td>name of paypal public certificate</td>
		</tr>
		<tr>
			<td>Name of your public certificate</td>
			<td>name of your public certificate</td>
		</tr>
		<tr>
			<td>Name of your private key</td>
			<td>name of your private key</td>
		</tr>
	</table>

	<h2 id="3">3. Preferences for Google checkout</h2>

	<p>If you are choose google checkout as payment method and your location is in US I prefer the following setup, &#8216;cause of different tax rates and tax calculation methods:<br />
Set <code>Tax Rate (%)</code> to <code>0</code> and <code>10  Tax inclusive (otherwise exclusive)</code> to <code>No</code>. In your google checkout merchant account you can configure the right tax rates for the states. The tax calculation will be done by google.</p>

	<p>For google checkout you have to set <code>Shipping via</code> with your appropriate shipping method.</p>

	<table>
		<tr>
			<td>Live or sandbox</td>
			<td>is this shop in testing use <code>&#39;sandbox&#39;</code> otherwise <code>&#39;live&#39;</code></td>
		</tr>
		<tr>
			<td>Google merchant ID</td>
			<td>this is your google checkout merchant id</td>
		</tr>
		<tr>
			<td>Google merchant key</td>
			<td>this is your google checkout merchant key</td>
		</tr>
	</table>

# --- END PLUGIN HELP ---
-->
<?php
}
?>