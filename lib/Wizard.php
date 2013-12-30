<?php

namespace Drupal\oowizard;

/**
 * Baseclass for multi-step forms.
 */
abstract class Wizard {
  public $steps = array();
  public $stepHandlers;
  public $currentStep;

  public $user;
  public $formInfo;

  public function __construct($user) {
    $this->user = $user ? $user : $GLOBALS['user'];

    $this->stepHandlers = array();
    $forms = array();
    foreach ($this->steps as $urlpart => $class) {
      $this->stepHandlers[$urlpart] = $step = new $class($this);
      $forms[$urlpart] = array(
        'form id' => 'oowizard_step_form',
        'title' => $step->getTitle(),
      );
    }

    $this->formInfo = array(
      'id' => 'oowizard_form',
      'path' => NULL,
      'show trail' => FALSE,
      'show back' => TRUE,
      'forms' => $forms,
    );
  }

  public function wizardForm() {
    $form_state = array();
    $form_state['step handler'] = $this->stepHandlers[$this->currentStep];
    ctools_include('wizard');
    $form = ctools_wizard_multistep_form($this->formInfo, $this->currentStep, $form_state);
    $form['#validate'] = 'oowizard_step_form_validate';

    return $form;
  }

  public function trailItems() {
    $trail = array();
    $accessible = TRUE;
    foreach ($this->stepHandlers as $urlpart => $step) {
      $is_current = $urlpart == $this->currentStep;
      $trail[] = array(
        'url' => strtr($this->formInfo['path'], array('%step' => $urlpart)),
        'title' => $step->getTitle(),
        'accessible' => $accessible = ($accessible && $step->checkDependencies()),
        'current' => $urlpart == $this->currentStep,
      );
    }
    return $trail;
  }

  public function trail() {
    return array(
      '#theme' => array('oowizard_trail__' . $this->formInfo['id'], 'oowizard_trail'),
      '#trail' => $this->trailItems(),
    );
  }

  public function run($step) {
    // return 404 if the form step is unknown
    if (!isset($this->steps[$step])) {
      return drupal_not_found();
    }
    $this->currentStep = $step;
    return $this->stepHandlers[$step]->pageCallback();
  }

}
