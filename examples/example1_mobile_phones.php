<?php

require dirname(__DIR__) . '/Alternative.php';
require dirname(__DIR__) . '/Criteria.php';
require dirname(__DIR__) . '/ElectreSolver.php';

$criterias = array();
$criterias['price'] = Criteria::make()->setName('price')->setType(Criteria::TYPE_MIN)->setWeight(0.4);
$criterias['memory'] = Criteria::make()->setName('memory')->setType(Criteria::TYPE_MAX)->setWeight(0.2);
$criterias['resolution'] = Criteria::make()->setName('resolution')->setType(Criteria::TYPE_MAX)->setWeight(0.1);
$criterias['battery'] = Criteria::make()->setName('battery')->setType(Criteria::TYPE_MAX)->setWeight(0.3);


$alternatives = array();
$alternatives[] = Alternative::make()->setName('Iphone 5S')
    ->addCriteria($criterias['price'], 7000)
    ->addCriteria($criterias['memory'], 64)
    ->addCriteria($criterias['resolution'], 326)
    ->addCriteria($criterias['battery'], 1560);


$alternatives[] = Alternative::make()->setName('Galaxy S5 mini')
    ->addCriteria($criterias['price'], 3300)
    ->addCriteria($criterias['memory'], 16)
    ->addCriteria($criterias['resolution'], 326)
    ->addCriteria($criterias['battery'], 2100);


$alternatives[] = Alternative::make()->setName('HTC One')
    ->addCriteria($criterias['price'], 5500)
    ->addCriteria($criterias['memory'], 16)
    ->addCriteria($criterias['resolution'], 441)
    ->addCriteria($criterias['battery'], 2300);

$solver = new ElectreSolver($alternatives, $criterias);
$solver->run(true);

