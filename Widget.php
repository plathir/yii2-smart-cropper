<?php

namespace plathir\cropper;

use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

class Widget extends InputWidget {

    public $uploadParameter = 'file';
    public $width = 200;
    public $height = 200;
    public $aspectRatio = 'Auto';
    public $cropImage = true;
    public $label = '';
    public $uploadUrl;
    public $previewUrl;
    public $KeyFolder;
    public $tempPreviewUrl;
    public $noPhotoImage = '';
    public $maxSize = 3145728; // 3MB
    public $cropAreaWidth = 400;
    public $cropAreaHeight = 400;
    public $extensions = 'jpeg, JPEG, jpg, JPG, png, PNG, gif, GIF';
    public $display_width = 200;
    public $display_height = 200;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        self::registerTranslations();

        if ($this->uploadUrl === null) {
            throw new InvalidConfigException(Yii::t('cropper', 'MISSING_ATTRIBUTE', ['attribute' => 'uploadUrl']));
        } else {
            $this->uploadUrl = rtrim(Yii::getAlias($this->uploadUrl), '/') . '/';
        }

        if ($this->KeyFolder) {
            $this->previewUrl = $this->previewUrl . '/' . $this->KeyFolder;
        }

        if ($this->label == '') {
            $this->label = Yii::t('cropper', 'DEFAULT_LABEL');
        }
        // keep aspect ratio
        $ratio1 = $this->width / $this->height;
        $ratio2 = $this->display_width / $this->display_height;

        if ($ratio1 != $ratio2) {
            if ($ratio1 > 1) {
                $target_width = $this->display_width;
                $target_height = $this->display_width / $ratio1;
            } else {
                $target_width = $this->display_height * $ratio1;
                $target_height = $this->display_height;
            }
            $this->display_width = $target_width;
            $this->display_height = $target_height;
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        $this->registerClientAssets();

        return $this->render('widget', [
                    'model' => $this->model,
                    'widget' => $this
        ]);
    }

    /**
     * Register widget asset.
     */
    public function registerClientAssets() {
        $view = $this->getView();
        $assets = Asset::register($view);

        if ($this->noPhotoImage == '') {
            $this->noPhotoImage = $assets->baseUrl . '/img/nophoto.png';
        }
        $settings = [
            'url' => $this->uploadUrl,
            'previewUrl' => $this->previewUrl,
            'tempPreviewUrl' => $this->tempPreviewUrl,
            'name' => $this->uploadParameter,
            'aspectRatio' => $this->aspectRatio,
            'cropImage' => $this->cropImage,
            'maxSize' => $this->maxSize / 1024,
            'allowedExtensions' => explode(', ', $this->extensions),
            'size_error_text' => Yii::t('cropper', 'TOO_BIG_ERROR', ['size' => $this->maxSize / (1024 * 1024)]),
            'ext_error_text' => Yii::t('cropper', 'EXTENSION_ERROR', ['formats' => $this->extensions]),
            'accept' => 'image/*'
        ];
        $view->registerJs(
                //     'jQuery("#' . $this->options['id'] . '").siblings(".new_photo_area").cropper(' . Json::encode($settings) . ', ' . $this->width . ', ' . $this->height . ');', $view::POS_READY
                'jQuery("#' . $this->options['id'] . '").siblings(".image_crop_box").cropper(' . Json::encode($settings) . ', ' . $this->width . ', ' . $this->height . ');', $view::POS_READY
        );
    }

    /**
     * Register widget translations.
     */
    public static function registerTranslations() {
        if (!isset(Yii::$app->i18n->translations['cropper']) && !isset(Yii::$app->i18n->translations['cropper/*'])) {
            Yii::$app->i18n->translations['cropper'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@plathir/cropper/messages',
                'forceTranslation' => true,
                'fileMap' => [
                    'cropper' => 'cropper.php'
                ]
            ];
        }
    }

}
