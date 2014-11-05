<?php
  /*
Plugin Name: WP eCommerce - Accept Bitcoin and Payin Local Payment Gateway
Plugin URI: https://aligncommerce.com
Description: Add Align Commerce Payment Gateway for WP eCommerce.
Version: 1.0.0
Author: Align Commerce Corporation
Author URI: https://aligncommerce.com
License: GPLv2
*/

//register_activation_hook(__FILE__, "aligncom_payment_create");

function ecommerce_btc_payment_fallback_notice() {
    echo '<div class="error"><p>' . sprintf( __( 'eCommerce Alligncommerce Payment Gateways depends on the last version of %s to work!', 'wpsc' ), '<a href="http://wordpress.org/extend/plugins/wp-e-commerce/">WP eCommerce</a>' ) . '</p></div>';
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
/**clear data when uninstall plugin******************/
register_uninstall_hook(    __FILE__, 'uninstall_ac_paymentGateways_ecommerce' );
function uninstall_ac_paymentGateways_ecommerce()
{
    delete_option('acBtc_al_country');
    delete_option('acBtc_description');
    delete_option('acBtc_api_key');
    delete_option('acBtc_api_secret');
    delete_option('acBtc_al_username');
    delete_option('acBtc_al_password');
    delete_option('acBtc_redirect_url');
    delete_option('acBtc_ipn_url');
    delete_option('acBank_al_country');
    delete_option('acBank_description');
    delete_option('acBank_redirect_url');
    delete_option('acBank_ipn_url');
}

//print_r(get_option('active_plugins'));
if(!in_array('wp-e-commerce/wp-shopping-cart.php',get_option('active_plugins')))
//if(!class_exists( 'wpsc_merchant' ))
//if ( ! file_exists( WP_PLUGIN_DIR.'/wp-e-commerce/wpsc-includes/merchant.class.php' ) )
 {
        add_action( 'admin_notices', 'ecommerce_btc_payment_fallback_notice' );
        $bct_plugin = plugin_dir_path( __FILE__ ).'/alligncommerce_payment.php';
        deactivate_plugins($bct_plugin);
        return;
    }

else{ 
    require_once WP_PLUGIN_DIR.'/wp-e-commerce/wpsc-includes/merchant.class.php';
    $nzshpcrt_gateways[$num] = array(
    'name' => __( 'Aligncommerce Bitcoin Payment', 'wpsc' ),
    'api_version' => 1.0,
    'class_name' => 'wpsc_merchant_acbctpay',
    'has_recurring_billing' => true,
    'display_name' => __( 'Aligncommerce Bitcoin Payment', 'wpsc' ),
    'wp_admin_cannot_cancel' => false,
    'form' => 'form_acBctpay',
    'submit_function' => 'submit_acBctpay',
    'function' => 'checkout_acBctpay',
    'internalname' => 'wpsc_merchant_acbctpay',
    'supported_currencies' => array('USD')
);
/*$image = apply_filters( 'wpsc_merchant_image', '', $nzshpcrt_gateways[$num]['internalname'] );
if ( ! empty( $image ) ) {
    $nzshpcrt_gateways[$num]['image'] = $image;
}   */

/***************Initialize payment gateway class*********/
/*add_action( 'plugins_loaded', 'ac_bct_class_init' );
function ac_bct_class_init()*/  

class wpsc_merchant_acbctpay extends wpsc_merchant {

    var $name = '';

    function __construct( $purchase_id = null, $is_receiving = false ) {
        $this->name = __( 'Aligncommerce Bitcoin Payment', 'wpsc' );
         $this->currency_url='https://api.aligncommerce.com/currency';
        parent::__construct( $purchase_id, $is_receiving );
         global $wpsc_gateways;
      $store_currency_data = WPSC_Countries::get_currency_data( get_option( 'currency_type' ), true );
      //debugbreak();
       $acUser=get_option( 'acBtc_al_username' );
        $acPasword=get_option( 'acBtc_al_password' );
        $allowed_currencies=array();
        if($acUser!='')
        {
      $curl   = curl_init();
            curl_setopt($curl, CURLOPT_URL,$this->currency_url);

            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $acUser.":" . $acPasword);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);


            curl_setopt($curl, CURLOPT_POST, 1);
            //curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            $contents = curl_exec ($curl);
            
            $cur_array = json_decode($contents, true);
            curl_close ($curl);
            
            for($i=0;$i<count($cur_array['data']);$i++)
            {
                $allowed_currencies[]=$cur_array['data'][$i]['code'];
                if($store_currency_data['code']==$cur_array['data'][$i]['code'])
                {
                    update_option( 'ac_currency_id_bit', $cur_array['data'][$i]['currency_id'] );
                }
            }
            
        if(!in_array( $store_currency_data['code'], $allowed_currencies))
        {
            
            $gateways = get_option( 'custom_gateway_options', array() );
            if ( false !== ( $key = array_search( 'wpsc_merchant_acbctpay', $gateways ) ) ) {
            unset( $gateways[ $key ] );
            }

            if ( empty( $gateways ) ) {
                $gateways[] = 'wpsc_merchant_testmode';
            }

            update_option( 'custom_gateway_options', $gateways );
             update_option( 'ac_currency_id_bit','');
         
        }
        }
        else
        {
            update_option( 'ac_currency_id_bit',1); 
        }
    }

}


