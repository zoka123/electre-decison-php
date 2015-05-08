<?php

class Alternative
{
    private $name;

    private $criterias = array();

    private $positiveSet = array();

    private $negativeSet = array();

    private $dominates = array();

    public static function make()
    {
        return new self();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;

    }

    /**
     * @return array
     */
    public function getCriterias()
    {
        return $this->criterias;
    }

    /**
     * @param array $criterias
     */
    public function setCriterias($criterias)
    {
        $this->criterias = $criterias;
        return $this;

    }


    public function addCriteria(Criteria $criteria, $value)
    {
        $this->criterias[$criteria->getName()] = array('criteria' => $criteria, 'value' => $value);
        return $this;
    }

    public function getCriteriaValue($name)
    {
        return $this->criterias[$name]['value'];
    }

    public function getCriteriaValues()
    {
        $return = array();

        foreach ($this->getCriterias() as $name => $criteria) {
            $return[$name] = $this->getCriteriaValue($name);
        }
        return $return;
    }

    /**
     * @return array
     */
    public function getPositiveSet()
    {
        return $this->positiveSet;
    }

    /**
     * @param array $positiveSet
     */
    public function setPositiveSet($positiveSet)
    {
        $this->positiveSet = $positiveSet;
    }

    /**
     * @return array
     */
    public function getNegativeSet()
    {
        return $this->negativeSet;
    }

    /**
     * @param array $negativeSet
     */
    public function setNegativeSet($negativeSet)
    {
        $this->negativeSet = $negativeSet;
    }

    public function addToPositiveSet($alternativeName, array $criteriaNames)
    {
        $this->positiveSet[$alternativeName] = $criteriaNames;

    }


    public function addToNegaitveSet($alternativeName, array $criteriaNames)
    {
        $this->negativeSet[$alternativeName] = $criteriaNames;
    }

    public function getPositivesFor($forName)
    {
        $return = array();
        foreach ($this->getPositiveSet() as $name => $positive) {
            if ($name == $forName) {
                $return = $positive;
                break;
            }
        }
        return $return;
    }


    public function getNegativesFor($forName)
    {
        $return = array();
        foreach ($this->getNegativeSet() as $name => $negative) {
            if ($name == $forName) {
                $return = $negative;
                break;
            }
        }
        return $return;
    }

    /**
     * @return array
     */
    public function getDominates()
    {
        return $this->dominates;
    }

    /**
     * @param array $dominates
     */
    public function setDominates($dominates)
    {
        $this->dominates = $dominates;
    }

    public function addDominates($alternativeName)
    {
        $this->dominates[] = $alternativeName;
    }

}