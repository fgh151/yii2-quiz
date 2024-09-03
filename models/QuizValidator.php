<?php

/**
 * Валидатор опросника
 *
 * @author : Fedor B Gorsky
 */

namespace fgh151\quiz\models;

use yii\validators\Validator;

/**
 * Валидатор опросника
 */
class QuizValidator extends QuizConsistent
{
    /**
     * Название
     *
     * @var string
     */
    public $title;

    /**
     * Параметры
     *
     * @var QuizValidatorParam[]
     */
    public $params;

    /**
     * Получить типы
     */
    public static function getTypes(): array
    {
        return array_keys(Validator::$builtInValidators);
    }
}
