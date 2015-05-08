<?php

require 'Alternative.php';
require 'Criteria.php';

$price = Criteria::make()->setName('price')->setType(Criteria::TYPE_MIN)->setWeight(0.4);
$weight = Criteria::make()->setName('weight')->setType(Criteria::TYPE_MIN)->setWeight(0.1);
$camera = Criteria::make()->setName('camera')->setType(Criteria::TYPE_MAX)->setWeight(0.2);
$memory = Criteria::make()->setName('memory')->setType(Criteria::TYPE_MAX)->setWeight(0.3);

$S6 = Alternative::make()->setName('Galaxy S6')
    ->addCriteria($price, 6945)
    ->addCriteria($weight, 132)
    ->addCriteria($camera, 16)
    ->addCriteria($memory, 3);


$iphone6 = Alternative::make()->setName('Iphone 6')
    ->addCriteria($price, 6645)
    ->addCriteria($weight, 129)
    ->addCriteria($camera, 8)
    ->addCriteria($memory, 1);


$LgG3 = Alternative::make()->setName('LG G3')
    ->addCriteria($price, 3545)
    ->addCriteria($weight, 153)
    ->addCriteria($camera, 13)
    ->addCriteria($memory, 3);


$alternatives = array($S6, $iphone6, $LgG3);

// 1. find ideals
$idealPrice = null;
$idealMem = null;
$idealResolution = null;
$idealBattery = null;

$criterias = array($price, $weight, $camera, $memory);
$ideals = array();


/** @var Criteria $criteria */
foreach ($criterias as $criteria) {
    $ideal = null;
    $type = $criteria->getType();

    $values = array();

    /** @var Alternative $alternative */
    foreach ($alternatives as $alternative) {
        $values[] = $alternative->getCriteriaValue($criteria->getName());
    }

    if ($criteria->getType() === Criteria::TYPE_MAX) {
        $ideal = max($values);
    } else {
        $ideal = min($values);
    }

    $ideals[$criteria->getName()] = $ideal;
}

echo 'IDEAL values' . PHP_EOL;
print_r($ideals);
echo PHP_EOL;


echo 'NORMALIZATION' . PHP_EOL;
// Normalization
/** @var Alternative $alternative */
foreach ($alternatives as $alternative) {
    /** @var Criteria $criteria */
    foreach ($criterias as $criteria) {
        $modifiedValue = $alternative->getCriteriaValue($criteria->getName());

        if ($criteria->getType() === Criteria::TYPE_MAX) {
            $modifiedValue = $modifiedValue / $ideals[$criteria->getName()];
        } else {
            $modifiedValue = $ideals[$criteria->getName()] / $modifiedValue;
        }

        $alternative->addCriteria($criteria, number_format($modifiedValue, 2));
    }

    echo $alternative->getName();
    print_r($alternative->getCriteriaValues());
}
echo PHP_EOL;

echo 'WEIGHTED' . PHP_EOL;
// Weighted values
/** @var Alternative $alternative */
foreach ($alternatives as $alternative) {
    /** @var Criteria $criteria */
    foreach ($criterias as $criteria) {
        $modifiedValue = $alternative->getCriteriaValue($criteria->getName());
        $modifiedValue *= $criteria->getWeight();

        $alternative->addCriteria($criteria, number_format($modifiedValue, 2));
    }

    echo $alternative->getName();
    print_r($alternative->getCriteriaValues());
}

echo PHP_EOL;

echo 'POSITIVE/NEGATIVE SETS' . PHP_EOL;

/** @var Alternative $alternative */
foreach ($alternatives as $alternative) {
    /** @var Alternative $opponent */
    foreach ($alternatives as $opponent) {

        if ($alternative == $opponent) {
            continue;
        }

        $positives = array();
        $negatives = array();

        foreach ($criterias as $criteria) {
            if ($alternative->getCriteriaValue($criteria->getName()) >= $opponent->getCriteriaValue($criteria->getName())) {
                $positives[] = $criteria->getName();
            } else {
                $negatives[] = $criteria->getName();
            }
        }

        $alternative->addToNegaitveSet($opponent->getName(), $negatives);
        $alternative->addToPositiveSet($opponent->getName(), $positives);
    }

    echo $alternative->getName() . ' Positives: ';
    print_r($alternative->getPositiveSet());


    echo $alternative->getName() . ' Negatives: ';
    print_r($alternative->getNegativeSet());

}


