<?php
/*
Bank Transfer / Payin Local Align Commerce Payment Gateway
**/
 $val=time();
$nzshpcrt_gateways[$val] = array(
    'name' => __( 'Align Commerce Bank Transfer Payment', 'wpsc' ),
    'api_version' => 1.1,
    'class_name' => 'wpsc_merchant_acbankpay',
    'has_recurring_billing' => true,
    'display_name' => __( 'Align Commerce Bank Transfer Payment', 'wpsc' ),
    'wp_admin_cannot_cancel' => false,
    'form' => 'form_acBankpay',
    'submit_function'=>'submit_acBank',
    'function' => 'checkout_acbanck',
    'internalname' => 'wpsc_merchant_acbankpay'
);


/***************Initialize payment gateway class*********/
//add_action( 'plugins_loaded',  array( 'wpsc_merchant_acbankpay', 'init' ));
//function ac_bank_class_init()
//{
class wpsc_merchant_acbankpay extends wpsc_merchant {

    var $name = '';
        
    function __construct( $purchase_id = null, $is_receiving = false ) {
       // debugbreak();
        $this->name = __( 'Align Commerce Bank Transfer Payment', 'wpsc' );
         $this->currency_url='https://api.aligncommerce.com/currency';
        parent::__construct( $purchase_id, $is_receiving );
        global $wpsc_gateways;
        $store_currency_data = WPSC_Countries::get_currency_data( get_option( 'currency_type' ), true );
        $acUser=get_option( 'acBank_al_username' );
        $acPasword=get_option( 'acBank_al_password' );
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
                update_option( 'ac_currency_id_bank', $cur_array['data'][$i]['currency_id'] );
                
            }
        }
        
        if(!in_array( $store_currency_data['code'], $allowed_currencies))
        {
            $gateways = get_option( 'custom_gateway_options', array() );
            if ( false !== ( $key = array_search( 'wpsc_merchant_acbankpay', $gateways ) ) ) {
            unset( $gateways[ $key ] );
            }

            if ( empty( $gateways ) ) {
                $gateways[] = 'wpsc_merchant_testmode';
            }

            update_option( 'custom_gateway_options', $gateways );
             update_option( 'ac_currency_id_bank','');
        } 
        }
        else
        {
            update_option( 'ac_currency_id_bank',1); 
        }
     
    }
    
    
    function acCustomerDetails()
    {
        echo $this->cart_data['billing_address']['first_name'];
    }
    
    
}
//}

/***handle checkout process****/
function checkout_acbanck($seperator, $sessionid)
{
    global $wpsc_gateways, $wpdb,$wpsc_cart;
    $acBankObj=new wpsc_merchant_acbankpay();
    $acTokenurl = 'https://api.aligncommerce.com/oauth/access_token';
    $acClientId=get_option( 'acBank_api_key' );
    $acClientSecter=get_option( 'acBank_api_secret' );
    $acUser=get_option( 'acBank_al_username' );
    $acPasword=get_option( 'acBank_al_password' );

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
    
   
    $productAry=array();
    $i=0;
  
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
                'checkout_type' => 'bank_transfer',
                'order_id'=>$purchase_log['id'],
                //'currency'=>$cur_code,
                'currency_id'=>get_option('ac_currency_id_bank'),
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
             
            
    
    $acUser=get_option( 'acBank_al_username' );
    $acPasword=get_option( 'acBank_al_password' );
    
 
     
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
   /* $file = $_SERVER['DOCUMENT_ROOT']."/wordpress_eac/response_ac.log";
     $current = file_get_contents($file);
      
        file_put_contents($file, json_encode($response, true));*/
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
        $acBankObj->set_error_message( $error_msg);
        $acBankObj->return_to_checkout();
    }
    exit();
}

