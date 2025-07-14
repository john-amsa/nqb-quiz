jQuery(document).ready(function($) {
    $('#submit-button').click(function() {

        var systems = [];
        $('input[name="systems"]:checked').each(function() {
            systems.push($(this).val());
        });

        var difficulty = [];
        $('input[name="difficulty"]:checked').each(function() {
            difficulty.push($(this).val());
        });

        var formData = {
            difficulty: difficulty,
            type: $('#type').val(),
            systems: systems,
            size: $( '#size').val()
        };
        
        $.post(uf_ajax.ajax_url, {
            action: 'handle_form',
            formData: JSON.stringify(formData)
        }, function(response) {
            var data = JSON.parse(response);
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert(data.message);
                }

        });
    });
});
