<?php

/**
 * Модели опросника
 */

namespace fgh151\quiz\models;

use app\common\models\user\User;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * Модель хранения результатов заполнения формы опросника
 *
 * @property int    $id Идентификатор
 * @property int    $quiz_id Идентификатор опроса
 * @property int|string    $user_id Идентификатор пользователя
 * @property string|DynamicModel $questions
 * @property array  $result Массив заполненных вопросов
 *
 * @property Quiz $quiz Опросник
 * @property IdentityInterface $user Пользователь
 */
class QuizResult extends ActiveRecord
{
    public $result;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%quiz_results}}';
    }

    /**
     * {@inheritdoc}
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['quiz_id', 'user_id'], 'default', 'value' => null],
            [['quiz_id', 'user_id'], 'integer'],
            [['questions'], 'safe'],
            [
                ['quiz_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Quiz::class,
                'targetAttribute' => ['quiz_id' => 'id'],
            ],
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => \Yii::$app->getUser()->identityClass,
                'targetAttribute' => ['user_id' => 'id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'quiz_id' => 'Quiz ID',
            'user_id' => 'User ID',
            'questions' => 'Questions',
        ];
    }

    /**
     * Опросник
     *
     * @return ActiveQuery
     */
    public function getQuiz(): ActiveQuery
    {
        return $this->hasOne(Quiz::class, ['id' => 'quiz_id']);
    }

    /**
     * Получить запрос на пользователя, ответивший на вопросы
     *
     * @return ActiveQuery
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(\Yii::$app->getUser()->identityClass, ['id' => 'user_id']);
    }

    /**
     * {@inheritDoc}
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            return $this->questions[$name];
        }
    }

    /**
     * Возвращает ответы на вопросы формы в подготовленном виде:
     * - Для файлов указывается полный публичный URL и заголовок
     *
     * @return string
     */
    public function getPreparedAnswers()
    {
        $quizQuestions = ArrayHelper::index($this->quiz->questions, 'field');
        $questionAnswers = $this->questions;

        foreach ($questionAnswers as $fieldName => $value) {
            if (!isset($quizQuestions[$fieldName])) {
                continue;
            }

            switch ($quizQuestions[$fieldName]['type']) {
                case 'file':
                    $oldValue = $questionAnswers[$fieldName];
                    $questionAnswers[$fieldName] = [
                        'filePath' => $oldValue !== null ? $this->quiz->getForm()->getDirectoryPath($this->user_id, true) . $oldValue['fileName'] : null,
                        'title' => $quizQuestions[$fieldName]['title'],
                    ];
                    break;
            }
        }

        return $questionAnswers;
    }
}
