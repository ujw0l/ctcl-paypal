<?php
/*
 Plugin Name:CTCL Paypal
 Plugin URI : 
 Description: CT commerce lite paypal payments addon
 Version: 1.0.0
 Author: Ujwol Bastakoti
 Author URI:https://ujw0l.github.io/
 Text Domain:  ctcl-paypal
 License: GPLv2
*/
if(class_exists('ctclBillings')){


    class ctclPaypal extends ctclBillings{

    /**
     * Payment id
     */
    public $paymentId = 'ctcl_paypal';

    /**
     * Payment name
     */
    public $paymentName;

    /**
     * Setting Fields
     */
    public $settingFields = 'ctcl_paypal_setting';

    /**
     * Stripe file path
     */
    public $paypalFilePath;

    public function __construct(){
        $this->paypalFilePath = plugin_dir_url(__FILE__);
        $this->paymentName = 'Paypal';

      
        self::displayOptionsUser();
        self::adminPanelHtml();
        self::registerOptions();
        self::requiredWpAction();
        add_filter('ctcl_process_payment_'.$this->paymentId ,array($this,'processPayment'));
        register_deactivation_hook(__FILE__,  array($this,'paypalDeactivate'));
        
    }



/**
 * Run on deactivation 
 */
public function paypalDeactivate(){

   delete_option('ctcl_activate_paypal');
   delete_option('ctcl_paypal_client-id');
}


    /**
     * Register form options
     */
public function registerOptions(){

    register_setting($this->settingFields,'ctcl_activate_paypal');
    register_setting($this->settingFields,'ctcl_paypal_client-id');
    

}
/**
 * 
 */
public function displayOptionsUser(){

    if('1'== get_option('ctcl_activate_paypal')):
        add_filter('ctcl_payment_options',function($val){
            array_push($val,array(
                                    'id'=>$this->paymentId,
                                    'name'=>$this->paymentName,
                                    'html'=>$this->frontendHtml(),
            ));
            return $val; 
        },10,1);
    endif;

}

/**
 * Required wp actions
 */
public function requiredWpAction(){
    add_action( 'wp_enqueue_scripts', array($this,'enequeFrontendJs' ));
}
/**
   * Eneque frontend JS files
   */

  public function enequeFrontendJs(){
    if('1'== get_option('ctcl_activate_paypal')):
        wp_enqueue_script('ctclPaypal','https://www.paypal.com/sdk/js?client-id='.get_option("ctcl_paypal_client-id").'&currency='.get_option('ctcl_currency'), '', null);
         wp_enqueue_script('ctclPaypalJs', "{$this->paypalFilePath}js/paypal.js",array('ctclPaypal'));
    endif;    
}




      /**
     * Create admin panel content
     */
    public function adminPanelHtml(){

        add_filter('ctcl_admin_billings_html',function($val){
            $activate =  '1'=== get_option('ctcl_activate_paypal')? 'checked':'';
            $clientId = !empty(get_option('ctcl_paypal_client-id'))? get_option('ctcl_paypal_client-id'):'';
;

            $html = '<div class="ctcl-content-display ctcl-stripe-settings">';
            $html .=  '<div class="ctcl-business-setting-row"><label for"ctcl-activate-paypal"  class="ctcl-activate-paypal-label">'.__('Activate Paypal :','ctcl-paypal').'</label>';
            $html .= "<span><input id='ctcl-activate-paypal' {$activate} type='checkbox' name='ctcl_activate_paypal' value='1'></span></div>";

           

            $html .=  '<div class="ctcl-business-setting-row"><label for"ctc-paypal-client-id"  class="ctc-paypal-client-id-label">'.__('Client Id : ','ctcl-paypal').'</label>';
            $html .= "<span><input id='ctc-paypal-client-id' type='text' name='ctcl_paypal_client-id' value='{$clientId}'></span></div>";
                    

            $html .= '</div>';
            array_push($val,array(
                                    'settingFields'=>$this->settingFields,
                                    'formHeader'=>__("Paypal Payment",'ctcl-paypal'),
                                    'formSetting'=>'ctcl_payment_setting',
                                    'html'=>$html
                                 )
                                );
      return $val;
        },40,1);
    }



    /**
      * html for frontend
      */
      public function frontendHtml(){
      return '<div id="paypal-button-container"></div> <br/><i style="color:red;display:none;" id="ctcl-paypal-error">'.__('An error occurred during the payment process').'</i>';
      }

    }

    
    new ctclPaypal();


}else{

add_thickbox();
/**
 * If main plugin CTC lite is not installed
 */
 add_action( 'admin_notices', function(){
     echo '<div class="notice notice-error is-dismissible"><p>';
     esc_html_e( 'CTCL Paypalplugin requires CTC Lite plugin installed and activated to work, please do so first.', 'ctcl-paypal' );
      echo esc_html('<a href="'.admin_url('plugin-install.php').'?tab=plugin-information&plugin=ctc-lite&TB_iframe=true&width=640&height=500" class="thickbox">'.__('Click Here to install it','ctcl-paypal')).' </a>'; 
     echo '</p></div>';
 } );
}
?>