//*******************Admin setting form for bank transfer****************************/
function form_acBankpay() {
    $countrylist = WPSC_Countries::get_countries_array( true, true );
    $acCountryAry=array();
    if(get_option('acBank_al_country')!='')
    {
    $acCountryAry=(get_option('acBank_al_country'));
    }
   
    global $wpsc_gateways;
    
    $store_currency_data = WPSC_Countries::get_currency_data( get_option( 'currency_type' ), true );
    if(get_option('ac_currency_id_bank')=='')
    {
       $output.='   <tr valign="top">
            <th colspan="2">'.__( 'Gateway Disabled : Align Commerce Bank Transfer does not support your store currency OR you entered wrong credentials.', 'wpsc' ).'</th>
           </tr>'; 
           
    }
   // else
    {
    $output .= '
             <tr valign="top">
            <th ><label for="acBank_description">'.__( 'Description', 'wpsc' ).'</label></th>
            <td ><textarea placeholder="" style="" id="wpsc_options[acBank_description]" name="wpsc_options[acBank_description]" type="textarea"  cols="20" rows="3">'.( get_option( 'acBank_description' ) ).'</textarea>
             </td>
        </tr>
        <tr valign="top">
            <th ><label for="acBank_api_key">'.__( 'Align Commerce API key', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" value="'.( get_option( 'acBank_api_key' ) ).'" style="" id="wpsc_options[acBank_api_key]" name="wpsc_options[acBank_api_key]" ></td>
        </tr>
                <tr valign="top">
            <th ><label for="acBank_api_secret">'.__( 'Align Commerce API Secret', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" value="'.( get_option( 'acBank_api_secret' ) ).'" style="" id="wpsc_options[acBank_api_secret]" name="wpsc_options[acBank_api_secret]" ></td>
        </tr>
                <tr valign="top">
            <th >
                <label for="acBank_al_username">'.__( 'Align Commerce Account Username', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" value="'.( get_option( 'acBank_al_username' ) ).'" style="" id="wpsc_options[acBank_al_username]" name="wpsc_options[acBank_al_username]" ></td>
        </tr>
                <tr valign="top">
            <th >
                <label for="acBank_al_password">'.__( 'Align Commerce account password', 'wpsc' ).'</label></th>
            <td ><input type="password" placeholder="" value="'.( get_option( 'acBank_al_password' ) ).'" style="" id="wpsc_options[acBank_al_password]" name="wpsc_options[acBank_al_password]" ></td>
        </tr><tr valign="top">
            <th >
                <label for="acBank_al_country">'.__( 'Enable for country', 'wpsc' ).'</label></th>
            <td ><select name="wpsc_options[acBank_al_country][]" id="wpsc_options[acBank_al_country]" multiple="multiple" size="7">';
                                 foreach ( (array)$countrylist as $country ) :
                                        if(in_array($country['isocode'],$acCountryAry) && is_array($acCountryAry)){$sel="selected";}
                                        else{$sel="";}
                                        $output.="<option value='".$country['isocode']."' ".$sel.">".( $country['country'] )."</option>";
                                       
                                 endforeach;
                            $output.='</select></td></tr>';
        /*                    <tr valign="top">
            <th ><label for="acBank_redirect_url">'.__( 'Redirect URL', 'wpsc' ).'</label></th>
            <td ><input type="text" placeholder="" style="" id="wpsc_options[acBank_redirect_url]" name="wpsc_options[acBank_redirect_url]" value="'.( get_option( 'acBank_redirect_url' ) ).'" /></td>
        </tr>
        <tr valign="top">
            <th ><label for="acBank_ipn_url">'.__( 'IPN URL', 'wpsc' ).'</label></th>
            <td ><input placeholder="" style="" id="wpsc_options[acBank_ipn_url]" name="wpsc_options[acBank_ipn_url]" type="text" value="'.( get_option( 'acBank_ipn_url' ) ).'" />
             </td>
        </tr>' ;*/
    }
    return $output;
}