$agreeMatrix = array();
$disagreeMatrix = array();

// Agree Matrix

$row = 1;
$column = 1;

echo PHP_EOL;

echo 'AGREE/DISAGREE MATRIX' . PHP_EOL;

/** @var Alternative $alternative */
foreach ($alternatives as $alternative) {
    $column = 1;
    /** @var Alternative $opponent */
    foreach ($alternatives as $opponent) {

        $agreeMatrix[$row][$column] = 0;
        $disagreeMatrix[$row][$column] = 0;

        if ($alternative != $opponent) {

            $positives = $alternative->getPositivesFor($opponent->getName());
            /** @var Criteria $criteria */
            foreach ($criterias as $criteria) {
                if (in_array($criteria->getName(), $positives)) {
                    $agreeMatrix[$row][$column] += $criteria->getWeight();
                }
            }


            $negatives = $alternative->getNegativesFor($opponent->getName());
            $maxDiff = 0;
            /** @var Criteria $criteria */
            foreach ($criterias as $criteria) {
                $diff = abs($alternative->getCriteriaValue($criteria->getName()) - $opponent->getCriteriaValue($criteria->getName()));

                if ($diff > $maxDiff) {
                    $maxDiff = $diff;
                }
            }

            $best = 0;
            foreach ($negatives as $criteriaName) {
                $diff = abs($alternative->getCriteriaValue($criteriaName) - $opponent->getCriteriaValue($criteriaName));
                if ($diff > $best) {
                    $best = $diff;
                }
            }
            $disagreeMatrix[$row][$column] = number_format($best / $maxDiff, 2);

        }
        $column++;
    }
    $row++;
}

$printMatrix = function ($src, $n) {
    for ($i = 1; $i <= $n; $i++) {
        for ($j = 1; $j <= $n; $j++) {
            echo $src[$i][$j] . '  ';
        }
        echo PHP_EOL;
    }
};

echo 'AGREE MATRIX' . PHP_EOL;
$printMatrix($agreeMatrix, count($alternatives));
echo PHP_EOL . PHP_EOL;

echo 'DISAGREE MATRIX' . PHP_EOL;
$printMatrix($disagreeMatrix, count($alternatives));
echo PHP_EOL . PHP_EOL;


echo 'OUTRANKING' . PHP_EOL;
echo 'AGREE AVERAGE' . PHP_EOL;
$values = array();
for ($i = 1; $i <= count($alternatives); $i++) {
    for ($j = 1; $j <= count($alternatives); $j++) {
        if ($i == $j) {
            continue;
        }
        $values[] = $agreeMatrix[$i][$j];
    }
}
$agreeAvg = number_format(array_sum($values) / count($values), 2);
echo $agreeAvg;

echo PHP_EOL;

echo 'DISAGREE AVERAGE' . PHP_EOL;
$values = array();
for ($i = 1; $i <= count($alternatives); $i++) {
    for ($j = 1; $j <= count($alternatives); $j++) {
        if ($i == $j) {
            continue;
        }
        $values[] = $disagreeMatrix[$i][$j];
    }
}
$disagreeAvg = number_format(array_sum($values) / count($values), 2);
echo $disagreeAvg;
echo PHP_EOL;

$finalMatrix = array();

for ($i = 1; $i <= count($alternatives); $i++) {
    for ($j = 1; $j <= count($alternatives); $j++) {
        if ($i == $j) {
            $finalMatrix[$i][$j] = 0;
        }
        if ($agreeMatrix[$i][$j] > $agreeAvg && $disagreeMatrix[$i][$j] < $disagreeAvg) {
            $finalMatrix[$i][$j] = 1;
            // i dominates j
            $alternatives[$i - 1]->addDominates($alternatives[$j - 1]->getName());

        } else {
            $finalMatrix[$i][$j] = 0;
            // i is dominated by j
        }
    }
}

echo PHP_EOL . PHP_EOL;
echo 'FINAL MATRIX' . PHP_EOL;
$printMatrix($finalMatrix, count($alternatives));
echo PHP_EOL . PHP_EOL;

/** @var Alternative $alternative */
foreach ($alternatives as $alternative) {
    echo $alternative->getName() . PHP_EOL;
    echo ' - DOMINATES OVER: ' . implode(', ', $alternative->getDominates()) . PHP_EOL;
    echo PHP_EOL;
}