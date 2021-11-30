<?php

namespace App\Service;

use App\Entity\NotionPage;
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
    public function storeNotionPage(): array {
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