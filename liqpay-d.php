<?php
/*
Plugin Name: Saphali Woocommerce LiqPay  for donate
Plugin URI: http://saphali.com/saphali-woocommerce-plugin-wordpress
Description: Кнопка для приема пожертвований.
Version: 1.0
Author: Saphali
Author URI: http://saphali.com/
*/
include_once (plugin_dir_path(__FILE__) . 'LiqPay-class.php');
class liqpay_donate_saphali {
	var $liqpay;
	var $public_key;
	var $privat_key;
	function __construct() {
		$this->public_key = get_option('liqpay_public_key'); 
		$this->privat_key       = base64_decode(strrev(get_option('liqpay_privat_key')));
		add_action('admin_footer', array($this, 'eg_quicktags') );
		add_action('init', array($this,'add_button'));
		add_shortcode('saphali_liqpay', array($this, 'shortcode') );
		add_action('wp_ajax_liqpay_sign', array($this, 'lqsignature') );
		add_action('wp_ajax_nopriv_liqpay_sign', array($this, 'lqsignature') );
		if( !( empty($this->public_key) || empty($this->privat_key) ) ) {
			$this->liqpay = new LiqPayApi($this->public_key, $this->privat_key);
		}
		add_action('admin_menu', array($this, 'adminMenu') );
	}
	function adminMenu() {
		if ( function_exists('add_menu_page') ) {
			$this->menu_id = add_menu_page( __('LiqPay','saphali-liqpay'), __('LiqPay','saphali-liqpay'), 'manage_options' ,'liqpay-config', array($this,'configPage') , plugins_url('images/menu_icons.png', __FILE__));
		}
	}
	function configPage () {
		if( isset($_POST['liqpay_privat_key']) && (sanitize_text_field( $_POST['liqpay_privat_key'] ) ==  $_POST['liqpay_privat_key'] ) )
			update_option('liqpay_privat_key',strrev(base64_encode( sanitize_text_field ( $_POST['liqpay_privat_key'] ) )));
		if( isset($_POST['liqpay_public_key']) )
			update_option('liqpay_public_key', sanitize_text_field( $_POST['liqpay_public_key'] ) );
		$public_key = isset($_POST['liqpay_public_key']) ? sanitize_text_field( $_POST['liqpay_public_key'] ): $this->public_key;
		if( sanitize_text_field( $_POST['liqpay_privat_key'] ) !=  $_POST['liqpay_privat_key'] ) {
			$error = new WP_Error ('broke', __( "Секретный ключ не сохранен, т.к. имеет запрещенные символы", "saphali-liqpay" ) );
			$privat_key = $this->privat_key;
		} else 
		$privat_key = isset($_POST['liqpay_privat_key']) ? sanitize_text_field( $_POST['liqpay_privat_key'] ): $this->privat_key;
		
		?>
		<h3><?php _e('LiqPay - Настройка','saphali-liqpay'); ?></h3>
		<?php if ( isset($error) && is_wp_error( $error ) ) {
   $error_string = $error->get_error_message();
   echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
} ?>
		<form method="post" action="">
		<table class="wp-list-table widefat filds_comment">
			<thead>
			<tr>
				
			</tr>
			</thead>
			<tbody id="the-list" class="myTable">
			<tr><th width="120px">Публичный ключ</th><td><input  type="text" name="liqpay_public_key" value='<?php echo $public_key; ?>' /></td></tr>
			<tr><th>Приватный ключ</th><td><input type="password" name="liqpay_privat_key" size="60" value='<?php echo $privat_key; ?>' /></td></tr>
			</tbody></table>
		<div class="clear"></div>
		<p><button class="button-primary"><?php _e('Сохранить','saphali-customer-reviews'); ?></button></p>
		</form>
		<?php
	}
	function lqsignature() {
		$params = (array) json_decode( base64_decode( sanitize_text_field( $_POST['data'] ) ) );
		$total = isset( $_POST['amount'] ) ? sanitize_text_field( $_POST['amount'] ) : $params['amount'];
		$order_id = isset($_POST['order_id']) ? sanitize_text_field( $_POST['order_id'] ) : $params['order_id'] ;
		$params['amount'] = $total;
		$params['order_id'] = $order_id;
		
		$data      = base64_encode( json_encode($params) );

		$signature = $this->liqpay->cnb_signature($params);
		die( json_encode(array('signature' => $signature, 'data' => $data )) );
	}
	function shortcode($atts, $content = null) {
		return do_shortcode($this->saphali_shortcode($atts));
	}
	function saphali_shortcode($atts) {
		if(empty($this->liqpay)) return;
		$order_id = time();
		if(!isset($atts["amount"]))  $atts["amount"] = 10;
		if(!isset($atts["desc"]))  $atts["desc"] = 'Пожертвование';
		$params = array(
		  'version'        => '3',
		  'public_key'     => $this->public_key,
		  'amount'         => $atts["amount"],
		  'currency'       => 'UAH',
		  'description'    => $atts["desc"],
		  'order_id'       => $order_id,
		  'pay_way'        => 'card,liqpay,delayed,invoice,privat24',
		  'type'      	   => 'donate',
		   'language'       => 'ru',
		);
        $data      = base64_encode( json_encode($params) );
        $signature = $this->liqpay->cnb_signature($params);
		return '<form id="liqpayform" method="POST" action="https://www.liqpay.com/api/checkout"  accept-charset="utf-8">			  <input type="hidden" name="data" value="'. $data .'" />			  			  <input type="text" class="form__input__new" name="amount" value="'. $atts["amount"] .'" />			  <input type="hidden" name="signature" value="'.$signature.'" />			  <input type="image" src="//static.liqpay.com/buttons/d1ru.radius.png" name="btn_text" />			</form>'			. '			<style>			.form__input__new {				background: #fff none repeat scroll 0 0;				border: 1px solid #b0b0b0;				color: #222;				font-size: 14px;				float: left;				height: 36px;				line-height: 36px;				margin-right: 5px;				margin-top: 7px;				padding-left: 10px;				width: auto;				min-width: 40px;			}			</style>			<script>jQuery("form#liqpayform").attr("action", jQuery("form#liqpayform").attr("action").replace(/^\/\//g,"https://") );jQuery("form#liqpayform input[type=\'image\']").click( function(event){	event.preventDefault();	var _this = jQuery(this);	var _this_sing = jQuery(\'input[name="signature"]\');	var _this_data = jQuery(\'input[name="data"]\');	jQuery.ajax({		url: \'/wp-admin/admin-ajax.php?action=liqpay_sign\',		method: \'POST\',		dataType : \'json\',		data: {\'amount\': jQuery(\'input[name="amount"]\').val(), \'order_id\': jQuery(\'input[name="order_id"]\').val(), data: "'. $data .'" },		success: function(data) {			_this_sing.val(data.signature);			_this_data.val(data.data);			_this.parent().submit();		}	});});</script>';
	}
	function eg_quicktags() {
		?>
		<script type="text/javascript" charset="utf-8">
			jQuery(document).ready(function(){
				if(typeof(QTags) !== 'undefined') {
					QTags.addButton( 'saphali_liqpay', 'Добавить кнопку пожертвований', '[saphali_liqpay amount="10" desc="Пожертвование"]');
				}
			});
		</script>
		<?php 
	}
		
		
	function add_button() {
	   if ( current_user_can('edit_posts') &&  current_user_can('edit_pages') )
	   {
		 add_filter('mce_external_plugins',array($this,  'add_plugin'));
		 add_filter('mce_buttons',array($this, 'register_button'));
	   }
	}
	function register_button($buttons) {
	   array_push($buttons, "saphali_liqpay");
	  
	   return $buttons;
	}
	function add_plugin($plugin_array) {

		$plugin_array['saphali_liqpay'] = plugin_dir_url(__FILE__).'js/customcodes.js';
	   
	   return $plugin_array;
	}
}
new liqpay_donate_saphali();
?>