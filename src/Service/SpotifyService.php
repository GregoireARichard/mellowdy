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

    public function getSpotifyMe(string $userToken): array {
       $requestMe = $this->getHttpClient($userToken,'me');
       return json_decode($requestMe->getContent(), true);
    }

    public function getSpotifySearch(string $userToken):string {
        $track = 'Thriller';
        $artist = 'Michael Jackson';
        $limit = "2";
        $searchLink = sprintf("search?q=%s %s&type=track&market=FR&limit=%s",$artist,$track,$limit);
        $requestSearch = $this->getHttpClient($userToken,$searchLink);

        $query = json_decode($requestSearch->getContent(), true);
        $artistSeeds = $query['tracks']['items'][0]['artists']['0']['id'];
        $trackSeed = $query['tracks']['items'][0]['id'];
        $associatedQuery = sprintf('seed_artists=%s&seed_tracks=%s',$artistSeeds,$trackSeed);
        return $associatedQuery;
    }

    public function getSpotifyPlaylist( MellowUser $user): array{

        $sprintUser = sprintf('https://api.spotify.com/v1/users/%s/playlists', $user->getUsername());
        $data = '{"name": "Your Mellowdy Playlist", "description": "Your Mellowdy playlist ;)", "public": true}';
        $requestPlaylist = $this->httpClient->request('POST', $sprintUser,[
            'headers' =>[
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $user->getUserToken())
            ],
            'json' => $data
        ]);
        return json_decode($requestPlaylist->getContent(),true);
    }
    public function getSpotifyReco(string $userToken): string {
        $limit = '10';
        $artistSeed = '3xWktqKQxBAu4LXqLufJwW';
        $trackSeed = '6Kp7ThQnDcwDrImLpJt0GB';
        $seeds = $this->getSpotifySearch($userToken);
        $energy = '0.5';
        $instrumentalness = '0.5';
        $liveness = '0.5';
        $popularity = '50';
        $tempo = '100';
        $recoLink = sprintf(
            'recommendations?limit=%s&market=FR&%s&target_energy=%s&target_instrumentalness=%s&target_liveness=%s&target_popularity=%s&target_tempo=%s',
            $limit,
            $seeds,
            $energy,
            $instrumentalness,
            $liveness,
            $popularity,
            $tempo
        );
        $requestReco = $this->getHttpClient($userToken,$recoLink);

        $jsonData = json_decode($requestReco->getContent(),true);
        $tracksList = '';
        foreach ($jsonData['tracks'] as $track) {
            $tracksList .= sprintf('%s,', $track['uri']);
        }

        return $tracksList;
    }
    public function getSpotifyAddItem(MellowUser $userToken, string $userTokenString): array{
        $track = $this->getSpotifyReco($userTokenString);
        $data = sprintf('https://api.spotify.com/v1/playlists/0BGUl8SqpAcraHvWO2Kp16/tracks?uris=%s',$track);

        $addItemRequest = $this->httpClient->request('POST',$data, [
            'headers' =>[
                'Accept' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $userTokenString),
                'Content-Type' => 'application/json',
            ]
        ]);
        return json_decode($addItemRequest->getContent(), true);
    }

    public function storeUser($user_token): MellowUser
    {
        $me = $this->getSpotifyMe($user_token);

        $frontToken = substr(sha1($user_token), 0, 64);

        $user = new MellowUser();
        $user->setUserToken($user_token)
            ->setUsername($me['id'])
            ->setFrontToken($frontToken);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
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