<?php

/**
 * Модели опросника
 */

namespace fgh151\quiz\models;

use app\common\models\user\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\base\Component;

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
 * @property User $user Пользователь
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

    /**
     * Сохранить в файл
     */
    public function saveFile($fileName = __DIR__ . '/test.xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->addSheetHeader($sheet);
        $rowIndex = 2;
        foreach ($this->results as $userId => $row) {
            $sheet->setCellValueByColumnAndRow(1, $rowIndex, $userId);
            $colIndex = 2;

            foreach ($this->fields as $field => $v) {
                $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $row[$field]);
                $colIndex++;
            }

            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($fileName);
    }

    /**
     * Добавить заголовок страницы
     */
    private function addSheetHeader(Worksheet $sheet)
    {
        $colIndex = 2;
        foreach ($this->fields as $field) {
            $sheet->setCellValueByColumnAndRow($colIndex, 1, $field);
            $colIndex++;
        }
    }
}
