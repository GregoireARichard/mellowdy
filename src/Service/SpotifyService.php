<?php

namespace App\Service;

use App\Entity\MellowUser;
use App\Repository\MellowUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;



class SpotifyService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;
    /**
     * @var ParameterBagInterface
     */
    private $ParameterBagInterface;
    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient,
        ParameterBagInterface  $parameterBag
    ){
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
    }

    public function getHttpClient($userToken, $route) {
        return $this->httpClient->request('GET', sprintf('https://api.spotify.com/v1/%s', $route), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $userToken)]
        ]);
    }
    public function postHttpClient($userToken, $route){
        return $this->httpClient->request('POST', sprintf('https://api.spotify.com/v1/%s', $route),[
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $userToken)]
        ]);
    }

    public function getSpotifyMe(string $userToken): array {
       $requestMe = $this->getHttpClient($userToken,'me');
       return json_decode($requestMe->getContent(), true);
    }
    public function getSpotifySearch(string $userToken):array {
        $track = 'tightly';
        $artist = 'kosheen';
        $limit = "10";
        $searchLink = sprintf("search?q=%s %s&type=track&market=FR&limit=%s",$artist,$track,$limit);
        $requestSearch = $this->getHttpClient($userToken,$searchLink);
        return json_decode($requestSearch->getContent(), true);
    }

    public function getSpotifyPlaylist( string $userToken): array{
        $returnPl =  $this->entityManager->getRepository(MellowUser::class)->find('Username');
        $fetchUsername= $this->getUsername();
        $sprintUser = sprintf('https://api.spotify.com/v1/users/%s/playlists', $fetchUsername);
        $data = '{"name": "New Playlist", "description": "Your Mellowdy playlist ;)", "public": true}';
        $createLink = sprintf('/users/%s/playlists',$returnPl);
        $requestPlaylist = $this->httpClient->request('POST', $sprintUser,[
            'headers' =>[
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $userToken)
            ],
            'body' => $data
        ]);
        return json_decode($requestPlaylist->getContent(),true);
    }
    public function getSpotifyReco(string $userToken): array {
        $limit = '10';
        $artistSeed = '3xWktqKQxBAu4LXqLufJwW';
        $trackSeed = '6Kp7ThQnDcwDrImLpJt0GB';
        $energy = '0.5';
        $instrumentalness = '0.5';
        $liveness = '0.5';
        $popularity = '50';
        $tempo = '100';
        $recoLink = sprintf('recommendations?limit=%s&market=FR&seed_artists=%s&seed_tracks=%s&target_energy=%s&target_instrumentalness=%s&target_liveness=%s&target_popularity=%s&target_tempo=%s',
        $limit, $artistSeed,$trackSeed,$energy,$instrumentalness,$liveness,$popularity,$tempo);
        $requestReco = $this->getHttpClient($userToken,$recoLink);

        return json_decode($requestReco->getContent(),true);
    }
    public function getSpotifyAddItem(): array{
        $track = 'spotify:track:33jnIhEmkDWRNGDqBTv9LP';
        $data = sprintf('https://api.spotify.com/v1/playlists/0BGUl8SqpAcraHvWO2Kp16/tracks?uris=%s',$track);

        $addItemRequest = $this->httpClient->request('POST',$data, [
            'headers' =>[
                'Accept' => 'application/json',
                'Authorization' => 'Bearer BQAowEHxSNq9WTb-gWdHAkLZG2_ekZsm5X_YLNnCxLZh2ApsBzK5Rwep-sCw7i7_wzLCa0WAygyADmRZGVLTf_RuMiFLABaYie8SoG8ek2Zcuagt248syMvcCmd4PjGz3TksXKB_61WmLgTJw-haoCtaaXd1XhBigsGYLMxthpYlDUoVS9Uuo4jG1ucYdzmsaQuIo9MjGswsTrpq3ds-uzEUSSa0aT8Z',
                'Content-Type' => 'application/json',
            ]
        ]);
        return json_decode($addItemRequest->getContent(), true);
    }
   /* public function StoreTokenUser(): array {
        $requestMe = $this->getSpotifyMe();
        $spotifyToken = [];
        $existingToken = $this->entityManager->getRepository(MellowUserRepository::class)->findOneByNotionId($page['id']);
    }*/
    public function storeUser($user_token): MellowUser
    {
        $me = $this->getSpotifyMe($user_token);

        $frontToken = substr(sha1($me['id']), 0, 64);

        $user = new MellowUser();
        $user->setUserToken($user_token)
            ->setUsername($me['id'])
            ->setFrontToken($frontToken);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function getNotionPage(): array
    {

        $spotifyBaseUrl = $this->parameterBag->get('base_url');
        $client_id = $this->parameterBag->get('client_id');

        /*$notionSearchUrl = sprintf('%s/search', $spotifyBaseUrl);
        $authorizationHeader = sprintf('Bearer %s', $client_id);

        $pages = $this->httpClient->request('POST', $notionSearchUrl, [
            'body' => [
                'query' => '',
            ],
            'headers' => [
                'Authorization' => $authorizationHeader,
                'Notion-version' => "2021-08-16",
            ],
        ]);

        return json_decode($pages->getContent(), true);*/
    }
    public function StoreToken(): array {
        $pages = $this->getNotionPage();

        $notionPages = [];

        foreach($pages['results'] as $page)
        {
            $existingNotionPage = $this->entityManager->getRepository(NotionPage::class)->findOneByNotionId($page['id']);

            if (null !== $existingNotionPage) {
                continue;
            }
            $notionPage = new NotionPage();
            if ( isset($page['properties']['title']) ) {
                $title = substr($page['properties']['title']['title'][0]['plain_text'], 0, 255);
            } else {
                $title = 'No title';
            }

            $creationDate = new \DateTime($page['created_time']);

            $notionPage->setTitle($title);
            $notionPage->setNotionId($page['id']);
            $notionPage->setCreationDate($creationDate);

            $this->entityManager->persist($notionPage);
            $notionPages[] = $notionPage;
        }

        $this->entityManager->flush();


        return $notionPages;
    }
}