<div id="submitdiv" class="stuffbox">
    <h3 class="hndle">
        <span><?php _e('Payments History', 'gfsr-gravity-forms-square'); ?></span>
    </h3>

    <div class="inside">
        <div id="submitcomment" class="submitbox">

            <table class="widefat">
                <thead>
                    <tr><th><?php _e('Date', 'gfsr-gravity-forms-square'); ?></th><th><?php _e('Transaction ID', 'gfsr-gravity-forms-square'); ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr><td><?php echo esc_attr($transaction->created_at); ?></td><td><?php echo esc_attr($transaction->transaction_id); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>