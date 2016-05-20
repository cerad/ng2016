<?php

namespace Cerad\Bundle\GameBundle\Action\Project\GameOfficial\AssignByUser;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Form\FormInterface;

use Cerad\Bundle\CoreBundle\Action\ActionController;

class AssignByUserController extends ActionController
{   
    public function __construct() {}
    
    public function assignAction(Request $request, AssignByUserModel $model, FormInterface $form)
    {
        // Handle the form
        $form->handleRequest($request);

        if ($form->isValid())
        {   
            // Maybe try/catch
            $model->process($request);

          //return new RedirectResponse($redirectUrl); // To schedule
            
            $formAction = $form->getConfig()->getAction();
            return new RedirectResponse($formAction);  // To form
        }

        // And render, pass the model directly to the view?
        $tplData = array();
        $tplData['form'] = $form->createView();
        $tplData['game'] = $model->game;
        $tplData['back'] = $model->back;
        
        $tplName = $request->attributes->get('_template');
        
        return $this->templating->renderResponse($tplName,$tplData);
    }
}
