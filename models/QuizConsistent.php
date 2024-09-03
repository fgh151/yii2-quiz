<?php
/**
 * Базовый класс полей опросника
 *
 * @author : Fedor B Gorsky
 */

namespace fgh151\quiz\models;

use yii\base\Model;

/**
 * Вспомогательные методы для сущностей опросника
 */
abstract class QuizConsistent extends Model
{
    /**
     * Поле необходимо для динамической формы
     *
     * @var bool
     */
    public $isNewRecord = true;

    /**
     * Имя вызываемого класса
     */
    public static function getClassName(): string
    {
        $path = explode('\\', get_called_class());

        return array_pop($path);
    }
}
