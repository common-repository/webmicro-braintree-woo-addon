<?php
/**
 * Plugin Name: Braintree WooCommerce Addon
 * Plugin URI: Plugin URI: https://wordpress.org/plugins/webmicro-braintree-woo-addon/
 * Description: This plugin adds a payment option in WooCommerce for customers to pay with their Credit Cards Via Braintree a Paypal Company.
 * Version: 1.0.0
 * Author: Syed Nazrul Hassan
 * Author URI: https://nazrulhassan.wordpress.com/
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function braintree_init()
{
	include(plugin_dir_path( __FILE__ )."lib/Braintree.php");
	
	function add_braintree_gateway_class( $methods ) 
	{
		$methods[] = 'WC_braintree_Gateway'; 
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_braintree_gateway_class' );
	
	if(class_exists('WC_Payment_Gateway'))
	{
		class WC_braintree_Gateway extends WC_Payment_Gateway 
		{
		public function __construct()
		{

		$this->id               = 'braintree';
		$this->icon             = plugins_url( 'images/braintree.png' , __FILE__ )  ;
		$this->has_fields       = true;
		$this->method_title     = 'Braintree Cards Settings';		
		$this->init_form_fields();
		$this->init_settings();

		$this->supports             = array( 'products');

		$this->title			    = $this->get_option( 'braintree_title' );
		$this->braintree_merchantid = $this->get_option( 'braintree_merchantid' );
		$this->braintree_publickey  = $this->get_option( 'braintree_publickey' );
		$this->braintree_privatekey = $this->get_option( 'braintree_privatekey' );
		$this->braintree_sandbox    = $this->get_option( 'braintree_sandbox' );
		//$this->braintree_authorize_only = $this->get_option( 'braintree_authorize_only' );
		$this->braintree_cardtypes      = $this->get_option( 'braintree_cardtypes'); 
		
		if(!defined("BRAINTREE_MODE"))
		{ define("BRAINTREE_MODE"  , ($this->braintree_sandbox =='yes'? 'sandbox' : 'production')); }

		/*if(!defined("BRAINTREE_TRANSACTION_MODE"))
		{ define("BRAINTREE_TRANSACTION_MODE"  , ($this->braintree_authorize_only =='yes'? true : false)); }*/
		
		 if (is_admin()) 
		 {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 		 }

		 }
		
		
		
		public function admin_options()
		{
		?>
		<h3><?php _e( 'Braintree addon for WooCommerce', 'woocommerce' ); ?></h3>
		<p><?php  _e( 'Braintree is a paypal company allowing merchants to accept Cards payment.', 'woocommerce' ); ?></p>
		<table class="form-table">
		  <?php $this->generate_settings_html(); ?>
		</table>
		<?php
		}
		
		
		
		public function init_form_fields()
		{
		$this->form_fields = array
		(
			'enabled' => array(
			  'title' => __( 'Enable/Disable', 'woocommerce' ),
			  'type' => 'checkbox',
			  'label' => __( 'Enable Network Merchant', 'woocommerce' ),
			  'default' => 'yes'
			  ),
			'braintree_title' => array(
			  'title' => __( 'Title', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This controls the title which the buyer sees during checkout.', 'woocommerce' ),
			  'default' => __( 'Braintree', 'woocommerce' ),
			  'desc_tip'      => true,
			  ),
			'braintree_merchantid' => array(
			  'title' => __( 'Braintree Merchantid', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is Braintree merchant id.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Braintree Merchant ID'
			  ),
			'braintree_publickey' => array(
			  'title' => __( 'Braintree Publickey', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is Braintree publickey.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Braintree Publickey'
			  ),
			'braintree_privatekey' => array(
			  'title' => __( 'Braintree Privatekey', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is Braintree privatekey.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Braintree Privatekey'
			  ),
			'braintree_sandbox' => array(
				'title'       => __( 'Braintree sandbox', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Braintree sandbox (Live Mode if Unchecked)', 'woocommerce' ),
				'description' => __( 'If checked its in sanbox mode and if unchecked its in live mode', 'woocommerce' ),
				'desc_tip'      => true,
				'default'     => 'no'
				),

			/*'braintree_authorize_only' => array(
				'title'       => __( 'Authorize Only', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Authorize Only Mode (Authorize & Capture If Unchecked)', 'woocommerce' ),
				'description' => __( 'If checked will only authorize the credit card only upon checkout.', 'woocommerce' ),
				'desc_tip'      => true,
				'default'     => 'no',
				),*/
			'braintree_cardtypes' => array(
			 'title'    => __( 'Accepted Cards', 'woocommerce' ),
			 'type'     => 'multiselect',
			 'class'    => 'chosen_select',
			 'css'      => 'width: 350px;',
			 'desc_tip' => __( 'Select the card types to accept.', 'woocommerce' ),
			 'options'  => array(
				'mastercard'       => 'MasterCard',
				'visa'             => 'Visa',
				'discover'         => 'Discover',
				'amex' 		       => 'American Express',
				'jcb'		       => 'JCB',
				'maestro'          => 'Maestro',
				'unionpay'		   => 'UnionPay'
			 ),
			 'default' => array( 'mastercard', 'visa', 'discover', 'amex' ),
			),
	  	);
  		}


  		public function get_icon() {
		$icon = '';
		if(is_array($this->braintree_cardtypes ))
		{
        foreach ($this->braintree_cardtypes as $card_type ) {

			if ( $url = $this->get_payment_method_image_url( $card_type ) ) {
				
				$icon .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( strtolower( $card_type ) ) . '" />';
			}
		  }
		}
		else
		{
			$icon .= '<img src="' . esc_url( plugins_url( 'images/braintree.png' , __FILE__ ) ).'" alt="Mercant One Gateway" />';	  
		}

         return apply_filters( 'woocommerce_braintree_icon', $icon, $this->id );
		}
      
		public function get_payment_method_image_url( $type ) {

		$image_type = strtolower( $type );

			return  WC_HTTPS::force_https_url( plugins_url( 'images/' . $image_type . '.png' , __FILE__ ) ); 
		}

	

		     /*Start of credit card form */
  		public function payment_fields() {
			$this->form();
		}

  		public function field_name( $name ) {
		return $this->supports( 'tokenization' ) ? '' : ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
	}

  		public function form() {
		wp_enqueue_script( 'wc-credit-card-form' );
		$fields = array();
		$cvc_field = '<p class="form-row form-row-last">
			<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Code', 'woocommerce' ) . ' <span class="required">*</span></label>
			<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . '/>
		</p>';
		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
			</p>',
			'card-cvc-field'  => $cvc_field
		);
		
		 $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>

		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
		
	}
  		/*End of credit card form*/



		/*Payment Processing Fields*/
		public function process_payment($order_id)
		{
		
			global $woocommerce;
         	$wc_order = new WC_Order($order_id);
         		
			Braintree_Configuration::environment(BRAINTREE_MODE);
			Braintree_Configuration::merchantId($this->braintree_merchantid);
			Braintree_Configuration::publicKey($this->braintree_publickey);
			Braintree_Configuration::privateKey($this->braintree_privatekey);
         
         	//echo($clientToken = Braintree_ClientToken::generate());
         	//$nonce = $_POST["payment_method_nonce"];

     	   $card_num = sanitize_text_field(str_replace(' ', '', $_POST['braintree-card-number']));
            $exp_date = explode("/", sanitize_text_field($_POST['braintree-card-expiry']));
            $exp_month = str_replace(' ', '', $exp_date[0]);
            $exp_year = str_replace(' ', '', $exp_date[1]);
            $cvc = sanitize_text_field($_POST['braintree-card-cvc']);

			if (strlen($exp_year) == 2) {
			$exp_year += 2000;
			}

			$result = Braintree_Transaction::sale(array(
            'amount'              => $wc_order->order_total,
            'orderId'             => $wc_order->get_order_number(),
            'creditCard' => array(
                'number'          => $card_num,
                'cvv'             => $cvc,
                'expirationMonth' => $exp_month,
                'expirationYear'  => $exp_year
            ),
            "customer"   => array(
                "firstName"       => $wc_order->billing_first_name,
                "lastName"        => $wc_order->billing_last_name,
                "company"         => $wc_order->billing_company,
                "phone"           => $wc_order->billing_phone,
                "email"           => $wc_order->billing_email
            ),
            "billing"    => array(
                'firstName'       => $wc_order->billing_first_name,
                'lastName'        => $wc_order->billing_last_name,
                'company'         => $wc_order->billing_company,
                'streetAddress'   => $wc_order->billing_address_1,
                'extendedAddress' => $wc_order->billing_address_2,
                'locality'        => $wc_order->billing_city,
                'region'          => $wc_order->billing_state,
                'postalCode'      => $wc_order->billing_postcode,
                'countryCodeAlpha2' => $wc_order->billing_country
            ),
            'shipping'   => array(
                'firstName'       => $wc_order->shipping_first_name,
                'lastName'        => $wc_order->shipping_last_name,
                'company'         => $wc_order->shipping_company,
                'streetAddress'   => $wc_order->shipping_address_1,
                'extendedAddress' => $wc_order->shipping_address_2,
                'locality'        => $wc_order->shipping_city,
                'region'          => $wc_order->shipping_state,
                'postalCode'      => $wc_order->shipping_postcode,
                'countryCodeAlpha2' => $wc_order->shipping_country
            ),
            "options"    => array(
                "submitForSettlement" => true
            )
                ));

		 $datearray =   (array) $result->transaction->createdAt ;

         if($result->success)
         {
         	$wc_order->add_order_note( __( 'Trx Id: '.$result->transaction->id.',Trx Type: '.$result->transaction->type.',Trx Status: '.$result->transaction->status.',Processor auth code: '.$result->transaction->processorAuthorizationCode.',Processor response text: '.$result->transaction->processorResponseText.', Created at: '.$datearray['date'].' '.$datearray['timezone']  , 'woocommerce' ) );

         	$wc_order->payment_complete($result->transaction->id);
			WC()->cart->empty_cart();
			return array (
						'result'   => 'success',
						'redirect' => $this->get_return_url( $wc_order ),
					   );
         }
         else 
		 {
			$wc_order->add_order_note( __( $result->message  , 'woocommerce' ) );	 
			wc_add_notice($result->message , $notice_type = 'error' );
		 }
		
        
		}// End of process_payment
		
		
		}// End of class WC_Authorizenet_braintree_Gateway
	} // End if WC_Payment_Gateway
}// End of function authorizenet_braintree_init

add_action( 'plugins_loaded', 'braintree_init' );

function braintree_addon_activate() {

	if(!function_exists('curl_exec'))
	{
		 wp_die( '<pre>This plugin requires PHP CURL library installled in order to be activated </pre>' );
	}
}
register_activation_hook( __FILE__, 'braintree_addon_activate' );

/*Plugin Settings Link*/
function braintree_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_braintree_gateway">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'braintree_settings_link' );