//*******************Admin setting form for Bitcoin****************************/
function form_acBctpay() {
    $countrylist = WPSC_Countries::get_countries_array( true, true );
    $acCountryAry=array();
    if(get_option('acBtc_al_country')!='')
    {
    $acCountryAry=(get_option('acBtc_al_country'));
    }
    
     global $wpsc_gateways;
     $store_currency_data = WPSC_Countries::get_currency_data( get_option( 'currency_type' ), true );
    if(get_option('ac_currency_id_bit')=='')
    {
        
       $output.='   <tr valign="top">
            <th colspan="2">'.__( 'Gateway Disabled : Align Commerce Bitcoin does not support your store currency OR you entered wrong credentials.', 'wpsc' ).'</th>
           </tr>'; 
    }
    //else
    {
  
    $output.= '
             <tr valign="top">
            <th ><label for="acBtc_description">'.__( 'Description', 'wpsc' ).'</label></th>
            <td ><textarea placeholder="" style="" id="wpsc_options[acBtc_description]" name="wpsc_options[acBtc_description]" type="textarea"  cols="20" rows="3">'.( get_option( 'acBtc_description' ) ).'</textarea>
             </td>
        </tr>
                <tr valign="top">
            <th ><label for="acBtc_api_key">'.__( 'Align Commerce API key', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" value="'.( get_option( 'acBtc_api_key' ) ).'" style="" id="wpsc_options[acBtc_api_key]" name="wpsc_options[acBtc_api_key]" ></td>
        </tr>
                <tr valign="top">
            <th ><label for="acBtc_api_secret">'.__( 'Align Commerce API Secret', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" value="'.( get_option( 'acBtc_api_secret' ) ).'" style="" id="wpsc_options[acBtc_api_secret]" name="wpsc_options[acBtc_api_secret]" ></td>
        </tr>
                <tr valign="top">
            <th >
                <label for="acBtc_al_username">'.__( 'Align Commerce Account Username', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" value="'.( get_option( 'acBtc_al_username' ) ).'" style="" id="wpsc_options[acBtc_al_username]" name="wpsc_options[acBtc_al_username]" ></td>
        </tr>
                <tr valign="top">
            <th >
                <label for="acBtc_al_password">'.__( 'Align Commerce account password', 'wpsc' ).'</label></th>
            <td ><input type="password" placeholder="" value="'.( get_option( 'acBtc_al_password' ) ).'" style="" id="wpsc_options[acBtc_al_password]" name="wpsc_options[acBtc_al_password]" ></td>
        </tr>
        <tr valign="top">
            <th >
                <label for="acBtc_al_password">'.__( 'Enable for country', 'wpsc' ).'</label></th>
            <td ><select name="wpsc_options[acBtc_al_country][]" id="wpsc_options[acBtc_al_country]" multiple="multiple" size="7">';
                                 foreach ( (array)$countrylist as $country ) :
                                        if(in_array($country['isocode'],$acCountryAry)){$sel="selected";}
                                        else{$sel="";}
                                        $output.="<option value='".$country['isocode']."' ".$sel.">".( $country['country'] )."</option>";
                                       
                                 endforeach;
                            $output.='</select></td></tr>';
                            /*<tr valign="top">
            <th ><label for="acBtc_redirect_url">'.__( 'Redirect URL', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" style="" id="wpsc_options[acBtc_redirect_url]" name="wpsc_options[acBtc_redirect_url]" value="'.( get_option( 'acBtc_redirect_url' ) ).'" /></td>
        </tr>
        <tr valign="top">
            <th ><label for="acBtc_ipn_url">'.__( 'IPN URL', 'wpsc' ).'</label></th>
            <td ><input placeholder="" style="" id="wpsc_options[acBtc_ipn_url]" name="wpsc_options[acBtc_ipn_url]" type="text" value="'.( get_option( 'acBtc_ipn_url' ) ).'" />
             </td>
        </tr>' ;        */
    }
    return $output;
}


