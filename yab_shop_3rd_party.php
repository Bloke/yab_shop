<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'yab_shop_3rd_party';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.9.1';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'Shopping Cart Plugin (3rd party classes)';

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

if (!class_exists('wfCart'))
{

/**
 * Modified Webforce Cart v.1.5
 *
 * 2008-03-02 Cleaned some code (Tommy Schmucker)
 * 2008-12-18 Modified with promocode-support (Tommy Schmucker)
 * 2009-01-27 Modified with TXP-ID-support (Tommy Schmucker)
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

	class wfCart
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

		function set_promocode($value)
		{
			$this->promocode = $value;
		}

		function get_promocode()
		{
			return $this->promocode;
		}

		function edit_promocodes($itemid, $value)
		{
			$this->promocodes[$itemid] = $value;
		}

		function edit_promo_prices($itemid, $value)
		{
			$this->itemprices[$itemid] = $value;
			$this->_update_total();
		}

		function set_ship_method($value)
		{
			$this->ship_method = $value;
		}

		function get_ship_method()
		{
			return $this->ship_method;
		}

		function get_contents()
		{
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

		function add_item($itemid, $txpid, $qty = 1, $price = false, $rrp = false, $saving = 0, $name = false, $property_1 = false, $property_2 = false, $property_3 = false, $spec_shipping = false, $tax = false, $weight = false) 
		{
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

		function edit_item($itemid, $qty)
		{
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

		function del_item($itemid)
		{
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

		function empty_cart()
		{
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

		function _update_total()
		{
			$this->itemcount = 0;
			$this->total = 0;
			if (sizeof($this->items) > 0)
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
		var $privateKeyPass;
		var $paypalCertificate;
		var $paypalCertificateFile;
		var $certificateID;
		var $tempFileDirectory;
		var $error;

		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			$this->error = 0;

			if (defined('YAB_SHOP_PRIVATE_KEY_PASS')) {
				$this->privateKeyPass = YAB_SHOP_PRIVATE_KEY_PASS;
			}
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

				$keyCheckData = ($this->privateKeyPass) ? array(0 => $privateKey, 1 => $this->privateKeyPass) : $privateKey;

				if (($certificate !== false) and ($privateKey !== false) and openssl_x509_check_private_key($certificate, $keyCheckData))
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

			$keySignData = ($this->privateKeyPass) ? array(0 => $this->privateKey, 1 => $this->privateKeyPass) : $this->privateKey;

			if (!openssl_pkcs7_sign("{$dataFile}_data.txt", "{$dataFile}_signed.txt", $this->certificate, $keySignData, array(), PKCS7_BINARY))
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
</style>
# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
<h1>Third party classes and functions for yab_shop</h1>

	<p><strong style="color: #75111B;">This is the third party plugin for yab_shop, which required this plugin.</strong></p>
# --- END PLUGIN HELP ---
-->
<?php
}
?>