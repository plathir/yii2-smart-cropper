<?php

namespace plathir\cropper\components;

use yii\db\ActiveRecord;
use yii\base\Behavior;

class UploadImageBehavior extends Behavior {

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {





            return true;
        }
        return false;
    }

}
