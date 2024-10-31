<?php
/**
 *
 */
?>
<div id="submitdiv" class="stuffbox">
    <h3 class="hndle">
        <span><?php _e('Square', 'gfsr-gravity-forms-square'); ?></span>
    </h3>

    <div class="inside">
        <div id="submitcomment" class="submitbox">
            <div id="minor-publishing" style="padding:10px;">
                <?php
                if (!empty($entry['payment_status'])) {
                    $entry_id = $entry['id'];
                    $mode = gform_get_meta( $entry['id'], 'payment_mode' );
                    if ( "test"===$mode ) {
                        $square_url = "https://squareupsandbox.com";
                    } else {
                        $square_url = "https://squareup.com";
                    }
                    ?> 
                    <p><strong><?php _e('Payment Mode', 'gfsr-gravity-forms-square'); ?>: </strong><?php echo esc_attr($mode); ?></p>                    
                    <p><strong><?php _e('Status', 'gfsr-gravity-forms-square'); ?>: </strong><?php echo esc_attr($entry['payment_status']); ?></p>
                    <p><strong><?php _e('Transaction ID', 'gfsr-gravity-forms-square'); ?>: </strong><?php echo esc_attr($entry['transaction_id']); ?></p>
                    <p><strong><?php _e('Amount', 'gfsr-gravity-forms-square'); ?>: </strong><?php echo GFCommon::to_money($entry['payment_amount'], $entry['currency']) ?></p>
                    <p><a class="button-secondary" target="_blank" href="<?php echo esc_html($square_url); ?>/dashboard/sales/transactions/<?php echo esc_attr($entry['transaction_id']); ?>/"><?php _e('Click to view transaction in dashboard', 'gfsr-gravity-forms-square' ); ?></a></p>
                    
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>