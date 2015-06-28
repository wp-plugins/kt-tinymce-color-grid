(function ($) {
    $(function () {
        var $document = $(document);
        var $Checkbox = $('#checkbox_custom');
        var $Colors = $('#custom_colors');
        var $Add = $('#add_custom_color');
        var $Blueprint = $(kt_TinyMCE_blueprint.replace('%1$s', '#000000').replace('%2$s', ''));

        $Checkbox.on('click', function () {
            $Colors.toggle($Checkbox.is(':checked'));
        });
        $Add.on('click', function () {
            initPicker($Blueprint.clone().insertBefore($Add));
        });
        $Colors.on('click', '.dashicons-no', function () {
            $(this).parent().remove();
        });

        var initPicker = function ($this) {
            var $button = $this.children('.picker');
            var $preview = $button.children('.preview');
            var $hex = $this.children('.hex');
            var $name = $this.children('.name');
            var $picker = $this.children('.farbtastic');
            var autoHide = function (e) {
                if (!$(e.target).closest($this[0]).length) {
                    hidePicker();
                }
            };
            var showPicker = function (e) {
                if (e.type == 'focus' || (e.type == 'mousedown' && e.which == 1)) {
                    $picker.show();
                    $document.on('mousedown', autoHide);
                }
            };
            var togglePicker = function (e) {
                $picker.is(':hidden') ? showPicker(e) : hidePicker();
            };
            var hidePicker = function () {
                $picker.hide();
                $document.off('mousedown', autoHide);
            };
            var updatePicker = function (color) {
                $preview.css('backgroundColor', color);
                $hex.val(color.toUpperCase());
            };
            var fb = $.farbtastic($picker, updatePicker);
            $hex.on('keyup', fb.updateValue);
            fb.setColor($hex.val());

            $button.on('mousedown', togglePicker);
            $hex.on('focus', showPicker).on('blur', hidePicker);
        };

        $Colors.children('.color').each(function () {
            initPicker($(this));
        });
    });
})(jQuery);