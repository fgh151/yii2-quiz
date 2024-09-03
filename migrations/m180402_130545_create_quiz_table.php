<?php

use yii\db\Migration;

/**
 * Handles the creation of table `quiz`.
 */
class m180402_130545_create_quiz_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('quiz', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'questions' => 'jsonb',
        ]);

        $this->createTable('quiz_results', [
            'id' => $this->primaryKey(),
            'quiz_id' => $this->integer()->null(),
            'user_id' => $this->integer()->null(),
            'questions' => 'jsonb',
        ]);

        $this->addForeignKey('fk-user-quiz_results', 'quiz_results', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-quiz-quiz_results', 'quiz_results', 'quiz_id', 'quiz', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('quiz_results');
        $this->dropTable('quiz');
    }
}
