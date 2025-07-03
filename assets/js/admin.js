jQuery(function($){
    $('.unified-sso-wrap .add-app-btn').on('click', function(e){
        e.preventDefault();
        $('.unified-sso-wrap .modal').show();
    });
    $('.unified-sso-wrap .close-modal').on('click', function(e){
        e.preventDefault();
        $('.unified-sso-wrap .modal').hide();
    });
});
