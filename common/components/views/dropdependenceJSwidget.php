<?php
use yii\web\View;

$this->registerJs("
    (function(){
        var select = $('#{$args['depend']}');
        var buildOptions = function(options) {
        if (typeof options === 'object') {
        select.children('option').remove();
        $('<option />')
        .appendTo(select)
        .html('{$args['msg']}')
        $.each(options, function(index, option) {
        $('<option />', {value:index})
        .appendTo(select)
        .html(option);
        });
        }
        };
    var dropdownOnChange = function({$args['independ']}){
    $.ajax({
        dataType: 'json',
        url: '" . $args['url'] . "?id=' + {$args['independ']} ,
        success: buildOptions
    });
    };
    
    window.dropdownOnChange = dropdownOnChange;
    window.buildOptions = buildOptions;

})();
", View::POS_READY);
?>