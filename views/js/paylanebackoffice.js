$(document).ready(function () {

    $('.paylane-tabs nav .tab-title').click(function () {
        var elem = $(this);
        var target = $(elem.data('target'));
        elem.addClass('active').siblings().removeClass('active');
        target.show().siblings().hide();
    })

    if ($('.paylane-tabs nav .tab-title.active').length == 0) {
        $('.paylane-tabs nav .tab-title:first').trigger("click");
    }

    $('[data-toggle="tooltip"]').tooltip();

});








