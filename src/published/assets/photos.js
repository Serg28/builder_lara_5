'use strict';

var Photo = {

    getFormAddAlbum : function()
    {
        $("#popup_add_album").modal('show').css("top", $(window).scrollTop());

        var $checkoutForm = $('#popup_add_album #create_form').validate({
            rules : {
                title : {
                    required : true
                },

            },
            messages : {
                title : {
                    required : 'Нужно заполнить название альбома'
                },

            },
            errorPlacement : function(error, element) {
                error.insertAfter(element.parent());
            },
            submitHandler: function(form) {
                Photo.doSaveAlbum();
            }
        });
    }, //end getFormAddAlbum

    doOpenAlbum : function(idAlbum)
    {
        var urlPage = "?id_album=" + idAlbum;
        window.history.pushState(urlPage, '', urlPage);
        doAjaxLoadContent("/admin/photos/gallery" + urlPage);
    }, //end doOpenAlbum

    doSaveAlbum : function()
    {
       var data = $('#popup_add_album form').serialize();
       $.post("/admin/photos/save_album", {data : data },
           function(data){
                if (data.status == "success") {
                    $("#popup_add_album").modal('hide');
                    doAjaxLoadContent(location.href);
                }
       }, "json");
    } //end doSaveAlbum
};