/***handle checkout process****/
function checkout_acBctpay($seperator, $sessionid)
{
    global $wpsc_gateways, $wpdb,$wpsc_cart;
    $acBctObj=new wpsc_merchant_acbctpay();
    $acTokenurl = 'https://api.aligncommerce.com/oauth/access_token';
    $acClientId=get_option( 'acBtc_api_key' );
    $acClientSecter=get_option( 'acBtc_api_secret' );
    $acUser=get_option( 'acBtc_al_username' );
    $acPasword=get_option( 'acBtc_al_password' );

    $post = array(
        'grant_type' => 'client_credentials',
        'client_id' => $acClientId,
        'client_secret' => $acClientSecter,
        'scope' => 'products,invoice,buyer'
    );
    
    $curl   = curl_init();
    curl_setopt($curl, CURLOPT_URL,$acTokenurl);

    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, $acUser .":" .$acPasword );
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    $contents = curl_exec ($curl);
    $response = json_decode($contents, true);
    $access_token = $response['access_token'];
    curl_close ($curl);
    
    

    //Create invoice
    //This grabs the purchase log id from the database
    //that refers to the $sessionid
    $purchase_log = $wpdb->get_row(
        "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
        "` WHERE `sessionid`= " . $sessionid . " LIMIT 1",
        ARRAY_A);

    //This grabs the users info using the $purchase_log
    // from the previous SQL query
    $usersql = "SELECT `" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.value,
        `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`name`,
        `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`unique_name` FROM
        `" . WPSC_TABLE_CHECKOUT_FORMS . "` LEFT JOIN
        `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` ON
        `" . WPSC_TABLE_CHECKOUT_FORMS . "`.id =
        `" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.`form_id` WHERE
        `" . WPSC_TABLE_SUBMITED_FORM_DATA . "`.`log_id`=" . $purchase_log['id'];

    $userinfo = $wpdb->get_results($usersql, ARRAY_A);

    // convert from awkward format 
    foreach ((array)$userinfo as $value) {
        if (strlen($value['value'])) {
            $ui[$value['unique_name']] = $value['value'];
        }
    }
     
    $userinfo = $ui;
    $acCustomer_details=array(
            'first_name' => $userinfo['billingfirstname'],
            'last_name' => $userinfo['billinglastname'],
            'email' => $userinfo['billingemail'],
            'address_1' => $userinfo['billingaddress'],
            'address_2' => $userinfo['billingcity']);
    
    //debugbreak();
    $productAry=array();
    $i=0;
      //debugbreak();
    $shipping_total=$purchase_log['base_shipping'];
    foreach ( $wpsc_cart->cart_items as $item ) {
      /*  if($i==0){$shipping_total=$purchase_log['base_shipping'];}
        else
        {$shipping_total=0;}*/
        $shipping_total=$shipping_total+$item->shipping;
        $productAry[]=array(
                'product_name' => $item->product_name,
                'product_price' => $item->unit_price ,
                'quantity' => $item->quantity,
                'product_shipping' => 0);
                $i++;
      
    }
   /* if($shipping_total>0)
    {
        $productAry[]= array(
                    'product_name' => 'Total Shipping',
                    'product_price' => 0,
                    'quantity' => 1,
                    'product_shipping' => round($shipping_total,2));
    }
    $tax = wpsc_tax_isincluded() ? 0 : round($item->cart->total_tax,2);
    // $tax=round($item->cart->total_tax,2);
    if($tax>0)
        {
        $productAry[]= array(
                    'product_name' => 'Tax Amount',
                    'product_price' => $tax,
                    'quantity' => 1,
                    'product_shipping' => 0);
        }
    $discount=round($wpsc_cart->coupons_amount,2);
     if($discount>0)
        {
        $productAry[]= array(
                    'product_name' => 'Discount',
                    'product_price' => -($discount),
                    'quantity' => 1,
                    'product_shipping' => 0);
        }*/
       // debugbreak();
        
     $tax = wpsc_tax_isincluded() ? 0 : round($item->cart->total_tax,2);
     $discount=round($wpsc_cart->coupons_amount,2);
     //$countrylist = WPSC_Countries::get_countries_array( true, true );
    
      $store_currency_data = WPSC_Countries::get_currency_data( get_option( 'currency_type' ), true );
      $cur_code=($store_currency_data['code']);  
     $invoice_post =  array(
                'access_token' => $access_token,
                'checkout_type' => 'btc',
                'order_id'=>$purchase_log['id'],
                //'currency'=>$cur_code,
                'currency_id'=>get_option('ac_currency_id_bit'),
                'products' => $productAry,
                'buyer_info' => array(
                           'first_name' => $userinfo['billingfirstname'],
                            'last_name' => $userinfo['billinglastname'],
                            'email' => $userinfo['billingemail'],
                            'address_1' => $userinfo['billingaddress'],
                            'address_2' => '',
                            'address_number' => "",
                            'city' => $userinfo['billingcity'],
                            'state' => $userinfo['billingstate'],
                            'zip' => $userinfo['billingpostcode'],
                            'country' => WPSC_Countries::get_country($userinfo['billingcountry'])->_name,
                            'phone' => $userinfo['billingphone']),
                'shipping' => array(
                          'description' => 'Shipping',
                          'amount' => round($shipping_total,2)
                             ),
                 'shipping_address' => array(
                            'first_name' => $userinfo['shippingfirstname'],
                            'last_name' => $userinfo['shippinglastname'],
                            'email' => $userinfo['billingemail'],
                            'address_1' => $userinfo['shippingaddress'],
                            'address_2' => '',
                            'address_number' => "",
                            'city' => $userinfo['shippingcity'],
                            'state' => $userinfo['shippingstate'],
                            'zip' => $userinfo['shippingpostcode'],
                            'country' => WPSC_Countries::get_country($userinfo['shippingcountry'])->_name,
                            'phone' => $userinfo['billingphone'])
        
    ); 
    if($tax>0)
    {$invoice_post['tax_rate']=array(
                  'description' => 'Tax',
                  //'percent' => '',
                  'amount' => $tax);}
    if($discount>0){
         $invoice_post['discount'] = array(
                          'description' => 'Discount',
                          //'percent_off' => '',
                          'amount_off' => $discount);
    }
   
     $acUser=get_option( 'acBtc_al_username' );
    $acPasword=get_option( 'acBtc_al_password' );
    $acCheckouturl = 'https://api.aligncommerce.com/invoice';

    $curl1   = curl_init($acCheckouturl);
    curl_setopt($curl1, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl1, CURLOPT_USERPWD,  $acUser.":" . $acPasword);
    curl_setopt($curl1, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($curl1, CURLOPT_POST, 1);
    curl_setopt($curl1, CURLOPT_POSTFIELDS, http_build_query($invoice_post));
    curl_setopt($curl1, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl1, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl1, CURLOPT_SSL_VERIFYHOST, 0);
    $contents1 = curl_exec ($curl1);
    $response = json_decode($contents1, true);
    $redirect_url=$response['data']['invoice_url'];
    if($redirect_url!='')
    {
        header("Location:".$redirect_url);
    }
    else
    {
        
       if(is_array($response['error_message']))
         {
             $err_msg=implode("<br>",$response['error_message']);
         }
         else{$err_msg=$response['error_message'];}
        $error_msg = __( $response['status']. " : ".$err_msg, 'wpsc' );
        $acBctObj->set_error_message( $error_msg);
        $acBctObj->return_to_checkout();
    }
    exit();
}

/***recieve response from gateway***********/
//add_action('init', 'nzshpcrt_acBitcoinPay_callback');
function nzshpcrt_acBitcoinPay_callback1()
{
   
   global $wpdb;
     $acBankObj=new wpsc_merchant_acbankpay();
    $acResponse=$_POST; 
     
                      
    if(isset($_GET['acBankCallback']) && $_GET['acBankCallback']==1)
    { 
    $purchase_log = $wpdb->get_row(
            "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
            "` WHERE `id`= " . $acResponse['order_id'] . " LIMIT 1",
            ARRAY_A);
            $sessionid=$purchase_log['sessionid']; 
    if($acResponse['checkout_type']=='bank_transfer')
    { 
  
        switch($acResponse['status'])
        {
            case 'success':
            $data = array(
                'processed'  => 2,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
            transaction_results($sessionid, false, $acResponse['order_id']);
           // $acBankObj->go_to_transaction_results($sessionid);
            break;
            
            case 'fail': // if it fails, delete it
            $data = array(
                'processed'  => 6,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
            transaction_results($sessionid, false, $acResponse['order_id']);
           // $acBankObj->go_to_transaction_results($sessionid);
            break;
            
             case 'cancel':      // need to wait for "Completed" before processing
            $wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array( 'transactid' => $acResponse['order_id'], 'date' => time() ), array( 'sessionid' => $sessionid ), array( '%d', '%s' ) );
           // $acBankObj->go_to_transaction_results($sessionid);
            break;
            
        } 
        
        $transaction_url_with_sessionid = add_query_arg( 'sessionid', $sessionid, get_option( 'transact_url' ) );
        echo $transaction_url_with_sessionid;
       // echo $acBankObj->go_to_transaction_results($sessionid);
        exit;
    }
    if($acResponse['checkout_type']=='btc')
    {
         
        switch($acResponse['status'])
        {
            case 'success':
            $data = array(
                'processed'  => 3,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
            transaction_results($sessionid, false, $acResponse['order_id']);
            $acBankObj->go_to_transaction_results($sessionid);
            break;
            
            case 'fail': // if it fails, delete it
            /*$log_id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`=%s LIMIT 1", $sessionid ) );
            $delete_log_form_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`=%d", $log_id );
            $cart_content = $wpdb->get_results($delete_log_form_sql,ARRAY_A);
            foreach((array)$cart_content as $cart_item)
              {
                  $cart_item_variations = $wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_CART_ITEM_VARIATIONS."` WHERE `cart_id` = %d", $cart_item['id'] ), ARRAY_A);
              }
            $wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`=%d", $log_id ) );
            $wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_SUBMITTED_FORM_DATA."` WHERE `log_id` IN ( %d )", $log_id ) );
            $wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`=%d LIMIT 1", $log_id ) );*/
            $data = array(
                'processed'  => 6,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
            transaction_results($sessionid, false, $acResponse['order_id']);
            $acBankObj->go_to_transaction_results($sessionid);
            break;
            
             case 'cancel':      // need to wait for "Completed" before processing
            $wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array( 'transactid' => $acResponse['order_id'], 'date' => time() ), array( 'sessionid' => $sessionid ), array( '%d', '%s' ) );
            $acBankObj->go_to_transaction_results($sessionid);
            break;
            
        }
        $transaction_url_with_sessionid = add_query_arg( 'sessionid', $sessionid, get_option( 'transact_url' ) );
        echo $transaction_url_with_sessionid;
    }
        
         
    }
}

