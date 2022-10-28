<?php

namespace App\Controller;

use App\Model\AnswerManager;
use App\Model\QuestionManager;

class QuestionController extends AbstractController
{
    private QuestionManager $questionManager;
    public const THEME = ['Culture Générale', 'Sciences', 'Sport'];
    public const LEVEL = ['facile', 'moyen', 'difficile'];

    public function __construct()
    {
        parent::__construct();
        $this->questionManager = new QuestionManager();
    }
    /**
     * List items
     */
    public function index(): string
    {
        $questions = $this->questionManager->selectAllWithAnswer();
        return $this->twig->render('Admin/show.html.twig', ['questions' => $questions]);
    }

    /**
     * Add a new item
     */
    public function add(): ?string
    {
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // clean $_POST data
            $questionInfos = array_map('trim', $_POST);
            //$questionInfos = array_map('htmlspecialchars', $questionInfos);

            // TODO validations (length, format...)
            $errors = $this->validate($questionInfos);

            // if validation is ok, insert and redirection
            if (empty($errors)) {
                $lastId = $this->questionManager->insert($questionInfos);
                $answerManager = new AnswerManager();
                $answerManager->insertAtId($questionInfos, $lastId);

                echo 'le dernier index est ' . $lastId;
                header('Location:/admin/show');
                return null;
            }

            return $this->twig->render('Admin/add.html.twig', ['questionsInfos' => $questionInfos,
                                                                'errors' => $errors]);
        }

        return $this->twig->render('Admin/add.html.twig', ['errors' => $errors]);
    }

    // private function isCompleted(string $adminInput) : bool
    // {
    //     return (!isset($adminInput) || empty($adminInput));

    //                 // if ($this->isCompleted($adminInput)) {
    //         //     $errors[$field] = 'Ce champ doit être complété';
    //         // }
    // }



    private function validate(array $questionInfos)
    {
        $errors = [];
        foreach ($questionInfos as $field => $adminInput) {
            $adminInput ?: $errors[$field] = 'Ce champ doit être complété';
        }

        if (!in_array($questionInfos['theme'], self::THEME)) {
            $errors['theme'] = 'Le thème choisit doit être dans la liste';
        }

        if (strlen($questionInfos['question']) > 500) {
            $errors['question'] = 'La question doit faire moins de 500 charactères';
        }

        if (!in_array(strtolower($questionInfos['level']), self::LEVEL)) {
            $errors['level'] = 'Le niveau choisit doit être facile, moyen ou difficile';
        }

        if (strlen($questionInfos['goodAnswer']) > 255) {
            $errors['goodAnswer'] = 'La bonne réponse doit faire moins de 255 charactères';
        }

        for ($i = 1; $i <= 3; $i++) {
            if (strlen($questionInfos['badAnswer' . $i]) > 255) {
                $errors['badAnswer' . $i] = 'La mauvaise réponse numéro ' . $i . ' doit faire moins de 255 charactères';
            }
        }
        return $errors;
    }

    /**
     * Delete a specific item
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = trim($_POST['id']);
            $id = htmlentities($id);
            $this->questionManager->delete((int)$id);
            header('Location:/admin/show');
        }
    }
}