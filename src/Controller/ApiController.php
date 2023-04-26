<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use App\Repository\ThemeRepository;
use App\Repository\ResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends AbstractController
{

    private ThemeRepository $themeRepository;
    private ResponseRepository $responseRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private QuestionRepository $questionRepository;

    /**
     * @param ThemeRepository $themeRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param QuestionRepository $questionRepository
     */public function __construct(ThemeRepository $themeRepository, ResponseRepository $responseRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, QuestionRepository $questionRepository)
    {
        $this->themeRepository = $themeRepository;
        $this->responseRepository = $responseRepository;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->questionRepository = $questionRepository;
    }

    #[Route('/api/themes/get', name: 'app_api_themes_get')]
    public function themesGet(): Response
    {

        $themes = $this->themeRepository->findAll();

        $json = $this->serializer->serialize($themes, "json", ['groups' => 'get_themes']);

        return new Response($json, 200, []);

    }

    #[Route('/api/quizz/get/{themeId}/{nbQuestion}', name: 'app_api_generate_quizz')]
    public function generateQuizz(int $themeId, int $nbQuestion): Response
    {

        $sql = "
            SELECT q.id, q.libelle 
            FROM question q INNER JOIN theme t ON q.theme_id = t.id 
            WHERE t.id = :themeId 
            ORDER BY RAND() 
            LIMIT :nbQuestion
        ";

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('App\Entity\Question', 'q');
        $rsm->addFieldResult('q', 'id', 'id');
        $rsm->addFieldResult('q', 'libelle', 'libelle');

        $query = $this->entityManager->createNativeQuery($sql, $rsm);
        $query->setParameter('themeId', $themeId);
        $query->setParameter('nbQuestion', $nbQuestion);

        $questions = $query->getResult();

        $json = $this->serializer->serialize($questions, "json", ['groups' => 'get_questions']);

        return new Response($json, 200, []);

    }

    #[Route('/api/question/responses/get/{questionId}', name: 'app_api_get_question_responses')]
    public function getQuestionResponses(int $questionId): Response
    {

        $question = $this->questionRepository->find($questionId);

        $responses = $question->getResponses();

        $json = $this->serializer->serialize($responses, "json", ['groups' => 'get_responses']);

        return new Response($json, 200, []);

    }

    #[Route('/api/question/responses/post', name: 'app_api_post_question_responses')]
    public function postQuestionResponses(Request $request): Response
    {
        $count = 0;

        $reponses = $request->toArray();

        foreach($reponses as $reponse) {

            if ($this->responseRepository->find($reponse)->isCorrect()) {

                $count ++;

            }

        }

        return new Response($count , 200, []);

    }
}
