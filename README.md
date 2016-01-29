# yii2-multilanguages
A translateable behavior for the Yii2 framework.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require noname9/yii2-multilanguages
```

or add

```
"noname9/yii2-multilanguages": "~1.0"
```

to the `require` section of your `composer.json` file.

## Migrations

Run the following command

```bash
$ yii migrate/create create_post_table
```

Open the `/path/to/migrations/m_xxxxxx_xxxxxx_create_post_table.php` file,
inside the `up()` method add the following

```php
$this->createTable('{{%post}}', [
    'id' => $this->primaryKey(),
]);
```

Run the following command

```bash
$ yii migrate/create create_post_translation_table
```

Open the `/path/to/migrations/m_xxxxxx_xxxxxx_create_post_translation_table.php` file,
inside the `up()` method add the following

```php
$this->createTable('{{%post_translation}}', [
    'id' => $this->primaryKey(),
    'post_id' => $this->integer()->notNull(),
    'language' => $this->string(8)->notNull(),
    'title' => $this->string(1024)->notNull(),
    'body' => $this->text(),
]);

```

## Configuring

Configure model as follows

```php
use creocoder\translateable\TranslateableBehavior;

/**
 * ...
 * @property string $title
 * @property string $body
 * ...
 */
class Post extends \yii\db\ActiveRecord
{
    public function behaviors()
    {
        return [
            'translateable' => [
                'class' => TranslateableBehavior::className(),
                'translationAttributes' => ['title', 'body'],
                // translationRelation => 'translations',
                // translationLanguageAttribute => 'language',
            ],
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_INSERT | self::OP_UPDATE,
        ];
    }

    public function getTranslations()
    {
        return $this->hasMany(PostTranslation::className(), ['post_id' => 'id']);
    }
}
```

Model `PostTranslation` can be generated using Gii.

## Usage

### Setting translations to the entity

To set translations to the entity

```php
$post = new Post();

// title attribute translation for default application language
$post->title = 'Post title';

// body attribute translation for default application language
$post->body = 'Post body';

// title attribute translation for German
$post->translate('de-DE')->title = 'Post titel';

// body attribute translation for German
$post->translate('de-DE')->body = 'Post inhalt';

// title attribute translation for Russian
$post->translate('ru-RU')->title = 'Заголовок поста';

// body attribute translation for Russian
$post->translate('ru-RU')->body = 'Тело поста';

// save post and its translations
$post->save();
```

### Getting translations from the entity

To get translations from the entity

```php
$posts = Post::find()->with('translations')->all();

foreach ($posts as $post) {
    // title attribute translation for default application language
    $title = $post->title;

    // body attribute translation for default application language
    $body = $post->body;

    // title attribute translation for German
    $germanTitle = $post->translate('de-DE')->title;

    // body attribute translation for German
    $germanBody = $post->translate('de-DE')->body;

    // title attribute translation for Russian
    $russianTitle = $post->translate('ru-RU')->title;

    // body attribute translation for Russian
    $russianBody = $post->translate('ru-RU')->body;
}
```

### Checking for translations in the entity

To check translations in the entity

```php
$post = Post::findOne(1);

// checking for default application language translation
$result = $post->hasTranslation();

// checking for German translation
$result = $post->hasTranslation('de-DE');

// checking for Russian translation
$result = $post->hasTranslation('ru-RU');
```

## Advanced usage

### Collecting tabular input

Example of view form

```php
<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin();

foreach (['en-US', 'de-DE', 'ru-RU'] as $language) {
    echo $form->field($model->translate($language), "[$language]title")->textInput();
    echo $form->field($model->translate($language), "[$language]body")->textarea();
}

//...

ActiveForm::end();
```

### Language specific translation attribute labels

Example of model attribute labels

```php
class PostTranslation extends \yii\db\ActiveRecord
{
    public function attributeLabels()
    {
        switch ($this->language) {
            case 'de-DE':
                return [
                    'title' => 'Titel',
                    'body' => 'Inhalt',
                ];
            case 'ru-RU':
                return [
                    'title' => 'Заголовок',
                    'body' => 'Тело',
                ];
            default:
                return [
                    'title' => 'Title',
                    'body' => 'Body',
                ];
        }
    }
}
```