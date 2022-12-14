<?php

namespace App\Model;

use PDO;

class QuestionManager extends AbstractManager
{
    public const TABLE = 'question';


    /**
     * Get all row from database.
     */
    public function selectAllWithFilter(array $filter): array
    {
        $query = "SELECT * FROM " . self::TABLE . " WHERE";
        if ($filter['theme'] != 'all') {
            $query .= ' theme=:theme AND ';
        }
        $query .= " content LIKE :include ORDER BY " . $filter['sort'][0] . " " . $filter['sort'][1];

        $statement = $this->pdo->prepare($query);

        if ($filter['theme'] != 'all') {
            $statement->bindValue(':theme', $filter['theme'], PDO::PARAM_STR);
        }

        $statement->bindValue(':include', '%' . $filter['include'] . '%', PDO::PARAM_STR);

        $statement->execute();
        $questions = $statement->fetchAll();
        $answerManager = new AnswerManager();
        foreach ($questions as &$question) {
            $answers = $answerManager->selectAllByQuestionsId($question['id']);
            foreach ($answers as $answer) {
                $question['answers'][$answer['answer']] = $answer['isTrue'];
            }
        }
        return $questions;
    }

    /**
     * SELECT all question & associates answers : ok !
     **/
    public function selectAllWithAnswer(string $orderBy = '', string $direction = 'ASC'): array
    {
        $answerManager = new AnswerManager();
        $questions = $this->selectAll($orderBy, $direction);
        foreach ($questions as &$question) {
            $answers = $answerManager->selectAllByQuestionsId($question['id']);
            foreach ($answers as $answer) {
                $question['answers'][$answer['answer']] = $answer['isTrue'];
            }
        }
        return $questions;
    }



    public function selectQuestionsWithAnswer(int $number = 15, string $theme = ''): array
    {
        $query = 'SELECT * FROM ' . self::TABLE;
        if ($theme && $theme != 'all') {
            $query .= ' WHERE theme=:theme';
        }
        $query .= ' ORDER BY RAND() LIMIT :number';

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':number', $number, PDO::PARAM_INT);
        if ($theme && $theme != 'all') {
            $statement->bindValue(':theme', $theme, PDO::PARAM_STR);
        }
        $statement->execute();
        $questions = $statement->fetchAll();
        $answerManager = new AnswerManager();
        foreach ($questions as &$question) {
            $question['answers'] = $answerManager->selectAllByQuestionsId($question['id']);
        }
        return $questions;
    }

    /**
     * SELECT a question and their answers with their id : ok
     **/
    public function selectOneWithAnswerForUpdate(int $id): array|false
    {
        $answerManager = new AnswerManager();
        $question = $this->selectOneById($id);

        if (!isset($question['id']) || empty($question['id'])) {
            return $question;
        }
        $answers = $answerManager->selectAllByQuestionsId($question['id']);

        foreach ($answers as $answer) {
            $question['answers'][] = $answer['answer'];
        }
        return $question;
    }

    /**
     * SELECT a question and their answers : ok
     **/
    public function selectOneWithAnswer(int $id): array|false
    {
        $answerManager = new AnswerManager();
        $question = $this->selectOneById($id);

        if (!isset($question['id']) || empty($question['id'])) {
            return $question;
        }
        $answers = $answerManager->selectAllByQuestionsId($question['id']);
        foreach ($answers as $answer) {
            $question['answers'][$answer['answer']] = $answer['isTrue'];
        }
        return $question;
    }

    /**
     * UPDATE all question and answer associated : ok
     **/
    public function update(array $questionInfos)
    {
        $statement = $this->pdo->prepare("UPDATE " . self::TABLE .
            " SET `content` = :question, `theme` = :theme, difficulty_level = :level WHERE id=:id");
        $statement->bindValue(':id', $questionInfos['id'], PDO::PARAM_INT);
        $statement->bindValue(':question', $questionInfos['question'], PDO::PARAM_STR);
        $statement->bindValue(':theme', $questionInfos['theme'], PDO::PARAM_STR);
        $statement->bindValue(':level', $questionInfos['level'], PDO::PARAM_STR);
        $statement->execute();
    }

    /**
     * Delete question from an ID : ok !
     **/
    public function delete(int $id): void
    {
        // prepared request
        $statement = $this->pdo->prepare('DELETE FROM ' . self::TABLE . ' WHERE id=:id');
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Insert question in BDD ok !
     **/
    public function insert(array $questions): int
    {
        $statement = $this->pdo->prepare('INSERT INTO ' . self::TABLE .
            ' (`content`, `theme`, `difficulty_level`) VALUES (:content, :theme, :level)');
        $statement->bindValue(':content', $questions['question'], PDO::PARAM_STR);
        $statement->bindValue(':theme', $questions['theme'], PDO::PARAM_STR);
        $statement->bindValue(':level', $questions['level'], PDO::PARAM_STR);

        $statement->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updatePicture(string $path, int $id)
    {
        $statement = $this->pdo->prepare('UPDATE ' . self::TABLE . ' SET image=:image WHERE id=:id');
        $statement->bindValue(':image', $path, PDO::PARAM_STR);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
    }
}
