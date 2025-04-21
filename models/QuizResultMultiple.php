<?php

/**
 * Модели опросника
 */

namespace fgh151\quiz\models;

use yii\base\Component;
use yii\web\IdentityInterface;

/**
 * Модель хранения результатов заполнения формы опросника
 *
 * @property int $id Идентификатор
 * @property int $quiz_id Идентификатор опроса
 * @property int $user_id Идентификатор пользователя
 * @property string $questions
 * @property array $result Массив заполненных вопросов
 *
 * @property Quiz $quiz Опросник
 * @property-read string $preparedAnswers
 * @property IdentityInterface $user Пользователь
 */
class QuizResultMultiple extends Component
{
    public $quizzes = [];
    public $results = [];

    public $fields = [];

    /**
     * Получить запросы
     */
    public static function findByQuizzes(array $ids): QuizResultMultiple
    {
        ini_set('memory_limit', "-1");

        $model = new self();

        /* @var Quiz[] $quizzes */
        $quizzes = Quiz::find()->where(['in', 'id', $ids])->indexBy('id')->all();
        /* @var QuizResult[] $results */
        $resultsBatch = QuizResult::find()->where(['in', 'quiz_id', $ids])->batch(5);

        foreach ($quizzes as $quiz) {
            foreach ($quiz->data as $quizQuestion) {
                $model->fields[$quizQuestion->field] = strip_tags($quizQuestion->title);
                foreach ($resultsBatch as $results) {
                    foreach ($results as $result) {
                        $res = $model->results[$result->user_id] ?? [];

                        if (isset($res[$quizQuestion->field])) {
                            if (isset($result->questions[$quizQuestion->field])) {
                                if (false === isset($res[$quizQuestion->field])) {
                                    $res[$quizQuestion->field] = $result->questions[$quizQuestion->field];
                                } elseif ($res[$quizQuestion->field] == '') {
                                    $res[$quizQuestion->field] = $result->questions[$quizQuestion->field];
                                }
                            }
                        } else {
                            $res[$quizQuestion->field] = '';
                        }

                        $model->results[$result->user_id] = $res;
                    }
                }
            }
        }

        return $model;
    }
}
