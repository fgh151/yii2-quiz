<?php

/**
 * Опросник
 *
 * @author : Fedor B Gorsky
 */

namespace fgh151\quiz\models;

/**
 * Модель параметров валидатора вопроса опросника
 */
class QuizValidatorParam extends QuizConsistent
{
    /**
     * Ключ
     *
     * @var string|mixed
     */
    public $key;

    /**
     * Значение
     *
     * @var string|mixed
     */
    public $value;
}
