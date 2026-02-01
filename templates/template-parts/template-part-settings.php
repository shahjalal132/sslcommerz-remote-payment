<?php

$store_id = get_option( 'sslcommerz_store_id', '' );
$store_password = get_option( 'sslcommerz_store_password', '' );
$is_sandbox = get_option( 'sslcommerz_is_sandbox', true );
$encryption_key = get_option( 'sslcommerz_encryption_key', '' );
$laravel_success_url = get_option( 'sslcommerz_laravel_success_url', '' );
$laravel_fail_url = get_option( 'sslcommerz_laravel_fail_url', '' );

?>

<h4 class="common-title">SSLCommerz Settings</h4>

<div class="credentials-wrapper overflow-hidden">
    <div class="common-input-group">
        <label for="sslcommerz_store_id">SSLCommerz Store ID <span style="color: red;">*</span></label>
        <input type="text" class="common-form-input" name="sslcommerz_store_id" id="sslcommerz_store_id" 
            placeholder="Enter your SSLCommerz Store ID" value="<?php echo esc_attr( $store_id ); ?>" required>
    </div>

    <div class="common-input-group mt-20">
        <label for="sslcommerz_store_password">SSLCommerz Store Password <span style="color: red;">*</span></label>
        <input type="password" class="common-form-input" name="sslcommerz_store_password" id="sslcommerz_store_password" 
            placeholder="Enter your SSLCommerz Store Password" value="<?php echo esc_attr( $store_password ); ?>" required>
    </div>

    <div class="common-input-group mt-20">
        <label for="sslcommerz_is_sandbox">
            <input type="checkbox" name="sslcommerz_is_sandbox" id="sslcommerz_is_sandbox" 
                value="1" <?php checked( $is_sandbox, true ); ?>>
            Enable Sandbox Mode
        </label>
        <small style="display: block; color: #666; margin-top: 5px;">Check this to use SSLCommerz sandbox environment for testing</small>
    </div>

    <div class="common-input-group mt-20">
        <label for="sslcommerz_encryption_key">Encryption Key <span style="color: red;">*</span></label>
        <input type="password" class="common-form-input" name="sslcommerz_encryption_key" id="sslcommerz_encryption_key" 
            placeholder="Enter encryption key (shared with Laravel)" value="<?php echo esc_attr( $encryption_key ); ?>" required>
        <small style="display: block; color: #666; margin-top: 5px;">This key must match the REMOTE_PAYMENT_ENCRYPTION_KEY in your Laravel application</small>
    </div>

    <div class="common-input-group mt-20">
        <label for="sslcommerz_laravel_success_url">Laravel Success URL</label>
        <input type="url" class="common-form-input" name="sslcommerz_laravel_success_url" id="sslcommerz_laravel_success_url" 
            placeholder="https://your-laravel-app.com/payment/remote-success" value="<?php echo esc_url( $laravel_success_url ); ?>">
        <small style="display: block; color: #666; margin-top: 5px;">URL where users will be redirected after successful payment</small>
    </div>

    <div class="common-input-group mt-20">
        <label for="sslcommerz_laravel_fail_url">Laravel Fail URL</label>
        <input type="url" class="common-form-input" name="sslcommerz_laravel_fail_url" id="sslcommerz_laravel_fail_url" 
            placeholder="https://your-laravel-app.com/payment/remote-fail" value="<?php echo esc_url( $laravel_fail_url ); ?>">
        <small style="display: block; color: #666; margin-top: 5px;">URL where users will be redirected after failed or cancelled payment</small>
    </div>

    <button type="button" class="save-btn mt-20 button-flex" id="save_sslcommerz_settings">
        <span>Save</span>
        <span class="spinner-loader-wrapper"></span>
    </button>
</div>