add_action('wp_footer','availablityCheckACBitcoin');
function availablityCheckACBitcoin()
{
    $acCountryAry="'".implode("','",(get_option('acBtc_al_country')))."'";
    ?>
    <script type="text/javascript">
    var shipCountry=(jQuery( "select[title='shippingcountry']" ). val());
        var billCountry=jQuery( "select[title='billingcountry']" ).val();
        
        var availAry=new Array(<?php echo $acCountryAry;?>) ;
       var isAvailable=(jQuery.inArray(billCountry, availAry));
       var isAvailableShip=(jQuery.inArray(shipCountry, availAry));
       if((isAvailable==-1 && billCountry!='') || (isAvailableShip==-1 && jQuery( "select[title='shippingcountry']" ).is(':visible') && shipCountry!=''))
       {
           jQuery(".wpsc_merchant_acbctpay").hide();
       }
       else
       {
          jQuery(".wpsc_merchant_acbctpay").show(); 
       }
    jQuery( ".wpsc-country-dropdown" ).change( function(){
        var shipCountry=(jQuery( "select[title='shippingcountry']" ). val());
        var billCountry=jQuery( ".wpsc-country-dropdown" ).val();
        var availAry=new Array(<?php echo $acCountryAry;?>) ;
       var isAvailable=(jQuery.inArray(billCountry, availAry));
       var isAvailableShip=(jQuery.inArray(shipCountry, availAry));
       if((isAvailable==-1 && billCountry!='') || (isAvailableShip==-1  && jQuery( "select[title='shippingcountry']" ).is(':visible') && shipCountry!=''))
       {
           jQuery(".wpsc_merchant_acbctpay").hide();
       }
       else
       {
          jQuery(".wpsc_merchant_acbctpay").show(); 
       }
        
    });
    </script>
<?php }



include(plugin_dir_path( __FILE__ ).'alligncommerce_bank_transfer_pay.php');
}
