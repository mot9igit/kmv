var kmv = {
    options: {
        live_form: ".kmv_live_form",
        message: ".kmv_message",
        logout: ".kmv_logout"
    },
    initialize: function(){
        $(document).on('submit', kmv.options.live_form, function(e){
            e.preventDefault();
            $(this).find(".dart-btn").addClass('dart-btn-loading').attr("disabled", "disabled");
            var data = $(this).serialize();
            kmv.send(data).then(() => {
                $(this).find(".dart-btn").removeClass('dart-btn-loading').removeAttr("disabled");
            });
        });
        $(document).on('click', kmv.options.logout, function(e) {
            e.preventDefault();
            var data = {
                kmv_action: "profile/logout"
            };
            kmv.send(data);
        });
    },
    send: async function(data){
        let response = await $.ajax({
            type: "POST",
            url: kmvConfig['actionUrl'],
            dataType: 'json',
            data: data,
            success:  function(data_r) {
                console.log(data_r);
                if(typeof data_r.data.error !== "undefined"){
                    $(".kmv-message").show().addClass("kmv-error");
                    $(".kmv-message").html(data_r.data.error);
                }
                if(typeof data_r.data.redirect !== "undefined"){
                    location.href = data_r.data.redirect
                }
            }
        });
        console.log(response);
    },
    join: function (arr /*, separator */) {
        var separator = arguments.length > 1 ? arguments[1] : ", ";
        return arr.filter(function(n){return n}).join(separator);
    }
}

$(document).ready(function() {
    kmv.initialize();
});