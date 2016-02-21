var jcrop_api;

(function ($) {
    $.fn.cropper = function (options, width, height) {
        var $widget = getWidget(this);
        var $progress = $widget.find('.progress');
        var buttons = [
            $widget.find('.cropper_label'),
            $widget.find('.upload_new_photo'),
        ];

        var settings = $.extend({
            button: buttons,
            dropzone: $widget.find('.cropper_label'),
            responseType: "json",
            noParams: true,
            multipart: true,
            autoSubmit: false,
            debug: true,
            maxSize: options['maxSize'],
            onChange: function (filename, extension, size) {
                this.clearQueue();
                $progress.removeClass('hidden').find('.progress-bar').css({'width': '55%'});
                if (size > options['maxSize']) {
                    $widget.parents('.form-group').addClass('has-error').find('.help-block').text(options['size_error_text']);
                    $progress.addClass('hidden');
                    return false;
                }

                if (!inArray(options['allowedExtensions'], extension.toLowerCase())) {
                    showError($widget, options['ext_error_text']);
                    $progress.addClass('hidden');
                    return false;
                }

                showError($widget, '');
                $widget.find('.cropper_buttons').removeClass('hidden');
                $widget.find('.cropper_label').addClass('hidden');
                var reader = new FileReader();

                reader.onload = function (e) {
                    var $img = $widget.find('.new_photo_area img');
                    if ($img.length) {
                        $img.data('Jcrop').destroy();
                        $img.remove();
                    }

                    $widget.find('.new_photo_area').append('<img src="' + e.target.result + '">');
                    $img = $widget.find('.new_photo_area img');
                    var x1 = ($img.width() - width) / 2;
                    var y1 = ($img.height() - height) / 2;
                    var x2 = x1 + width;
                    var y2 = y1 + height;
                    var aspectRatio = settings["aspectRatio"];
                    if (aspectRatio === '' || aspectRatio === 'Auto') {
                        aspectRatio = width / height;
                    }

                    if (!settings["cropImage"]) {
                        // select all area
                        x1 = 0;
                        y1 = 0;
                        x2 = ($img.width());
                        y2 = ($img.height());
                    }


                    $img.Jcrop({
                        aspectRatio: aspectRatio,
                        setSelect: [x1, y1, x2, y2],
                        boxWidth: $widget.find('.new_photo_area').width(),
                        boxHeight:$widget.find('.new_photo_area').height(),
                    }, function () {
                        jcrop_api = this;
                    });

                    if (!settings["cropImage"]) {
                        jcrop_api.disable();
                    }

                    $progress.addClass('hidden').find('.progress-bar').css({'width': '0%'});
                };

                reader.readAsDataURL(this._input.files[0]);


            },
            onComplete: function (filename, response) {
                $progress.addClass('hidden');
                if (response.error) {
                    showError($widget, response.error);
                    return;
                }
                $widget.find('.thumbnail').attr({'src': settings['tempPreviewUrl'] + '/' + response.filelink});
                $widget.find('.photo_field').val(response.filelink);
            },
            onSizeError: function () {
                showError($widget, options['size_error_text'])
            }
        }, options);

        var $uploader = new ss.SimpleUpload(settings);
        $widget.data('uploader', $uploader);
        $widget.data('progress', $progress);
    };

    $('.cropper_widget')
            .on('click', '.delete_photo', function () {
                var $widget = getWidget($(this));
                var $thumbnail = $widget.find('.thumbnail');
                $widget.find('.photo_field').val('');
                $thumbnail.attr({'src': $thumbnail.data('no-photo')});
            })
            .on('click', '.edit_photo', function () {
                var $widget = getWidget($(this));
                var $ImageCropBox = $widget.find('.image_crop_box');
                $ImageCropBox.show();

            });

    $('.image_crop_box')
            .on('click', '.crop_photo', function () {

                var $widget = getWidget($(this));
                var $img = $widget.find('.new_photo_area img');
                var data = $img.data('Jcrop').tellSelect();

                data[yii.getCsrfParam()] = yii.getCsrfToken();
                var $uploader = $widget.data('uploader');

                $uploader._queue[1] = $uploader._queue[0];
                $uploader.setData(data);

                var $progress = $widget.find('.progress');
                $progress.removeClass('hidden');
                $uploader.setProgressBar($progress.find('.progress-bar'));

                $uploader.submit();
            })
            ;

    function getWidget($element)
    {
        return $element.parents('.cropper_widget');
    }

    function showError($widget, error)
    {
        if (error == '') {
            $widget.parents('.form-group').removeClass('has-error').find('.help-block').text('');
        } else {
            $widget.parents('.form-group').addClass('has-error').find('.help-block').text(error);
        }
    }

    function inArray(haystack, needle)
    {
        var length = haystack.length;
        for (var i = 0; i < length; i++) {
            if (haystack[i] == needle) {
                return true;
            }
        }
        return false;
    }

})(jQuery);