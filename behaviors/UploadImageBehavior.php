<?php

namespace plathir\cropper\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;
use yii;
use yii\validators\Validator;

class UploadImageBehavior extends Behavior {
    /*
     * Are available 3 indexes:
     * `path` Path where the file will be moved.
     * - `temp_path` Temporary path from where file will be moved.
     * - `url` Path URL where file will be saved.
     */

    const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * Are available 3 indexes:
     * - `path` Path where the file will be moved.
     * - `tempPath` Temporary path from where file will be moved.
     * - `url` Path URL where file will be saved.
     *
     * @var array Attributes array
     */
    public $attributes = [];

    /**
     * @var boolean If `true` current attribute file will be deleted
     */
    public $unlinkOnSave = true;

    /**
     * @var boolean If `true` current attribute file will be deleted after model deletion
     */
    public $unlinkOnDelete = true;
    public $keyFolder = null;

    /**
     * @var array Publish path cache array
     */
    protected static $_cachePublishPath = [];

    public function events() {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate'
        ];
    }

    /*
     * before Insert record
     */

    public function attach($owner) {
        parent::attach($owner);
        if (!is_array($this->attributes) || empty($this->attributes)) {
            throw new InvalidParamException('Invalid or empty attributes array.');
        } else {
            foreach ($this->attributes as $attribute => $config) {
                if (!isset($config['path']) || empty($config['path'])) {
                    throw new InvalidParamException('Path must be set for all attributes.');
                }
                if (!isset($config['temp_path']) || empty($config['temp_path'])) {
                    throw new InvalidParamException('Temporary path must be set for all attributes.');
                }
                if (!isset($config['url']) || empty($config['url'])) {
                    $config['url'] = $this->publish($config['path']);
                }
                $this->attributes[$attribute]['path'] = FileHelper::normalizePath(Yii::getAlias($config['path'])) . DIRECTORY_SEPARATOR;
                $this->attributes[$attribute]['temp_path'] = FileHelper::normalizePath(Yii::getAlias($config['temp_path'])) . DIRECTORY_SEPARATOR;

                if (isset($config['key_folder'])) {
                    $this->keyFolder = $config['key_folder'];
                }

                $this->attributes[$attribute]['url'] = rtrim($config['url'], '/') . '/';

                $validator = Validator::createValidator('string', $this->owner, $attribute);
                $this->owner->validators[] = $validator;
                unset($validator);
            }
        }
    }

    protected function saveFile($attribute, $insert = true) {
        if (empty($this->owner->$attribute)) {
            if ($insert !== true) {
                $this->deleteFile($this->oldFile($attribute));
            }
        } else {
            $tempFile = $this->tempFile($attribute);
            $file = $this->file($attribute);
            if (is_file($tempFile) && FileHelper::createDirectory($this->path($attribute))) {
                if (rename($tempFile, $file)) {
                    if ($insert === false && $this->unlinkOnSave === true && $this->owner->getOldAttribute(
                                    $attribute
                            )
                    ) {
                        $this->deleteFile($this->oldFile($attribute));
                    }
                    $this->triggerEventAfterUpload();
                } else {
                    unset($this->owner->$attribute);
                }
            } elseif ($insert === true) {
                unset($this->owner->$attribute);
            } else {
                $this->owner->setAttribute($attribute, $this->owner->getOldAttribute($attribute));
            }
        }
    }

    protected function deleteFile($file) {
        if (is_file($file)) {
            return unlink($file);
        }
        return false;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Old file path
     */
    public function oldFile($attribute) {
        return $this->path($attribute) . $this->owner->getOldAttribute($attribute);
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Path to file
     */
    public function path($attribute) {
        return $this->attributes[$attribute]['path'];
    }

    public function tempFile($attribute) {
        return $this->tempPath($attribute) . $this->owner->$attribute;
    }

    public function tempPath($attribute) {
        return $this->attributes[$attribute]['temp_path'];
    }

    public function file($attribute) {
        return $this->path($attribute) . $this->owner->$attribute;
    }

    public function publish($path) {
        if (!isset(static::$_cachePublishPath[$path])) {
            static::$_cachePublishPath[$path] = Yii::$app->assetManager->publish($path)[1];
        }
        return static::$_cachePublishPath[$path];
    }

    /**
     * Trigger [[EVENT_AFTER_UPLOAD]] event.
     */
    protected function triggerEventAfterUpload() {
        $this->owner->trigger(self::EVENT_AFTER_UPLOAD);
    }

    public function removeAttribute($attribute) {
        if (isset($this->attributes[$attribute])) {
            if ($this->deleteFile($this->file($attribute))) {
                return $this->owner->updateAttributes([$attribute => null]);
            }
        }
        return false;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return null|string Full attribute URL
     */
    public function urlAttribute($attribute) {
        if (isset($this->attributes[$attribute]) && $this->owner->$attribute) {
            return $this->attributes[$attribute]['url'] . $this->owner->$attribute;
        }
        return null;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Attribute mime-type
     */
    public function getMimeType($attribute) {
        return FileHelper::getMimeType($this->file($attribute));
    }

    public function beforeInsert() {
        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->$attribute) {
                $this->saveFile($attribute);
            }
        }
    }

    /*
     * Before Update Record
     */

    public function beforeUpdate() {
        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->isAttributeChanged($attribute)) {
                $this->saveFile($attribute, false);
            }
        }
    }

    /*
     * Before Delete Record
     */

    public function beforeDelete() {
        if ($this->unlinkOnDelete) {
            foreach ($this->attributes as $attribute => $config) {
                if ($this->owner->$attribute) {
                    $this->deleteFile($this->file($attribute));
                }
            }
        }
    }

    public function afterUpdate() {
        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->isAttributeChanged($attribute)) {
                $this->saveFile($attribute, false);
            }
        }
    }

    public function fileExists($attribute) {
        return file_exists($this->file($attribute));
    }

}
