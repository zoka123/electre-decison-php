<?php

require dirname(__DIR__) . '/Alternative.php';
require dirname(__DIR__) . '/Criteria.php';
require dirname(__DIR__) . '/ElectreSolver.php';

$criterias = array();
$criterias['price'] = Criteria::make()->setName('price')->setType(Criteria::TYPE_MIN)->setWeight(0.4);
$criterias['onTime'] = Criteria::make()->setName('onTime')->setType(Criteria::TYPE_MIN)->setWeight(0.1);
$criterias['recommendations'] = Criteria::make()->setName('recommendations')->setType(Criteria::TYPE_MAX)->setWeight(0.3);
$criterias['bonity'] = Criteria::make()->setName('bonity')->setType(Criteria::TYPE_MAX)->setWeight(0.2);


$alternatives = array();
$alternatives[] = Alternative::make()->setName('Tempo')
    ->addCriteria($criterias['price'], 2.1)
    ->addCriteria($criterias['onTime'], 60)
    ->addCriteria($criterias['recommendations'], 3)
    ->addCriteria($criterias['bonity'], 10);

$alternatives[] = Alternative::make()->setName('Konstruktor')
    ->addCriteria($criterias['price'], 2.3)
    ->addCriteria($criterias['onTime'], 65)
    ->addCriteria($criterias['recommendations'], 2)
    ->addCriteria($criterias['bonity'], 4);

$alternatives[] = Alternative::make()->setName('Zagorje')
    ->addCriteria($criterias['price'], 1.8)
    ->addCriteria($criterias['onTime'], 70)
    ->addCriteria($criterias['recommendations'], 5)
    ->addCriteria($criterias['bonity'], 6);


$alternatives[] = Alternative::make()->setName('Tehnobeton')
    ->addCriteria($criterias['price'], 1.9)
    ->addCriteria($criterias['onTime'], 80)
    ->addCriteria($criterias['recommendations'], 4)
    ->addCriteria($criterias['bonity'], 10);

$alternatives[] = Alternative::make()->setName('Grad')
    ->addCriteria($criterias['price'], 2)
    ->addCriteria($criterias['onTime'], 70)
    ->addCriteria($criterias['recommendations'], 4)
    ->addCriteria($criterias['bonity'], 7);


$solver = new ElectreSolver($alternatives, $criterias);
$solver->run(true);
