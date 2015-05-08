<?php

class ElectreSolver
{

    private $alternatives;

    private $criterias;

    private $idealValues = array();

    private $agreeMatrix = array();

    private $disagreeMatrix = array();

    private $finalMatrix = array();


    private $outrankingAgreeThreshold;
    private $outrankingDisagreeThreshold;

    /**
     * @param $alternatives
     * @param $criterias
     */
    public function __construct($alternatives, $criterias)
    {
        $this->setAlternatives($alternatives);
        $this->setCriterias($criterias);
    }

    /**
     * @return mixed
     */
    public function getAlternatives()
    {
        return $this->alternatives;
    }

    /**
     * @param mixed $alternatives
     */
    public function setAlternatives($alternatives)
    {
        foreach ($alternatives as $alternative) {
            $this->alternatives[] = $alternative;
        }
    }

    /**
     * @return mixed
     */
    public function getCriterias()
    {
        return $this->criterias;
    }

    /**
     * @param mixed $criterias
     */
    public function setCriterias($criterias)
    {
        foreach ($criterias as $criteria) {
            $this->criterias[] = $criteria;
        }
    }


    protected function findIdealValues()
    {
        // 1. find ideals
        $ideals = array();

        /** @var Criteria $criteria */
        foreach ($this->criterias as $criteria) {
            $ideal = null;
            $values = array();

            /** @var Alternative $alternative */
            foreach ($this->alternatives as $alternative) {
                $values[] = $alternative->getCriteriaValue($criteria->getName());
            }

            if ($criteria->getType() === Criteria::TYPE_MAX) {
                $ideal = max($values);
            } else {
                $ideal = min($values);
            }

            $ideals[$criteria->getName()] = $ideal;
        }
        $this->idealValues = $ideals;
    }


    protected function normalize()
    {
        /** @var Alternative $alternative */
        foreach ($this->alternatives as $name => $alternative) {
            /** @var Criteria $criteria */
            foreach ($this->criterias as $criteria) {
                $modifiedValue = $alternative->getCriteriaValue($criteria->getName());
                if ($criteria->getType() === Criteria::TYPE_MAX) {
                    $modifiedValue = $modifiedValue / $this->idealValues[$criteria->getName()];
                } else {
                    $modifiedValue = $this->idealValues[$criteria->getName()] / $modifiedValue;
                }

                $alternative->addCriteria($criteria, number_format($modifiedValue, 2));
            }
        }
    }


    protected function calculateWeighted()
    {
        /** @var Alternative $alternative */
        foreach ($this->alternatives as $alternative) {
            /** @var Criteria $criteria */
            foreach ($this->criterias as $criteria) {
                $modifiedValue = $alternative->getCriteriaValue($criteria->getName());
                $modifiedValue *= $criteria->getWeight();
                $alternative->addCriteria($criteria, number_format($modifiedValue, 2));
            }
        }
    }


    protected function findPositiveAndNegativeSets()
    {
        /** @var Alternative $alternative */
        foreach ($this->alternatives as $alternative) {
            /** @var Alternative $opponent */
            foreach ($this->alternatives as $opponent) {

                if ($alternative == $opponent) {
                    continue;
                }

                $positives = array();
                $negatives = array();

                /** @var Criteria $criteria */
                foreach ($this->criterias as $criteria) {
                    if ($alternative->getCriteriaValue($criteria->getName()) >= $opponent->getCriteriaValue($criteria->getName())) {
                        $positives[] = $criteria->getName();
                    } else {
                        $negatives[] = $criteria->getName();
                    }
                }

                $alternative->addToNegaitveSet($opponent->getName(), $negatives);
                $alternative->addToPositiveSet($opponent->getName(), $positives);
            }
        }
    }


    protected function getAgreeAndDisagreeMatrixes()
    {
        $agreeMatrix = array();
        $disagreeMatrix = array();

        $row = 0;

        /** @var Alternative $alternative */
        foreach ($this->alternatives as $alternative) {
            $column = 0;
            /** @var Alternative $opponent */
            foreach ($this->alternatives as $opponent) {
                $agreeMatrix[$row][$column] = 0;
                $disagreeMatrix[$row][$column] = 0;
                if ($alternative != $opponent) {
                    $positives = $alternative->getPositivesFor($opponent->getName());
                    /** @var Criteria $criteria */
                    foreach ($this->criterias as $criteria) {
                        if (in_array($criteria->getName(), $positives)) {
                            $agreeMatrix[$row][$column] += $criteria->getWeight();
                        }
                    }

                    $negatives = $alternative->getNegativesFor($opponent->getName());
                    $maxDiff = 0;

                    /** @var Criteria $criteria */
                    foreach ($this->criterias as $criteria) {
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

        $this->agreeMatrix = $agreeMatrix;
        $this->disagreeMatrix = $disagreeMatrix;
    }


    protected function printMatrix($matrix)
    {
        $n = count($matrix);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                echo $matrix[$i][$j] . '  ';
            }
            echo PHP_EOL;
        }
    }


    protected function calculateOutrankingThresholds()
    {
        $n = count($this->agreeMatrix);
        $values = array();
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i == $j) {
                    continue;
                }
                $values[] = $this->agreeMatrix[$i][$j];
            }
        }
        $this->outrankingAgreeThreshold = number_format(array_sum($values) / count($values), 2);

