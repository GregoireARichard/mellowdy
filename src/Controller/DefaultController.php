<?php

namespace App\Controller;

use App\Entity\NotionPage;
use App\Entity\User;
use App\Service\NotionService;
use App\Service\SpotifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;



class DefaultController extends AbstractController
{
    /**
     * @var SpotifyService
     */
    private $notionService;
    /**
     * @var HttpClientInterface
     */
    private $httpClient;
    private $logger;
    private $parameterBag;
    public function __construct(SpotifyService $spotifyService,
    HttpClientInterface $httpClient,
    LoggerInterface $logger,
    ParameterBagInterface  $parameterBag)

    {
        $this->SpotifyService = $spotifyService;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;

    }

    /**
     * @Route("/", name="default")
     */
    public function index(): Response
    {
        return $this->json('hello world.');
    }
    /**
     * @Route("/oauth", name="oauth")
     */
    public function oauth(): Response
    {
       $client_id = '47fcde357cd7454088ed5b0bf054c823';
       $location = 'http://127.0.0.1:8080/&exchange_token';
       $scope = 'user-read-playback-state playlist-read-private playlist-read-collaborative playlist-modify-private playlist-modify-public';

       $oauth_string = sprintf(
           'https://accounts.spotify.com/authorize?client_id=%s&response_type=code&redirect_uri=%s&scope=%s',
           $client_id,
           $location,
           $scope
        );
        return $this->redirect($oauth_string);
    }

    /**
     * @Route("/exchange_token",name="exchange_token")
     */
    public function exchange_token(Request $request): Response{
        $authorization_code = $request->get('code');
        try {
            $header = [
                'Authorization' => 'basic' . base64_encode('47fcde357cd7454088ed5b0bf054c823'. ':' . 'e537d899f2b84318942fea52e41d9428'),
                'Content-Type' => 'application/x-www-form-urlencode',
            ];
            $body = [
                'client_id' => '47fcde357cd7454088ed5b0bf054c823',
                'client_secret' => 'e537d899f2b84318942fea52e41d9428',
                'code' => $authorization_code,
                'grant_type' => 'authorization_code',
                'redirection_uri' =>'localhost:8080/exchange_token',
            ];

            $response = $this->httpClient->request(
                'POST',
                'https://accounts.spotify.com/api/token',
                ['header' => $header , 'body' => $body ]

            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'Error : %s',
                    $e->getMessage()
                )
            );
            return $this->json($e->getMessage());
        }

        $json_response = json_decode($response->getContent(), true);
        $token = $json_response['access_token'];

        $filesystem = new Filesystem();
        $filesystem->dumpFile(sprintf('%s/%s', $this->getParameter('kernel.project_dir'), 'private/token'), $token);

        return $this->json('oAuth login successful.');

    }

    /**
     * @Route("/savepages", name="savePages")
     */
    public function savePages(): Response
    {
        $this->spotifyService->storeNotionPages();

        return $this->json('Notion pages saved.');
    }

    /**
     * @Route("/notionpages", name="notionPage")
     */
    public function getNotionPages(): Response
    {
        $pages = $this->getDoctrine()->getRepository(NotionPage::class)->findAll();

        $returnArray = [];

        /** @var NotionPages $page */
        foreach ($pages as $page) {
            $returnArray[] = [
                'id' => $page->getId(),
                'notionId' => $page->getNotionId(),
                'title' => $page->getTitle(),
                'creationDate' => $page->getCreationDate()->format(DATE_ATOM),
            ];
        }

        return $this->json($returnArray);
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(Request $request): Response
    {
        $params = json_decode($request->getContent(), true);

        if (!isset($params['username']) || empty($params['username'])) {
            throw new HttpException(400, 'Missing username parameter.');
        }

        if (!isset($params['email']) || empty($params['email'])) {
            throw new HttpException(400, 'Missing email parameter.');
        }

        $entityManager = $this->getDoctrine()->getManager();

        $user = $entityManager->getRepository(User::class)->findOneByEmail($params['email']);

        if (null === $user) {
            $user = new User();
        }

        $user->setUsername($params['username'])
            ->setEmail($params['email'])
        ;

        $entityManager->persist($user);
        $entityManager->flush();

        $returnArray = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ];

        return $this->json($returnArray);
    }

    /**
     * @Route("/error", name="error")
     */
    public function error(): Response
    {
        return $this->json('nope.');
    }
}