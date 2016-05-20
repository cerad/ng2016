<?php

namespace Cerad\Bundle\GameBundle\Action\Project\GameOfficial\AssignByUser;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AssignByUserSlotFormType extends AbstractType
{
    public function getName() { return 'cerad_game__game_official__assign_by_user_slot'; }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cerad\Bundle\GameBundle\Doctrine\Entity\GameOfficial',
        ));
    }
    protected $workflow;
    protected $projectOfficial;
    
    public function __construct($workflow,$projectOfficial)
    {
        $this->workflow        = $workflow;        
        $this->projectOfficial = $projectOfficial;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('role', 'text', array(
            'attr'      => array('size' => 10),
            'read_only' => true,
        ));
        $subscriber = new AssignByUserSlotSubscriber($builder->getFormFactory(),$this->workflow,$this->projectOfficial);
        $builder->addEventSubscriber($subscriber);
        
        if ($options);
    }
}

