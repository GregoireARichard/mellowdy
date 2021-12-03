<?php

namespace App\Controller;

use App\Entity\NotionPage;
use App\Entity\User;
//use App\Repository\MellowUserRepository;
use App\Service\NotionService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SpotifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
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
    private $entityManager;
    public function __construct(SpotifyService $spotifyService,
    HttpClientInterface $httpClient,
    LoggerInterface $logger,
    ParameterBagInterface  $parameterBag,
    EntityManagerInterface $entityManager)


    {
        $this->spotifyService = $spotifyService;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;

    }

    /**
     * @Route("/", name="default")
     */
    public function index(): Response
    {
        $token = 'Bearer BQDuTVWWu3vXxcstb4emlSuw6kMwMWfvoa5rrn9YFS8gapcmUlQ4Obf2MHousID2A40fJcLrfGEvSF_riiPxyVY8FKagxi7wGlK_YjVR2GXXoYSa5B9kOAB1GRgB4TDnH15pIVIFxP-DsGNeKZDcm_QFXC1uxBAQybbUSdYA61rYs0aLIfta29jjQCOalQi54gTKpYAjBe6oUP6De2yX9glx2XV2eMyt';
       // $fetch = $this->entityManager->getRepository(MellowUserRepository::class)->find(['user_token']);
       // $token = $fetch->getUserToken();
        //$this->spotifyService->getSpotifyReco($token);
        //$return = $this->spotifyService->getSpotifyReco($token);

        $this->spotifyService->getSpotifyPlaylist($token);
        //$this->spotifyService->getSpotifySearch();
        //$this->spotifyService->storeUser();
       // $this->spotifyService->getSpotifyAddItem();
        //$this->spotifyService->getSpotifyReco($token);
        //return $this->json($return);
        //return $this->json($return['tracks']['items'][0]['uri']);
       //$returnq = $this->spotifyService->getSpotifyAddItem();
       //return $this->json($returnq);


    }
    /**
     * @Route("/oauth", name="oauth")
     */
    public function oauth(): Response
    {
       $client_id = '47fcde357cd7454088ed5b0bf054c823';
       $location = 'http://127.0.0.1:8080/exchange_token';
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
        $client_secret = $this->parameterBag->get('client_secret');
        try {
            $basicAuth = base64_encode(sprintf('%s:%s',  '47fcde357cd7454088ed5b0bf054c823', $client_secret));
            $header = [
                'Authorization' => sprintf('Basic %s', $basicAuth),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];
            $body = [
                'client_id' => '47fcde357cd7454088ed5b0bf054c823',
                'client_secret' => $client_secret,
                'code' => $authorization_code,
                'grant_type' => 'authorization_code',
                'redirect_uri' =>'http://127.0.0.1:8080/exchange_token',
            ];

            $response = $this->httpClient->request(
                'POST',
                'https://accounts.spotify.com/api/token',
                ['headers' => $header , 'body' => $body ]

            );
            $json_response = json_decode($response->getContent(), true);
        } catch (ClientException $e) {
            $this->logger->error(
                sprintf(
                    'Error : %s',
                    $e->getMessage()
                )
            );

            return $this->json($e->getMessage());
        }


        $user_token = $json_response['access_token'];
        $this->spotifyService->storeUser($user_token);
        return $this->json($json_response);
        // Redirect to front-end with front token as a query param
        // localhost:3000/?frontToken=xxxxx
        //return $this->redirect('/');

    }

    /**
     * @Route("/savepages", name="savePages")
     */
    public function saveUser(Request $request): Response
    {
        $user = $this->userService->getUserFromRequest($request);
        if (null === $user) {
            return new Response('Unauthorized', 401);
        }

        $user->getToken();
        $this->spotifyService->getSpotifyMe($user->getToken());

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