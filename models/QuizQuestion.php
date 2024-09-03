<?php

/**
 * Модели опросника
 *
 * @author : Fedor B Gorsky
 */

namespace fgh151\quiz\models;

use Yii;

/**
 * Модель хранения Вопроса
 */
class QuizQuestion extends QuizConsistent
{
    public const TYPE_TEXT = 'text';
    public const TYPE_SELECT = 'select';
    public const TYPE_FILE = 'file';
    public const TYPE_CHECKBOX = 'binaryCheckbox';
    public const TYPE_CHECKBOX_LIST = 'checkboxList';
    public const TYPE_RADIO = 'radioList';
    public const TYPE_RADIO_OTHER = 'radioListOther';
    public const TYPE_LABEL = 'label';
    public const TYPE_NUMBER = 'number';

    /**
     * Имя поля. Попадет в аттрибут name
     *
     * @var string
     */
    public $field;
    /**
     * Вопрос
     *
     * @var string
     */
    public $title;
    /**
     * Тип поля (text, textarea и т.д.)
     *
     * @var string
     */
    public $type;
    /**
     * Тип информации пользователя
     *
     * @var string
     */
    public $userInfoType;
    /**
     * Массив валидаторов, применяемых к полю
     *
     * @var QuizValidator[]
     */
    public $validators = [];
    /**
     * Описание для полей типа: file
     *
     * @var string $description
     */
    public $description;
    /**
     * Массив вариантов для полей типа: select
     *
     * @var array $options
     */
    public $options;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->field = $this->field ?? str_replace('-', '', Yii::$app->security->generateRandomString());
        parent::init();
    }

    /**
     * Получить типы
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_TEXT => 'Текст',
            self::TYPE_SELECT => 'Выпадающий список',
            self::TYPE_FILE => 'Файл',
            self::TYPE_CHECKBOX => 'Чекбокс',
            self::TYPE_CHECKBOX_LIST => 'Список чекбоксов',
            self::TYPE_RADIO => 'Радиокнопки',
            self::TYPE_RADIO_OTHER => 'Радиокнопки + другое',
            self::TYPE_LABEL => 'Разделитель',
        ];
    }
}
