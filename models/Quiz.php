<?php

/**
 * Модели опросника
 */

namespace fgh151\quiz\models;

use app\common\components\db\SoftDelete;
use app\common\components\enums\UserGenderEnum;
use app\common\components\helpers\Translit;
use app\common\models\event\EventSettingsRegistrationForm;
use app\common\models\user\User;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Модель хранит опросник
 *
 * @property int $id Идентификатор
 * @property string $title Название
 * @property DynamicModel $questions Модель опросника
 * @property string $description
 * @property string $text_after
 * @property string $date_end
 * @property string
 * @property bool $visible
 * @property int $deletion_time
 * @property string $deleted_by
 * @property string|null $external_quiz_link
 * @property string|null $results_link
 *
 * @property-read DynamicModel $form
 */
class Quiz extends ActiveRecord
{
    public const SCENARIO_EVENT_REGISTRATION_FORM = 'event_registration_form';

    /**
     * Идентификаторы опросников по компетенциям
     */
    public const COMPETENCE_QUIZZES = [
        67, 68, 70, 71, 72, 73, 74, 82, 83, 84, 85, /*86, 87, 88, */
        89, 90, 91, 92, 93,
    ];

    /**
     * Массив Вопросов
     *
     * @var QuizQuestion[]
     */
    public $data;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%quiz}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            ['title', 'required'],
            ['title', 'string', 'max' => 255],

            ['questions', 'safe'],

            ['description', 'string'],

            ['text_after', 'string'],

            ['date_end', 'required'],
            ['date_end', 'date', 'format' => 'php:Y-m-d'],

            ['visible', 'boolean'],

            ['external_quiz_link', 'string', 'max' => 1000],
            ['external_quiz_link', 'url', 'enableIDN' => true],

            ['results_link', 'string', 'max' => 1000],
            ['results_link', 'url', 'enableIDN' => true],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_EVENT_REGISTRATION_FORM] = ['title', 'questions'];

        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'questions' => 'Вопросы',
            'description' => 'Описание',
            'text_after' => 'Текст в случае успешного завершения',
            'date_end' => 'Дата окончания приема голосов',
            'visible' => 'Видимый',
            'external_quiz_link' => 'Ссылка на внешний опрос',
            'results_link' => 'Ссылка на результаты',
        ];
    }

    /**
     * Подготовка данных опросника для сохранения
     *
     * @param bool $insert Сохранение или изменение записи
     *
     * @return bool Результат сохранения
     */
    public function beforeSave($insert): bool
    {
        $this->questions = $this->data;

        return parent::beforeSave($insert);
    }

    /**
     * Получаем модель, которую можно запихивать в форму
     *
     * @return DynamicModel
     */
    public function getForm(): DynamicModel
    {
        $this->data = $this->questions;
        $this->questions = new DynamicModel();
        $this->questions->quiz_id = $this->id;

        // Для формы регистрации на мероприятие добавляются чек-боксы согласия на обработку данных и фото
        if ($this->scenario == self::SCENARIO_EVENT_REGISTRATION_FORM) {
            $this->data[] = [
                'type' => 'binaryCheckbox',
                'field' => EventSettingsRegistrationForm::AGREEMENT_PERSONAL_DATA_FIELD_NAME,
                'title' => '
                    Согласен с условиями <a href="/agreement" target="_blank">пользовательского соглашения</a>.
                ',
                'validators' => [
                    [
                        'title' => 'required',
                        'params' => [
                            [
                                'key' => 'message',
                                'value' => 'Необходимо дать согласие на передачу персональных данных',
                            ],
                        ],
                    ],
                ],
            ];

            $this->data[] = [
                'type' => 'binaryCheckbox',
                'field' => EventSettingsRegistrationForm::AGREEMENT_PHOTO_FIELD_NAME,
                'title' => '
                    Согласен с политикой обработки персональных данных и Ознакомлен с политикой конфиденциальности.
                ',
                'validators' => [
                    [
                        'title' => 'required',
                        'params' => [
                            [
                                'key' => 'message',
                                'value' => 'Необходимо дать согласие на обработку персональных данных',
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($this->data) {
            foreach ($this->data as $questionData) {
                $this->questions->defineAttribute($questionData['field'], null, $questionData['type']);
                $this->questions->defineLabel($questionData['field'], $questionData['title']);
                $this->questions->addRule($questionData['field'], 'safe');

                if ($this->id === 109) {
                    $this->questions->addRule($questionData['field'], 'required');
                }

                // Дополнительные опции для передаци в генератор поля
                switch ($questionData['type']) {
                    case QuizQuestion::TYPE_TEXT:
                        $this->questions->defineAttributeParam($questionData['field'], [
                            'userInfoType' => $questionData['userInfoType'] ?? '',
                        ]);
                        $this->checkValidators($questionData);
                        break;
                    case QuizQuestion::TYPE_SELECT:
                        $this->questions->defineAttributeParam($questionData['field'], [
                            'selectOptions' => $questionData['options'],
                        ]);
                        break;
                    case QuizQuestion::TYPE_RADIO:
                    case QuizQuestion::TYPE_RADIO_OTHER:
                        $this->questions->defineAttributeParam($questionData['field'], [
                            'variants' => $questionData['options'],
                        ]);
                        break;
                    case QuizQuestion::TYPE_CHECKBOX_LIST:
                        $this->questions->defineAttributeParam($questionData['field'], [
                            'options' => $questionData['options'],
                        ]);
                        break;
                    case QuizQuestion::TYPE_FILE:
                        // Определение, обязателен ли файл для загрузки
                        $skipOnEmpty = true;

                        if (isset($questionData['validators']) && in_array('required', array_column($questionData['validators'], 'title'), true)) {
                            $skipOnEmpty = false;
                        }

                        $this->questions->addRule($questionData['field'], 'file', [
                            'skipOnEmpty' => $skipOnEmpty,
                            'extensions' => ['png', 'jpg', 'pdf', 'doc', 'docx', 'odt', 'xml'],
                            'maxSize' => 1024 * 1024 * 10, // 10 мегабайт
                            'uploadRequired' => 'Загрузите файл "' . $questionData['title'] . '"',
                        ]);
                        break;
                    default:
                        $this->checkValidators($questionData);
                }
            }
        }

        return $this->questions;
    }

    /**
     * Проверка валидаторов
     */
    private function checkValidators($questionData)
    {
        if (isset($questionData['validators'])) {
            foreach ($questionData['validators'] as $validator) {
                $this->questions->addRule(
                    $questionData['field'],
                    $validator['title'],
                    isset($validator['params']) ? ArrayHelper::map($validator['params'], 'key', 'value') : []
                );
            }
        }
    }

    /**
     * Десериализация данных вопросов в свойство data
     */
    public function afterFind(): void
    {
        $this->data = [];
        foreach ($this->questions as $modelQuestion) {
            $question = new QuizQuestion();
            $question->isNewRecord = false;
            $question->type = $modelQuestion['type'];
            $question->title = $modelQuestion['title'];
            $question->userInfoType = $modelQuestion['userInfoType'] ?? null;
            $question->field = $modelQuestion['field'];
            $question->description = $modelQuestion['description'] ?? null;
            $question->options = $modelQuestion['options'] ?? null;
            if (!empty($modelQuestion['validators'])) {
                foreach ($modelQuestion['validators'] as $modelValidator) {
                    $validator = new QuizValidator();
                    $validator->isNewRecord = false;
                    $validator->title = $modelValidator['title'];
                    if (!empty($modelValidator['params'])) {
                        foreach ($modelValidator['params'] as $modelParam) {
                            $param = new QuizValidatorParam();
                            $param->isNewRecord = false;
                            $param->key = $modelParam['key'];
                            $param->value = $modelParam['value'];

                            $validator->params[] = $param;
                        }
                    }

                    $question->validators[] = $validator;
                }
            }

            $this->data[] = $question;
        }

        parent::afterFind();
    }

    /**
     * Проходил ли пользователь тест
     *
     * @param int $userId
     *
     * @return bool
     */
    public function getHasUserAnswer($userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        return QuizResult::find()->where(['user_id' => $userId, 'quiz_id' => $this->id])->exists();
    }

    /**
     * Добавление/редактирование опроса
     * Связывает модель опроса Quiz с моделями
     * QuizQuestion, QuizValidator, QuizValidatorParam
     */
    public function initializeQuiz()
    {
        $this->load(Yii::$app->request->post());

        $questionIndex = 0;

        // Обнуляем данные, позже мы их добавим
        $this->data = [];

        // Конвертация данных формы для сохранения в базе
        $formQuestions = Yii::$app->request->post(QuizQuestion::getClassName());

        if (is_array($formQuestions)) {
            foreach ($formQuestions as $formQuestion) {
                $question = new QuizQuestion();
                $question->isNewRecord = false; // костыль для DynamicForm
                $question->title = $formQuestion['title'];
                $question->type = $formQuestion['type'];

                if (false === empty($formQuestion['field'])) {
                    $question->field = $formQuestion['field'];
                }

                // Валидаторы и опции вопроса
                $question->validators = [];

                switch ($formQuestion['type']) {
                    case QuizQuestion::TYPE_RADIO:
                        $question->options = [];

                        if (false === empty($formQuestion['options'])) {
                            foreach (array_filter($formQuestion['options']) as $k => $v) {
                                $question->options[] = [
                                    'label' => $v,
                                    'value' => $k,
                                ];
                            }
                        }
                        break;
                    case QuizQuestion::TYPE_SELECT:
                        $question->options = [];

                        if (false === empty($formQuestion['options'])) {
                            foreach (array_filter($formQuestion['options']) as $k => $v) {
                                $optionId = strtolower(Translit::translit($v, true, 'ru-en'));
                                $question->options[$optionId] = $v;
                            }

                            // Для селектора добавляем дополнительный валидатор IN
                            $validator = new QuizValidator();
                            $validator->title = 'in';
                            $validator->params[] = [
                                'key' => 'range',
                                'value' => array_keys($question->options),
                            ];

                            $question->validators['range'] = $validator;
                        }
                        break;
                }

                $this->data[] = $question;
                $questionIndex++;
            }
        }

        $this->save();
    }

    /**
     * Возвращает значения по-умолчанию для полей регистрации на мероприятие
     *
     * @param string $name
     *
     * @return string|null
     */
    public static function getEventRegistrationFieldDefaultValue($name)
    {
        /* @var User $user */
        $user = Yii::$app->user->identity;

        if ($user === null) {
            return '';
        }

        switch ($name) {
            case 'email':
                $value = $user->email;
                break;
            case 'first_name':
                $value = $user->first_name;
                break;
            case 'last_name':
                $value = $user->last_name;
                break;
            case 'father_name':
                $value = $user->father_name;
                break;
            case 'phone':
                $value = $user->phone;
                break;
            case 'company':
                $value = $user->primaryEmployment && $user->primaryEmployment->organisation ? $user->primaryEmployment->organisation->name_short : null;
                break;
            case 'position':
                $value = $user->primaryEmployment ? $user->primaryEmployment->position : null;
                break;
            case 'age':
                $value = $user->getCurrentAge() ?: null;
                break;
            case 'birthday':
                $value = $user->birthday ? date('d.m.Y', strtotime($user->birthday)) : null;
                break;
            case 'gender':
                $value = $user->gender;
                $genderList = UserGenderEnum::$list;
                array_shift($genderList);
                $value = $genderList[$value] ?? null;
                break;
            case 'academic_degree':
                $value = !empty($user->degrees) ? $user->degrees[0]->name : null;
                break;
            case 'interest':
                $value = !empty($user->interests) ? $user->interests[0]->classifierItem->title : null;
                break;
            case 'smu':
                $smuValue = [];

                foreach ($user->smuMember as $smu) {
                    $smuValue[] = $smu->smu->title;
                }

                if (empty($smuValue)) {
                    $smuValue = ['Нет'];
                }

                $value = implode(', ', $smuValue);
                break;
            default:
                $value = null;
        }

        return $value;
    }

    /**
     * Получить текст ответа на вопрос
     */
    public function getTextAnswer($field, $answer)
    {
        foreach ($this->questions as $question) {
            if ($question['field'] == $field) {
                return $question['options'][$answer];
            }
        }
    }
}
