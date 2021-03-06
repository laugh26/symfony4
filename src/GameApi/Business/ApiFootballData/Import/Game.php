<?php


namespace App\GameApi\Business\ApiFootballData\Import;


use App\GameApi\Business\ApiFootballData\ClientInterface;
use App\GameCore\Persistence\Entity\Game as GameEntity;
use App\GameCore\Persistence\Entity\Team as TeamEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class Game implements GameInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ClientInterface $client
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ClientInterface $client, EntityManagerInterface $entityManager)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
    }


    public function import(): void
    {
        $games = $this->client->getGames();

        $teamName2Entity = $this->getTeamsEntities();
        $gameRepository = $this->entityManager->getRepository(GameEntity::class);
        foreach ($games['fixtures'] as $game) {
            if (!empty($game['homeTeamName']) && !empty($game['awayTeamName'])) {
                $dateTime = new \DateTime($game['date'], new \DateTimeZone('UTC'));
                $dateTime->setTimezone(new \DateTimeZone('Europe/Berlin'));
                $gameEntity = $gameRepository->findOneBy([
                    'teamFirst' => $teamName2Entity[$game['homeTeamName']],
                    'teamSecond' => $teamName2Entity[$game['awayTeamName']]
                ]);

                if (!$gameEntity instanceof GameEntity) {
                    $gameEntity = new GameEntity();
                }
                $gameEntity->setDate($dateTime);
                $gameEntity->setTeamFirst($teamName2Entity[$game['homeTeamName']]);
                $gameEntity->setTeamSecond($teamName2Entity[$game['awayTeamName']]);
                $gameEntity->setFirstTeamResult($this->getHomeGoals($game['result']));
                $gameEntity->setSecondTeamResult($this->getAwayGoals($game['result']));
                $this->entityManager->persist($gameEntity);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @param array $result
     * @return int|null
     */
    private function getHomeGoals(array $result): ?int
    {
        $goals = $result['goalsHomeTeam'];

        if (isset($result['extraTime'])) {
            $goals = $result['extraTime']['goalsHomeTeam'];
        }

        if (isset($result['penaltyShootout'])) {
            $goals += $result['penaltyShootout']['goalsHomeTeam'];
        }

        return $goals;
    }

    /**
     * @param array $result
     * @return int|null
     */
    private function getAwayGoals(array $result): ?int
    {
        $goals = $result['goalsAwayTeam'];

        if (isset($result['extraTime'])) {
            $goals = $result['extraTime']['goalsAwayTeam'];
        }

        if (isset($result['penaltyShootout'])) {
            $goals += $result['penaltyShootout']['goalsAwayTeam'];
        }

        return $goals;
    }

    /**
     * @return TeamEntity[]
     */
    private function getTeamsEntities(): array
    {
        $teamRepository = $this->entityManager->getRepository(TeamEntity::class);
        $teamEntitys = $teamRepository->findAll();
        $teamName2Entity = [];
        foreach ($teamEntitys as $entity) {
            $teamName2Entity[$entity->getName()] = $entity;
        }
        return $teamName2Entity;
    }

}