        $values = array();
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i == $j) {
                    continue;
                }
                $values[] = $this->disagreeMatrix[$i][$j];
            }
        }
        $this->outrankingDisagreeThreshold = number_format(array_sum($values) / count($values), 2);
    }


    protected function getFinalMatrix()
    {
        $finalMatrix = array();
        $n = count($this->agreeMatrix);

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i == $j) {
                    $finalMatrix[$i][$j] = 0;
                }
                if ($this->agreeMatrix[$i][$j] > $this->outrankingAgreeThreshold && $this->disagreeMatrix[$i][$j] < $this->outrankingDisagreeThreshold) {
                    $finalMatrix[$i][$j] = 1;
                    // i dominates j
                    $this->alternatives[$i]->addDominates($this->alternatives[$j]->getName());

                } else {
                    // i is dominated by j
                    $finalMatrix[$i][$j] = 0;
                }
            }
        }

        $this->finalMatrix = $finalMatrix;
    }


    protected function printDecisionTable()
    {
        /** @var Alternative $alternative */
        foreach ($this->alternatives as $alternative) {
            echo $alternative->getName() . ': ';
            foreach ($alternative->getCriteriaValues() as $name => $value) {
                echo "[$name]" . ': ' . $value . ' ';
            }
            echo PHP_EOL;
        }

    }


    protected function printPositiveNegativeSets()
    {
        /** @var Alternative $alternative */
        foreach ($this->alternatives as $alternative) {
            echo $alternative->getName() . ' positive sets:';
            print_r($alternative->getPositiveSet());
            echo PHP_EOL;

            echo $alternative->getName() . ' negative sets:';
            print_r($alternative->getNegativeSet());
            echo PHP_EOL;

        }


    }


    public function run($verbose = false)
    {

        if ($verbose) {
            $this->printDecisionTable();
            echo PHP_EOL;
        }

        echo 'IDEAL VALUES CALCULATION ...' . PHP_EOL;
        $this->findIdealValues();
        if ($verbose) {
            print_r($this->idealValues);
            echo PHP_EOL;
        }


        echo 'NORMALIZATION ...' . PHP_EOL;
        $this->normalize();
        if ($verbose) {
            $this->printDecisionTable();
            echo PHP_EOL;
        }

        echo 'WEIGHTED CALCULATION ...' . PHP_EOL;
        $this->calculateWeighted();
        if ($verbose) {
            $this->printDecisionTable();
            echo PHP_EOL;
        }

        echo 'POSITIVE/NEGATIVE SETS' . PHP_EOL;
        $this->findPositiveAndNegativeSets();
        if ($verbose) {
            $this->printPositiveNegativeSets();
        }
        echo PHP_EOL;


        echo 'AGREE/DISAGREE MATRIX' . PHP_EOL;
        $this->getAgreeAndDisagreeMatrixes();
        if ($verbose) {
            echo 'AGREE MATRIX' . PHP_EOL;
            $this->printMatrix($this->agreeMatrix);
            echo PHP_EOL . PHP_EOL;

            echo 'DISAGREE MATRIX' . PHP_EOL;
            $this->printMatrix($this->disagreeMatrix);
            echo PHP_EOL . PHP_EOL;
        }


        echo 'OUTRANKING ...' . PHP_EOL;
        $this->calculateOutrankingThresholds();
        if ($verbose) {
            echo 'Agree threshold: ' . $this->outrankingAgreeThreshold . PHP_EOL;
            echo 'Disagree threshold: ' . $this->outrankingDisagreeThreshold . PHP_EOL;
        }
        echo PHP_EOL;


        echo 'FINAL MATRIX ...' . PHP_EOL;
        $this->getFinalMatrix();
        if ($verbose) {
            $this->printMatrix($this->finalMatrix);
        }
        echo PHP_EOL;

        echo 'RESULT ...' . PHP_EOL;
        $this->printResult();
    }

    public function printResult()
    {
        /** @var Alternative $alternative */
        foreach ($this->alternatives as $alternative) {
            echo "[" . $alternative->getName() . "] ";
            echo 'DOMINATES OVER: ' . implode(', ', $alternative->getDominates()) . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
