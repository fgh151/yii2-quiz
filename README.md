Yii2 quiz extemsion
===================
Provide quiz algorithms

Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist fgh151/yii2-quiz "*"
```

or add

```
"fgh151/yii2-quiz": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Example controller

```php
public function actionUpdate($id)
    {
        $quiz = Quiz::findOne($id);

        if (Yii::$app->request->post()) {
            $quiz->initializeQuiz();
        }

        return $this->render('create', [
            'quiz' => $quiz,
            'questions' => empty($quiz->data) ? [new QuizQuestion()] : $quiz->data,
        ]);
    }
```

Example view, using [Dynamic form](https://github.com/fgh151/yii2-dynamicform) 

```php
<?php $form = ActiveForm::begin([
    'id' => 'dynamic-form',
    'options' => [
        'class' => 'quiz-form',
        'autocomplete' => 'off',
    ],
]); ?>

<?php DynamicFormWidget::begin([
    'widgetContainer' => 'form_wrapper',
    // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
    'widgetBody' => '.form-questions',
    // required: css class selector
    'widgetItem' => '.question',
    // required: css class
    'limit' => 300,
    // the maximum times, an element can be added (default 999)
    'min' => 0,
    // 0 or 1 (default 1)
    'insertButton' => '.add-question',
    // css class
    'deleteButton' => '.remove-question',
    // css class
    'model' => $questions[0],
    'formId' => 'dynamic-form',
    'formFields' => [
        'title',
        'type',
    ],
]); ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>
                <i class="fa fa-cloud"></i> Вопросы
                <button type="button" class="add-question btn btn-success btn-sm pull-right">
                    <i class="fas fa-plus"></i> Добавить вопрос
                </button>
            </h4>
        </div>
        <div class="panel-body">
            <div class="form-questions"><!-- widgetBody -->
                <?php foreach ($questions as $i => $question) : ?>
                    <?php $withOptions = in_array($question->type, [QuizQuestion::TYPE_SELECT, QuizQuestion::TYPE_RADIO, QuizQuestion::TYPE_CHECKBOX], true); ?>
                    <div class="p-1 mb-4 border question panel panel-default"><!-- widgetItem -->
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-auto">
                                    <button type="button" class="remove-question btn btn-danger btn-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <h3 class="panel-title pull-left">Вопрос</h3>
                                </div>
                                <div class="col-auto">
                                    <?= $form->field($question, "[$i]type")->dropDownList([
                                        QuizQuestion::TYPE_TEXT => 'Текстовое поле',
                                        QuizQuestion::TYPE_FILE => 'Файл',
                                        QuizQuestion::TYPE_NUMBER => 'Поле для ввода цифр',
                                        QuizQuestion::TYPE_SELECT => 'Выпадающий список',
                                        QuizQuestion::TYPE_RADIO => 'Булево поле',
                                        QuizQuestion::TYPE_CHECKBOX => 'Чекбокс',
                                        QuizQuestion::TYPE_LABEL => 'Произвольный текст без вопроса',
                                    ], ['class' => 'form-control js-question-type-selector'])
                                        ->label(false) ?>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <?php if (!$question->isNewRecord) {
                                echo Html::activeHiddenInput($question, "[$i]field");
                            } ?>

                            <div class="row">
                                <div class="col-12 col-xl-6">
                                    <?= $form->field($question, "[$i]title")->textarea(['class' => 'js-editor'])->label(false) ?>
                                </div>
                                <div class="col-12 col-xl-6">
                                    <div class="question-options-wrapper <?= false === $withOptions ? 'hidden' : '' ?>"
                                         data-type="select">
                                        <label>Опции</label>
                                        <br>
                                        <ol class="question-options">
                                            <?php if ($withOptions && !empty($question->options)) : ?>
                                                <?php foreach ($question->options as $option) : ?>
                                                    <li class="question-option">
                                                        <div class="row mb-2">
                                                            <div class="col">
                                                                <?= Html::activeTextInput($question, "[$i]options[]", ['value' => $question->type === QuizQuestion::TYPE_RADIO ? $option['label'] : $option, 'class' => 'form-control']) ?>

                                                            </div>
                                                            <div class="col-auto">
                                                                <button class="btn btn-danger btn-md del-option">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <li class="question-option">
                                                    <div class="row mb-2">
                                                        <div class="col">
                                                            <?= Html::activeTextInput($question, "[$i]options[]", ['class' => 'form-control']) ?>
                                                        </div>
                                                        <div class="col-auto">
                                                            <button class="btn btn-danger btn-md del-option">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endif; ?>
                                        </ol>
                                        <button class="btn btn-success btn-sm add-option">
                                            <i class="fas fa-plus"></i> Добавить опцию
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div><!-- .panel -->

    <button type="button" class="mb-4 add-question btn btn-success btn-sm pull-right">
        <i class="fas fa-plus"></i> Добавить вопрос
    </button>
<?php DynamicFormWidget::end(); ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>

$js = <<<JS
$(document).on('click', '#dynamic-form .question .add-option', function(e) {
    e.preventDefault();

    var questionOptions = $(this).siblings('.question-options');
    var newOption = questionOptions.find('li').last().clone().removeClass('hidden');
    newOption.find('input').prop('disabled', false);
    newOption.find('input[type="text"]').val('');
    newOption.appendTo(questionOptions);
});
$(document).on('click', '#dynamic-form .question .del-option', function(e) {
    e.preventDefault();

    var questionOptions = $(this).closest('.question-options').find('.question-option');
    var optionToDelete = $(this).closest('.question-option');

    if (questionOptions.length > 1) {
        optionToDelete.remove();
    } else {
        optionToDelete.addClass('hidden');
        optionToDelete.find('input').prop('disabled', true);
    }
});

$(document).on('change', '.js-question-type-selector', function (e) {
    const val = e.target.value;
    if (['select', 'radioList', 'binaryCheckbox'].includes(val)) {
        $(this).closest('.panel').find('.question-options-wrapper').removeClass('hidden');
    } else {
        $(this).closest('.panel').find('.question-options-wrapper').addClass('hidden');
    }
    
})
JS;

$this->registerJs($js);

```