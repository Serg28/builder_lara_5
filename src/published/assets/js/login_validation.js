jQuery(function() {
    jQuery("#login-form").validate({
            rules : {
                email : {
                    required : true,
                    email : true
                },
                password : {
                    required : true,
                    minlength : 6,
                    maxlength : 20
                }
            },
            messages : {
                email : {
                    required : $("[name=email]").attr("email_required"),
                    email : $("[name=email]").attr("email_email")
            },
            password : {
                    required : $("[name=password]").attr("password_required")
            }
        },
        errorPlacement : function(error, element) {
        error.insertAfter(element.parent());
    }
});
});