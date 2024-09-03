<?php

/**
 * Модели опросника
 *
 * @author : Fedor B Gorsky
 */

namespace fgh151\quiz\models;

use app\common\components\helpers\HelpersGlobals;
use app\common\models\user\User;
use Yii;
use yii\base\DynamicModel as BaseDynamicModel;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * Модель формы для передачи в контроллер
 */
class DynamicModel extends BaseDynamicModel
{
    /**
     * Идентификатор опросника
     *
     * @var int
     */
    public $quiz_id;
    /**
     * Имена аттрибутов
     *
     * @var array
     */
    private $_attributeLabels = [];
    /**
     * Html типы аттрибутов (text, radio, textarea и т.д.)
     *
     * @var array
     */
    private $_attributeTypes = [];
    /**
     * Дополнительные параметры аттрибутов (например массив ключ => значение для селектора)
     *
     * @var array
     */
    private $_attributeParams = [];

    /**
     * Добавление имени для аттрибута
     *
     * @param string $name Название свойства
     * @param string $label Имя
     */
    public function defineLabel($name, $label)
    {
        $this->_attributeLabels[$name] = $label;
    }

    /**
     * Массив названий аттрибутов
     */
    public function attributeLabels(): array
    {
        return $this->_attributeLabels;
    }

    /**
     * Массив типов аттрибутов
     */
    public function fieldTypes(): array
    {
        return $this->_attributeTypes;
    }

    /**
     * Массив дополнительных параметров аттрибутов
     *
     * @return array
     */
    public function attributeParams()
    {
        return $this->_attributeParams;
    }

    /**
     * Переопределенная функция добавления аттрибута.
     * Добавляет тип
     *
     * @param string $name Название аттрибута
     * @param string|null $value Значение аттрибута
     * @param string $fieldType Тип поля
     */
    public function defineAttribute($name, $value = null, $fieldType = 'text')
    {
        parent::defineAttribute($name, $value);
        $this->_attributeTypes[$name] = $fieldType;
    }

    /**
     * Функция добавляет дополнительные параметры аттрибутов
     *
     * @param string $name
     * @param mixed $value
     */
    public function defineAttributeParam(string $name, $value = null)
    {
        $this->_attributeParams[$name] = $value;
    }

    /**
     * Сохранение результатов заполнения формы
     *
     * @param User|null $user
     * @param array $params
     *
     * @return QuizResult|null|static
     * @throws Exception
     */
    public function save(?User $user = null, $params = [])
    {
        $userId = $user !== null ? $user->id : Yii::$app->getUser()->getId();
        $model = QuizResult::findOne(['quiz_id' => $this->quiz_id, 'user_id' => $userId]) ?? new QuizResult(['quiz_id' => $this->quiz_id, 'user_id' => $userId]);
        $fieldTypes = $this->fieldTypes();

        // Создаем папку для файлов
        if (array_search('file', $fieldTypes) !== false) {
            $directory = $this->createDirectory((int)$model->user_id);
        }

        $oldFilePaths = [];

        foreach ($this->attributes as $k => $v) {
            if ($fieldTypes[$k] == 'file') {
                if (empty($params['keepFiles'])) {
                    if ($v instanceof UploadedFile) {
                        // Сохранение нового файла
                        $fileName = HelpersGlobals::uniqidStr() . '.' . $v->extension;
                        $v->saveAs($directory . '/' . $fileName);

                        if (!empty($model->questions[$k]) && is_file($filePath = $directory . '/' . $model->questions[$k]['fileName'])) {
                            $oldFilePaths[] = $filePath;
                        }

                        $this->{$k} = [
                            'fileName' => $fileName,
                            'size' => $v->size,
                        ];
                    }
                } else {
                    // Если нужно сохранить загруженные файлы, то записываем в аттрибут старые данные
                    $this->{$k} = $model->questions[$k] ?? null;
                }
            }
        }

        $model->questions = $this;

        if ($model->save()) {
            // Удаление старых файлов
            foreach ($oldFilePaths as $oldFilePath) {
                if (is_file($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
        }

        return $model;
    }

    /**
     * Создаем структуру директорий на сервере
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function createDirectory(int $userId): string
    {
        $path = $this->getDirectoryPath($userId);

        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Возвращает путь к папке, где хранятся пользовательские файлы, либо публичный адрес папки
     *
     * @param int $userId
     * @param bool $public
     *
     * @return string
     */
    public function getDirectoryPath($userId, $public = false)
    {
        return ($public ? Yii::$app->params['userFilesPath'] : Yii::$app->params['userFilesStorage']) . $userId . '/files/';
    }
}
