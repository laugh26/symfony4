<?php

namespace App\GameExtraBetting\Communication\Controller;


use App\GameCore\Persistence\Entity\Team;
use App\GameExtraBetting\Business\Form\UserExtraBettingType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserExtraBetting extends Controller
{
    const TIPP_DATE = '14.06.2018';
    /**
     * @Route("/gamebetextra/", name="game_bet_extra")
     */
    public function index()
    {
        $user = $this->getUser();
        
        $teams = $this->getTeams();
        $userExtraBet = $this->getUserExtraBet($user);
        $bet = new \App\GameExtraBetting\Persistence\Entity\UserExtraBetting();
        $form = $this->createForm(UserExtraBettingType::class, $bet,
                ['teams' => $teams, 'extrabet' => $userExtraBet]
                )->createView();

        return $this->render(
            'gameextrabetting/user_extra_betting/widget.html.twig',
            [
                'form' => $form,
                'betId' => $userExtraBet ? $userExtraBet->getId() : '',
            ]
        );
    }

    /**
     * @Route("/saveextrabet/", name="save_extra_bet")
     * @Method({"POST"})
     */
    public function saveExtraBet(Request $request)
    {
        $params = $request->get('user_extra_betting');

        $userExtraBetting = new \App\GameExtraBetting\Persistence\Entity\UserExtraBetting();
        $form = $this->createForm(UserExtraBettingType::class, $userExtraBetting);
        $form->handleRequest($request);
        if (!$form->isSubmitted() && !$form->isValid()) {
            return $this->redirectToRoute('game_bet_list', array(), 302);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $userExtraBetting = $entityManager->getRepository(\App\GameExtraBetting\Persistence\Entity\UserExtraBetting::class)
            ->find($params['betId']);

        if (!$userExtraBetting instanceof \App\GameExtraBetting\Persistence\Entity\UserExtraBetting) {
            $userExtraBetting = new \App\GameExtraBetting\Persistence\Entity\UserExtraBetting();
            $userExtraBetting->setUser($this->getUser());
        }

        $userExtraBetting->setDate(new \DateTime("now"));
        $userExtraBetting->setText($params['text']);
        //TODO: This must be changed
        $userExtraBetting->setType(1);

        $entityManager->persist($userExtraBetting);
        $entityManager->flush();

        return $this->redirectToRoute('game_bet_list');
    }

    private function getTeams()
    {
        $result = [];
        $teams = $this->getDoctrine()->getRepository(Team::class)
                      ->findAll();

        foreach ($teams as $team) {
            $result[$team->getName()] = $team->getId();
        }

        return $result;
    }

    private function getUserExtraBet($user)
    {
        return $this->getDoctrine()->getRepository(\App\GameExtraBetting\Persistence\Entity\UserExtraBetting::class)
                    ->findOneBy(['user' => $user->getId()]);
    }
}
