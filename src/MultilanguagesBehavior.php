<?php
/**
 * @link https://github.com/creocoder/yii2-translateable
 * @copyright Copyright (c) 2015 Alexander Kochetov
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @modified by noname9
 * @link https://github.com/noname9/yii2-multilanguages
 */

namespace noname9\multilanguages;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveRecord;

/**
 * TranslateableBehavior
 *
 * @property ActiveRecord $owner
 *
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
class MultilanguagesBehavior extends Behavior
{
    /**
     * @var string the translations relation name
     */
    public $translationRelation = 'translations';
    /**
     * @var string the translations model language attribute name
     */
    public $translationLanguageAttribute = 'language';
    /**
     * @var string[] the list of attributes to be translated
     */
    public $translationAttributes;

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'addTranslations',     // populate translations on new object
            ActiveRecord::EVENT_AFTER_FIND => 'addTranslations',	// populate translations on find object
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }
    
    public function init()
    {
        if ($this->translationAttributes === null) {
            throw new InvalidConfigException('The "translationAttributes" property must be set.');
        }
    }

    /**
     * Returns the translation model for the specified language.
     * @param string|null $language
     * @return ActiveRecord
     */
    public function translate($language = null)
    {
        return $this->getTranslation($language);
    }

    /**
     * Returns the translation model for the specified language.
     * @param string|null $language
     * @return ActiveRecord
     */
    public function getTranslation($language = null)
    {
        if ($language === null) {
            $language = Yii::$app->language;
        }

        /* @var ActiveRecord[] $translations */
        $translations = $this->owner->{$this->translationRelation};

        foreach ($translations as $translation) {
            if ($translation->getAttribute($this->translationLanguageAttribute) === $language) {
                return $translation;
            }
        }

        /* @var ActiveRecord $class */
        $class = $this->owner->getRelation($this->translationRelation)->modelClass;
        /* @var ActiveRecord $translation */
        $translation = new $class();
        $translation->setAttribute($this->translationLanguageAttribute, $language);
        $translations[] = $translation;
        $this->owner->populateRelation($this->translationRelation, $translations);

        return $translation;
    }

    /**
     * Returns a value indicating whether the translation model for the specified language exists.
     * @param string|null $language
     * @return boolean
     */
    public function hasTranslation($language = null)
    {
        if ($language === null) {
            $language = Yii::$app->language;
        }

        /* @var ActiveRecord $translation */
        foreach ($this->owner->{$this->translationRelation} as $translation) {
            if ($translation->getAttribute($this->translationLanguageAttribute) === $language) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Auto populate translation attributes
     * 
     * @return void
     */
    public function addTranslations()
    {
        $this->owner->{$this->translationRelation};
		
        /* @var ActiveRecord $class */
        $class = $this->owner->getRelation($this->translationRelation)->modelClass;
		
        /* If method create or update - populate attributes */
	$className = (new \ReflectionClass($class))->getShortName();
//        var_dump($className);
	foreach (Yii::$app->request->post($className, []) as $language => $data) {
            foreach ($data as $attribute => $translation) {
                var_dump($data);
                $this->owner->translate($language)->$attribute = $translation;
            }
        }
    }
    
    /**
     * 
     * @return void
     */
    public function beforeDelete()
    {	
        foreach ($this->owner->translations AS $translation){
            $translation->delete();
        }
    }

    /**
     * @return void
     */
    public function afterValidate()
    {
        if (!Model::validateMultiple($this->owner->{$this->translationRelation})) {
            foreach ($this->owner->{$this->translationRelation} as $model) {
                /** @var ActiveRecord $model */
                if ($model->hasErrors()){
                    $this->owner->addErrors($model->getErrors());
                }
            }
        }
    }

    /**
     * @return void
     */
    public function afterSave()
    {
        /* @var ActiveRecord $translation */
        foreach ($this->owner->{$this->translationRelation} as $translation) {
            $this->owner->link($this->translationRelation, $translation);
        }
    }
    
    public function canGetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->translationAttributes) ?: parent::canGetProperty($name, $checkVars);
    }
    
    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->translationAttributes) ?: parent::canSetProperty($name, $checkVars);
    }
    
    public function __get($name)
    {
        return $this->getTranslation()->getAttribute($name);
    }
    
    public function __set($name, $value)
    {
        $translation = $this->getTranslation();
        $translation->setAttribute($name, $value);
    }
}
