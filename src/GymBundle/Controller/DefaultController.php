<?php

namespace GymBundle\Controller;

use GymBundle\Entity\Abonnement;
use GymBundle\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('@Gym/Layout/Layout.html.twig');
    }

    public function listabonnementAction()
    {
        $em = $this->getDoctrine()->getManager();
        $clients = $em->getRepository('GymBundle:Client')->findAll();
        return $this->render('@Gym/Abonnement/list.html.twig',array('clients'=>$clients));
    }

    public function createabonnementAction(Request $request)
    {
        if ($request->isMethod("POST")){
            $em = $this->getDoctrine()->getManager();
            $client = $em->getRepository('GymBundle:Client')->findOneBy(array('nom'=>strtolower($request->get('nom')),'prenom'=>strtolower($request->get('prenom'))));
            if($client == null){
                $client = new Client();
                $client->setNom(strtolower($request->get('nom')));
                $client->setPrenom(strtolower($request->get('prenom')));
                $client->setSexe($request->get('sexe'));
            }
            $abonnement = new Abonnement();
            $abonnement->setClient($client);
            $abonnement->setType($request->get('type'));
            $datedebut = new \DateTime($request->get('datedebut'));
            $abonnement->setDateDebut($datedebut);
            $dated = new \DateTime($request->get('datedebut'));
            $datefin = null;
            try {
                $datefin = $dated->add(new \DateInterval('P'.$request->get('duree').'M'));
            } catch (\Exception $e) {
            }
            $abonnement->setDateFin($datefin);
            $em->persist($abonnement);
            $em->flush();

        }
        return $this->render('@Gym/Abonnement/create.html.twig',array('today'=>new \DateTime('now')));
    }

    public function clientHistoryAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository('GymBundle:Client')->findOneBy(array('id'=>$id));
        return $this->render('@Gym/Abonnement/clientHistory.html.twig',array('abonnements'=>$client->getAbonnements(),'client'=>$client));
    }
}
