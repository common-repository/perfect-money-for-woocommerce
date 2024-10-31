<?php
/*
Plugin Name: WooCommerce Perfect Money Payment Gateway
Plugin URI: http://perfectmoney.is
Description: Perfect Money Payment gateway for woocommerce
Version: 1.0
Author: SR & I
Author URI: http://perfectmoney.is
*/
add_action('plugins_loaded', 'woocommerce_gateway_perfectmoney_init', 0);
function woocommerce_gateway_perfectmoney_init(){
  if(!class_exists('WC_Payment_Gateway')) return;
 
  class WC_Gateway_PerfectMoney extends WC_Payment_Gateway{ 
    public function __construct(){
      $this -> id = 'perfectmoney';
      $this -> medthod_title = 'Perfect Money';
	  $this -> icon = get_site_url().'/wp-content/plugins/perfectmoney/perfectmoney.png';
      $this -> has_fields = false;
 
      $this -> init_form_fields();
      $this -> init_settings();
 
      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      $this -> payee_account = $this -> settings['payee_account'];
      $this -> payee_name = $this -> settings['payee_name'];
      $this -> alternate_phrase = $this -> settings['alternate_phrase'];
      $this -> redirect_page_id = $this -> settings['redirect_page_id'];
      $this -> liveurl = 'https://perfectmoney.is/api/step1.asp';
      define('CALLBACK_URL', get_site_url().'/?wc-api=WC_Gateway_PerfectMoney&perfectmoney=callback');

      $this -> msg['message'] = "";
      $this -> msg['class'] = "";

      add_action('woocommerce_api_wc_gateway_perfectmoney', array($this, 'check_perfectmoney_response'));
     	if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array($this, 'process_admin_options' ) );
            }
      add_action('woocommerce_receipt_perfectmoney', array($this, 'receipt_page'));
	}

    function init_form_fields(){

       $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'pm'),
                    'type' => 'checkbox',
                    'label' => __('Enable Perfect Money Payment Module.', 'pm'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'pm'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'pm'),
                    'default' => __('Perfect Money', 'pm')),
                'description' => array(
                    'title' => __('Description:', 'pm'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'pm'),
                    'default' => __('Pay securely by Perfect Money through Secure Servers.', 'pm')),
                'payee_account' => array(
                    'title' => __('Payee Account', 'pm'),
                    'type' => 'text',
                    'description' => __('Your Perfect Money account you want to get payments to (like U12345)')),
                'payee_name' => array(
                    'title' => __('Payee Name', 'pm'),
                    'type' => 'text',
                    'default' => __('Shop', 'pm')),
                'alternate_phrase' => array(
                    'title' => __('Alternate PassPhrase', 'pm'),
                    'type' => 'text',
                    'description' =>  __('Alternate PassPhrase can be found and set under Settings section in your
PM account.', 'pm'),
                ),
                'redirect_page_id' => array(
                    'title' => __('Return Page'),
                    'type' => 'select',
                    'options' => $this -> get_pages('Select Page'),
                    'description' => "URL of success page"
                )
            );
    }

       public function admin_options(){
        echo '<h3>'.__('Perfect Money Payment Gateway', 'pm').'</h3>';
        echo '<p>'.__('Perfect Money popular payment gateway for online shopping.').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

    /**
     *  There are no payment fields for perfectmoney, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }
    /**
     * Receipt Page
     **/
    function receipt_page($order){
        echo '<p>'.__('Thank you for your order, please click the button below to pay with Perfect Money.', 'pm').'</p>';
        echo $this -> generate_perfectmoney_form($order);
    }
    /**
     * Generate perfectmoney button link
     **/
    public function generate_perfectmoney_form($order_id){

       global $woocommerce;
    	$order = new WC_Order( $order_id );
        $txnid = $order_id.'_'.date("ymds");

        $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);

        $productinfo = "Order $order_id";

        $str = "$this->payee_account|$txnid|$order->order_total|$productinfo|$order->billing_first_name|$order->billing_email|||||||||||$this->alternate_phrase";
        $hash = hash('sha512', $str);

        $perfectmoney_args = array(
          'key' => $this -> payee_account,
          'txnid' => $txnid,
          'amount' => $order -> order_total,
          'productinfo' => $productinfo,
          'firstname' => $order -> billing_first_name,
          'lastname' => $order -> billing_last_name,
          'address1' => $order -> billing_address_1,
          'address2' => $order -> billing_address_2,
          'city' => $order -> billing_city,
          'state' => $order -> billing_state,
          'country' => $order -> billing_country,
          'zipcode' => $order -> billing_zip,
          'email' => $order -> billing_email,
          'phone' => $order -> billing_phone,
          'surl' => $redirect_url,
          'furl' => $redirect_url,
          'curl' => $redirect_url,
          'hash' => $hash,
          'pg' => 'NB'
          );

		  $currs=array('U'=>'USD', 'E'=>'EUR');
		  $cur_l=substr($this -> payee_account, 0,1);
		  $currency=$currs[$cur_l];

        $perfectmoney_args_array = array();
        foreach($perfectmoney_args as $key => $value){
          $perfectmoney_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
        return '<form action="'.$this -> liveurl.'" method="post" id="perfectmoney_payment_form">
<input type="hidden" name="SUGGESTED_MEMO" value="'.$productinfo.'">

<input type="hidden" name="PAYMENT_ID" value="'.$order_id.'" />
<input type="hidden" name="PAYMENT_AMOUNT" value="'.$order -> order_total.'" />
<input type="hidden" name="PAYEE_ACCOUNT" value="'.$this -> payee_account.'" />
<input type="hidden" name="PAYMENT_UNITS" value="'.$currency.'" />
<input type="hidden" name="PAYEE_NAME" value="'.$this -> payee_name.'" />
<input type="hidden" name="PAYMENT_URL" value="'.$redirect_url.'" />
<input type="hidden" name="PAYMENT_URL_METHOD" value="LINK" />
<input type="hidden" name="NOPAYMENT_URL" value="'.$redirect_url.'" />
<input type="hidden" name="NOPAYMENT_URL_METHOD" value="LINK" />
<input type="hidden" name="STATUS_URL" value="'.CALLBACK_URL.'" />
            <input type="submit" class="button-alt" id="submit_perfectmoney_payment_form" value="'.__('Pay via Perfect Money', 'pm').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'pm').'</a>
            <script type="text/javascript">
jQuery(function(){
jQuery("body").block(
        {
            message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'pm').'",
                overlayCSS:
        {
            background: "#fff",
                opacity: 0.6
    },
    css: {
        padding:        20,
            textAlign:      "center",
            color:          "#555",
            border:         "3px solid #aaa",
            backgroundColor:"#fff",
            cursor:         "wait",
            lineHeight:"32px"
    }
    });
    jQuery("#submit_perfectmoney_payment_form").click();});</script>
            </form>';


    }
    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id){
        global $woocommerce;
    	$order = new WC_Order( $order_id );
        return array('result' => 'success', 'redirect' => add_query_arg('order',
            $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
        );
    }

    /**
     * Check for valid perfectmoney server callback
     **/
    function check_perfectmoney_response(){
        global $woocommerce;

		define('ALTERNATE_PHRASE_HASH', strtoupper(md5($this->alternate_phrase)));

		// Path to directory to save logs. Make sure it has write permissions.
		//define('PATH_TO_LOG',  '/somewhere/out/of/document_root/');

		$string=
			  $_POST['PAYMENT_ID'].':'.$_POST['PAYEE_ACCOUNT'].':'.
			  $_POST['PAYMENT_AMOUNT'].':'.$_POST['PAYMENT_UNITS'].':'.
			  $_POST['PAYMENT_BATCH_NUM'].':'.
			  $_POST['PAYER_ACCOUNT'].':'.ALTERNATE_PHRASE_HASH.':'.
			  $_POST['TIMESTAMPGMT'];

		$hash=strtoupper(md5($string));

		if($hash==$_POST['V2_HASH']){ // proccessing payment if only hash is valid

			$order = new WC_Order($_POST['PAYMENT_ID']);

			if($_POST['PAYMENT_AMOUNT']==$order->order_total && $_POST['PAYEE_ACCOUNT']==$this->payee_account){

				$order -> payment_complete();
                $order -> add_order_note('Perfect Money payment successful<br/>Unnique Id from Perfect Money: '.$_REQUEST['mihpayid']);
                $order -> add_order_note($this->msg['message']);
                $woocommerce -> cart -> empty_cart();

			    /*f=fopen(PATH_TO_LOG."good.log", "ab+");
				fwrite($f, date("d.m.Y H:i")."; POST: ".serialize($_POST)."; STRING: $string; HASH: $hash\n");
				fclose($f);*/

		   }else{ // you can also save invalid payments for debug purposes

			  /*$f=fopen(PATH_TO_LOG."bad.log", "ab+");
			  fwrite($f, date("d.m.Y H:i")."; REASON: fake data; POST: ".serialize($_POST)."; STRING: $string; HASH: $hash\n");
			  fclose($f);*/

		   }


		}else{ // you can also save invalid payments for debug purposes

		   // uncomment code below if you want to log requests with bad hash
		   /*$f=fopen(PATH_TO_LOG."bad.log", "ab+");
		   fwrite($f, date("d.m.Y H:i")."; REASON: bad hash; POST: ".serialize($_POST)."; STRING: $string; HASH: $hash\n");
		   fclose($f);*/

		}

		wp_die('done');

    }

    function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
     // get all pages
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
}
   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_perfectmoney_gateway($methods) {
        $methods[] = 'WC_Gateway_PerfectMoney';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_perfectmoney_gateway' );
}
