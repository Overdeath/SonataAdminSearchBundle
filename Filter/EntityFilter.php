<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminSearchBundle\Filter;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;

class EntityFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $query, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        if (!is_object($data['value'])) {
            return;
        }

        $entity = $data['value'];

        $data['type'] = ChoiceType::TYPE_EQUAL;

        list($firstOperator, $secondOperator) = $this->getOperators((int) $data['type']);

        // Create a query that match terms (indepedent of terms order) or a phrase
        $queryBuilder = new \Elastica\Query\Builder();
        
        $path = $field;
        foreach ($this->getParentAssociationMappings() as $parentAssociationMapping) {
            $path = $parentAssociationMapping["fieldName"].".".$path;
        }

        $queryBuilder
            ->fieldOpen($secondOperator)
                ->field('path', $path)
                ->fieldOpen('query')
                    ->bool()
                        ->must()
                            ->fieldOpen("match")
                                ->field($path.".id", $entity->getId())
                            ->fieldClose()
                        ->mustClose()
                    ->boolClose()
                ->fieldClose()
            ->fieldClose();

        $query->addMust($queryBuilder);

    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return array('sonata_type_filter_choice', array(
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ));
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function getOperators($type)
    {
        $choices = array(
            ChoiceType::TYPE_EQUAL => array('must', 'nested'),
        );

        return isset($choices[$type]) ? $choices[$type] : false;
    }
}
