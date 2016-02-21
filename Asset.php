<?php

namespace plathir\cropper;

use yii\web\AssetBundle;

/**
 * Widget asset bundle
 */
class Asset extends AssetBundle {

    /**
     * @inheritdoc
     */
    public $sourcePath = '@plathir/cropper/assets';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/jquery.Jcrop.min.css',
        'css/cropper.css'
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/jquery.Jcrop.min.js',
        'js/SimpleAjaxUploader.min.js',
        'js/cropper.js',
        'js/jQueryRotate.js'
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset'
    ];

}
