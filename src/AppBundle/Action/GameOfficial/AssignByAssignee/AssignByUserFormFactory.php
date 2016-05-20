<?php

namespace Cerad\Bundle\GameBundle\Action\Project\GameOfficial\AssignByUser;

use Symfony\Component\HttpFoundation\Request;

use Cerad\Bundle\CoreBundle\Action\ActionFormFactory;

class AssignByUserFormFactory extends ActionFormFactory
{   
    public function create(Request $request, AssignByUserModel $model)
    {   
        // The 'form' is actually the type
        $builder = $this->formFactory->createBuilder('form',$model);

        $actionRoute = $request->attributes->get('_route');
        $actionUrl = $this->router->generate($actionRoute,array
        (
             'back'         => $model->back,
            '_game'         => $model->game->getNum(),
            '_gameOfficial' => $model->gameOfficial->getSlot(),
            '_project'      => $request->attributes->get('_project'),
        ));
        $builder->setAction($actionUrl);
        
        $slotFormType = new AssignByUserSlotFormType(
                $model->workflow,
                $model->projectOfficial
        );
        $builder->add('gameOfficial',$slotFormType);
        
        $builder->add('assign', 'submit', array(
            'label' => 'Submit',
            'attr'  => array('class' => 'submit'),
        ));  
        $builder->add( 'reset','reset', array(
            'label' => 'Reset',
            'attr'  => array('class' => 'submit'),
        ));  
        return $builder->getForm();
    }
}
