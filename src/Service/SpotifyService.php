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
        $requestUrl = $_SERVER['REQUEST_URI'];
        $url_component = parse_url($requestUrl);
        parse_str($url_component['query'], $params);
        $track = $params['artist'];
        $artist = $params['track'];
        $limit = "2";
        $searchLink = sprintf("search?q=%s %s&type=track&market=FR&limit=%s",$artist,$track,$limit);
        $requestSearch = $this->getHttpClient($userToken,$searchLink);
        $query = json_decode($requestSearch->getContent(), true);
        $artistSeeds = $query['tracks']['items'][0]['artists'][0]['id'];
        $trackSeed = $query['tracks']['items'][0]['id'];
        $associatedQuery = sprintf('seed_artists=%s&seed_tracks=%s',$artistSeeds,$trackSeed);
        return $associatedQuery;
    }

    public function getSpotifyPlaylist( MellowUser $user): string{
        $sprintUser = sprintf('https://api.spotify.com/v1/users/%s/playlists', $user->getUsername());
        $data = '{"name": "Your Mellowdy Playlist", "description": "Your Mellowdy playlist by https://mellowdy.fr ;)", "public": true}';
        $requestPlaylist = $this->httpClient->request('POST', $sprintUser,[
            'headers' =>[
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', $user->getUserToken())
            ],
            'body' => $data
        ]);
        $playlistId = json_decode($requestPlaylist->getContent(),true);
        return $playlistId['id'];
    }
    public function getSpotifyReco(string $userToken): string {
        $requestUrl = $_SERVER['REQUEST_URI'];
        $url_component = parse_url($requestUrl);
        parse_str($url_component['query'], $params);
        $limit = $params['limit'];
        $seeds = $this->getSpotifySearch($userToken);
        $energy = $params['energy'];
        $instrumentalness = $params['instrumentalness'];
        $liveness = $params['liveness'];
        $popularity = $params['popularity'];
        $tempo = $params['tempo'];
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
        $playlistId = $this->getSpotifyPlaylist($userToken);
        $data = sprintf('https://api.spotify.com/v1/playlists/%s/tracks?uris=%s',$playlistId,$track);

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

}