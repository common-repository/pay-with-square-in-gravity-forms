jQuery(function(){
    jQuery('.notice-dismiss').on('click', function(e) {
        e.preventDefault();
        var id = jQuery(this).parent('.notice.is-dismissible').attr('id');
        if ( typeof id !== 'undefined' || id !== null ) {
            //alert('id of the parent is ' + id);

            jQuery.ajax({
                url: gfsqsadmin.ajaxUrl,
                type: "POST",
                dataType: "json",
                data: {
                    action: 'gfsqs_hide_notifications',
                    id: id
                },
                success: function (response) { 
                    
                    if ( response.status == 'SUCCESS') { //if success
                        console.log('notice will permanently hide until deleted from database.');
                    }

                    if ( response.status == 'FAILED') { //if success
                        console.log('notice will not permanently hide due to some error!');
                    }
                }
            });
        } 
    });
});