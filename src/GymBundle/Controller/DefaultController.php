<?php

namespace GymBundle\Controller;

use Ob\HighchartsBundle\Highcharts\Highchart;
use GymBundle\Entity\Abonnement;
use GymBundle\Entity\Client;
use Ob\HighchartsBundle\Twig\HighchartsExtension;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $clients_musculation_mois = $em->getRepository('GymBundle:Abonnement')->findByTypeCurrentMonth('musculation');
        $clients_boxe_mois = $em->getRepository('GymBundle:Abonnement')->findByTypeCurrentMonth('boxe');
        return $this->render('@Gym/Layout/Layout.html.twig');
    }

    public function listclientsAction()
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
            return $this->redirectToRoute('gym_abonnement_list');
        }
        return $this->render('@Gym/Abonnement/create.html.twig',array('today'=>new \DateTime('now')));
    }

    public function clientHistoryAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository('GymBundle:Client')->findOneBy(array('id'=>$id));
        return $this->render('@Gym/Abonnement/clientHistory.html.twig',array('abonnements'=>$client->getAbonnements(),'client'=>$client));
    }

    public function abonnementListAction()
    {
        $em = $this->getDoctrine()->getManager();
        $abonnements = $em->getRepository('GymBundle:Abonnement')->getCurrentAbonnement();
        return $this->render('@Gym/Abonnement/list_abonnements.html.twig',array('abonnements'=>$abonnements));
    }

    public function checkAction()
    {
        $em = $this->getDoctrine()->getManager();
        $twilio = $this->get('twilio.api');
        $message = $twilio->account->messages->sendMessage(
            '+16672184673', // From a Twilio number in your account
            '+21699407417', // Text any number
            "Test SMS!"
        );
        var_dump($message);
        die();
    }

    public function abonnementArchiveAction()
    {
        $em = $this->getDoctrine()->getManager();
        $abonnements = $em->getRepository('GymBundle:Abonnement')->getOutDatedAbonnement();
        return $this->render('@Gym/Abonnement/list_abonnements.html.twig',array('abonnements'=>$abonnements));
    }

    public function chartAction()
    {

        $em = $this->getDoctrine()->getManager();
        $abonnementRepo = $em->getRepository('GymBundle:Abonnement');

        $ob = new Highchart();
        $ob->chart->renderTo('container');
        $ob->chart->type('column');
        $ob->title->text('Nombre d\'abonnements par catégorie.');
        $ob->xAxis->categories(array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre'));
        $ob->yAxis(array(
                'min'=>0,
                'title'=>array('text'=>'Totale abonnements'),
                'allowDecimals'=>false,
                'stackLabels'=>array(
                    'enabled'=>true,
                    'style'=>array(
                        'fontWeight'=>'bold',
                        'color'=>'gray'
                        )
                    )
        )
        );
        $ob->plotOptions->series(
            array(
                'stacking'=> 'normal',
                'dataLabels' => array(
                    'enabled' => true,
                    'style' => array('fontSize'=>'20px'),
                    'color'=>'white',
                )
            )
        );

        $ob->tooltip->headerFormat('<b>{point.x}</b><br/>');
        $ob->tooltip->pointFormat('{series.name}: {point.y}<br/>Total: {point.stackTotal}');

        $ob->legend
            ->align('right')
            ->verticalAlign('top')
            ->x(-30)
            ->y(25)
            ->floating(true)
            ->backgroundColor('white')
            ->borderColor('#CCC')
            ->borderWidth(1)
            ->shadow(false);

        $dataM=array();
        $dataB=array();
        for($i=1;$i<=12;$i++){
            array_push($dataM,(int)$abonnementRepo->getAbonnementsByMonth($i,'Musculation'));
            array_push($dataB,(int)$abonnementRepo->getAbonnementsByMonth($i,'Boxe'));
        }
        $data = array(
            array(
                'name' => 'Musculation',
                'data' => $dataM,//[5, 3, 4, 7, 2],
                'color' => '#f45b5b'
            ),
            array(
                'name' => 'Boxe',
                'data' => $dataB,//[2, 2, 3, 2, 0],
                'color' => '#7cb5ec'
            ),

        );
        $ob->series($data);

        $ob2 = new Highchart();
        $ob2->chart->renderTo('genrechart');
        $ob2->chart->type('column');
        $ob2->title->text('Nombre d\'abonnements par genre.');
        $ob2->xAxis->categories(array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre'));
        $ob2->yAxis(array(
                'min'=>0,
                'title'=>array('text'=>'Totale abonnements'),
                'allowDecimals'=>false,
                'stackLabels'=>array(
                    'enabled'=>true,
                    'style'=>array(
                        'fontWeight'=>'bold',
                        'color'=>'gray'
                    )
                )
            )
        );
        $ob2->plotOptions->series(
            array(
                'stacking'=> 'normal',
                'dataLabels' => array(
                    'enabled' => true,
                    'style' => array('fontSize'=>'20px'),
                    'color'=>'white',
                )
            )
        );

        $ob2->tooltip->headerFormat('<b>{point.x}</b><br/>');
        $ob2->tooltip->pointFormat('{series.name}: {point.y}<br/>Total: {point.stackTotal}');

        $ob2->legend
            ->align('right')
            ->verticalAlign('top')
            ->x(-30)
            ->y(25)
            ->floating(true)
            ->backgroundColor('white')
            ->borderColor('#CCC')
            ->borderWidth(1)
            ->shadow(false);

        $dataH=array();
        $dataF=array();
        for($i=1;$i<=12;$i++){
            array_push($dataH,(int)$abonnementRepo->getAbonnementsByMonthAndGender($i,'Homme'));
            array_push($dataF,(int)$abonnementRepo->getAbonnementsByMonthAndGender($i,'Femme'));
        }
        $data2 = array(
            array(
                'name' => 'Femme',
                'data' => $dataF,//[2, 2, 3, 2, 0],
                'color' => '#f45b5b'
            ),
            array(
                'name' => 'Homme',
                'data' => $dataH,//[5, 3, 4, 7, 2],
                'color' => '#7cb5ec'
            ),


        );
        $ob2->series($data2);

        //ToDo; Revenu par mois Controlleur
        // MoneyChart
        $moneydata=array();
        for($i=1;$i<=12;$i++){
            array_push($moneydata,$abonnementRepo->getRevenuByMonth($i));
        }
        $series3 = array(
            array(
                "name" => "Revenu",
                "data" => $moneydata,
                "step"=> 'left', // or 'center' or 'right',
                "fillOpacity" => 0.3,
                "color"=>"#066492",
                //"pointPlacement"=>"between"
            ),
        );
        $ob3 = new Highchart();
        $ob3->chart->type('area')->renderTo('moneylinechart');  // The #id of the div where to render the chart
        $ob3->title->text('Revenu par mois');
        $ob3->xAxis->labels(array('align'=>'left'))->gridLineWidth(2)->
        categories(array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre'));
        $ob3->yAxis->labels(array('format'=> '{value} DT'))->title(array('text'=>'Revenu en DT'));
        $ob3->plotOptions->area(array(
            'stacking'=>'normal',
            'step'=> 'right'
        ));
        $ob3->series($series3);
        return $this->render('@Gym/dashboard.html.twig', array(
            'chart' => $ob,'genrechart' => $ob2,'moneylinechart' => $ob3
        ));
    }
}