/***recieve response from gateway***********/
add_action('init', 'nzshpcrt_acBankpay_callback');
function nzshpcrt_acBankpay_callback($sessionid)
{
   
     global $wpdb;
     $acBankObj=new wpsc_merchant_acbankpay();
    $acResponse=$_POST; 
     //$acResponse=array('checkout_type'=>'btc','status'=>'cancel','order_id'=>'164');
                      
    if(isset($_GET['acBankCallback']) && $_GET['acBankCallback']==1)
    { 
    $purchase_log = $wpdb->get_row(
            "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
            "` WHERE `id`= " . $acResponse['order_id'] . " LIMIT 1",
            ARRAY_A);
            $sessionid=$purchase_log['sessionid']; 
    if($acResponse['checkout_type']=='bank_transfer')
    { 
         /*
         1-Incomplete Sale
         2-Order Received
         3-Accepted Payment
         4-Job Dispatched
         5-Closed Order
         6-Payment Declined
         */
        switch($acResponse['status'])
        {
            case 'success':
            $data = array(
                'processed'  => 3,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
             delete_post_meta($order_id, 'ac_fail_message');             
            //transaction_results($sessionid, false, $acResponse['order_id']);
            //$acBankObj->go_to_transaction_results($sessionid);
            break;
            
            case 'fail': 
            $data = array(
                'processed'  => 6,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
            update_post_meta($acResponse['order_id'], 'ac_fail_message',$acResponse['status_message']);
            break;
            
            case 'processing':
            $data = array(
                'processed'  => 1,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
             wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' ); 
              delete_post_meta($order_id, 'ac_fail_message');
            //$wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array( 'transactid' => $acResponse['order_id'], 'date' => time() ), array( 'sessionid' => $sessionid ), array( '%d', '%s' ) );
            break;
            
            case 'refund': 
            $data = array(
                'processed'  => 5,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
             delete_post_meta($order_id, 'ac_fail_message');
            break;
            
            case 'cancel':      // need to wait for "Completed" before processing
            $data = array(
                'processed'  => 1,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
             wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
             update_post_meta($acResponse['order_id'], 'ac_fail_message', $acResponse['status_message']);
            //$wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array( 'transactid' => $acResponse['order_id'], 'date' => time() ), array( 'sessionid' => $sessionid ), array( '%d', '%s' ) );
            break;
            
        }
        
        if($acResponse['status']=='success' || $acResponse['status']=='processing' || $acResponse['status']=='refund')
        {
            $transaction_url_with_sessionid = add_query_arg( 'sessionid', $sessionid, get_option( 'transact_url' ) );
        }
        else
        {
            $transaction_url_with_sessionid =  get_option( 'transact_url' );
        }
        echo $transaction_url_with_sessionid;
       // echo add_query_arg( 'sessionid', $sessionid, get_option( 'transact_url' ) );
       
        exit;
    }
    if($acResponse['checkout_type']=='btc')
    {
         
        /*
         1-Incomplete Sale
         2-Order Received
         3-Accepted Payment
         4-Job Dispatched
         5-Closed Order
         6-Payment Declined
         */
        switch($acResponse['status'])
        {
            case 'success':
            $data = array(
                'processed'  => 3,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
             delete_post_meta($order_id, 'ac_fail_message');             
            //transaction_results($sessionid, false, $acResponse['order_id']);
            //$acBankObj->go_to_transaction_results($sessionid);
            break;
            
            case 'fail': 
            $data = array(
                'processed'  => 6,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
            update_post_meta($acResponse['order_id'], 'ac_fail_message',$acResponse['status_message']);
            break;
            
            case 'processing':
            $data = array(
                'processed'  => 1,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
             wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' ); 
              delete_post_meta($order_id, 'ac_fail_message');
            //$wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array( 'transactid' => $acResponse['order_id'], 'date' => time() ), array( 'sessionid' => $sessionid ), array( '%d', '%s' ) );
            break;
            
            case 'refund': 
            $data = array(
                'processed'  => 5,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
            wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
             delete_post_meta($order_id, 'ac_fail_message');
            break;
            
            case 'cancel':      // need to wait for "Completed" before processing
            $data = array(
                'processed'  => 1,
                'transactid' => $acResponse['order_id'],
                'date'       => time(),
            );
             wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
             update_post_meta($acResponse['order_id'], 'ac_fail_message', $acResponse['status_message']);
            //$wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array( 'transactid' => $acResponse['order_id'], 'date' => time() ), array( 'sessionid' => $sessionid ), array( '%d', '%s' ) );
            break;
            
        }
        if($acResponse['status']=='success' || $acResponse['status']=='processing' || $acResponse['status']=='refund')
        {
            $transaction_url_with_sessionid = add_query_arg( 'sessionid', $sessionid, get_option( 'transact_url' ) );
        }
        else
        {
            $transaction_url_with_sessionid =  get_option( 'transact_url' );
        }
        echo $transaction_url_with_sessionid;
       // wp_redirect($transaction_url_with_sessionid);
        exit;
    }
        
    exit;     
    }
}

add_action('wp_footer','availablityCheckACBankPay');
function availablityCheckACBankPay()
{
    $acCountryAryBank="'".implode("','",(get_option('acBank_al_country')))."'";
    ?>
    <script type="text/javascript">
    var shipCountryBank=(jQuery( "select[title='shippingcountry']" ). val());
        var billCountryBank=jQuery( "select[title='billingcountry']" ).val();
        var availAryBank=new Array(<?php echo $acCountryAryBank;?>) ;
       var isAvailableBank=(jQuery.inArray(billCountryBank, availAryBank));
       var isAvailableShipBank=(jQuery.inArray(shipCountryBank, availAryBank));
       if((isAvailableBank==-1 && billCountryBank!='') || (isAvailableShipBank==-1 && jQuery( "select[title='shippingcountry']" ).is(':visible') && shipCountryBank!=''))
       {
           jQuery(".wpsc_merchant_acbankpay").hide();
       }
       else
       {
          jQuery(".wpsc_merchant_acbankpay").show(); 
       }
    jQuery( ".wpsc-country-dropdown" ).change( function(){
         var shipCountryBank=(jQuery( "select[title='shippingcountry']" ). val());
        var billCountryBank=jQuery( ".wpsc-country-dropdown" ).val();
        var availAryBank=new Array(<?php echo $acCountryAryBank;?>) ;
       var isAvailableBank=(jQuery.inArray(billCountryBank, availAryBank));
       var isAvailableShipBank=(jQuery.inArray(shipCountryBank, availAryBank));
       if((isAvailableBank==-1 && billCountryBank!='') || (isAvailableShipBank==-1 && jQuery( "select[title='shippingcountry']" ).is(':visible') && shipCountryBank!=''))
       {
           jQuery(".wpsc_merchant_acbankpay").hide();
       }
       else
       {
          jQuery(".wpsc_merchant_acbankpay").show(); 
       }
        
    });
    </script>
<?php }

add_action('wpsc_user_log_after_order_status','show_ac_order_status_message');
function show_ac_order_status_message($purchase)
{
    
   $post_id=$purchase['id'];
    $ac_fail_msg=get_post_meta( $post_id, 'ac_fail_message' );
    if($ac_fail_msg)
    {
        echo '<p class="order-info"><b>'.__('Order Note','woocommerce').' : </b> '.$ac_fail_msg[0].'<p>';
    }
}

add_action('wpsc_shipping_details_bottom','admin_ac_status');
function admin_ac_status()
{
    
   $post_id=$_REQUEST['id'];
    $ac_fail_msg=get_post_meta( $post_id, 'ac_fail_message' );
    if($ac_fail_msg)
    {
        ?>
        <div class="metabox-holder">
                <div class="postbox" id="purchlogs_notes">
                    <h3 class="hndle"><?php echo __('Order Status Note','wpsc');?></h3>
                    <div class="inside">
                     <?php echo $ac_fail_msg[0];?>   
                    </div>
                </div>
            </div>
            <?php 
    } 
}
?>