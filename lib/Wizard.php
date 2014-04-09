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

  protected $step;

  public function __construct($user) {
    $this->user = $user ? $user : $GLOBALS['user'];

    $this->stepHandlers = array();
    $forms = array();
    foreach ($this->steps as $urlpart => $class) {
      $this->stepHandlers[$urlpart] = $step = new $class($this);
      $forms[$urlpart] = array(
        'form id' => 'oowizard_form',
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
    $form_state['oowizard'] = $this;
    ctools_include('wizard');
    $form = ctools_wizard_multistep_form($this->formInfo, $this->currentStep, $form_state);
    $form['#validate'] = 'oowizard_form_validate';
    return $form;
  }

  public function form($form, &$form_state) {
    return $this->step->stepForm($form, $form_state);
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

  protected function setStep($step) {
    $this->currentStep = $step;
    $this->step = $this->stepHandlers[$step];
  }

  public function run($step) {
    // return 404 if the form step is unknown
    if (!isset($this->steps[$step])) {
      return drupal_not_found();
    }
    $this->setStep($step);
    return $this->step->pageCallback();
  }

  public function validate($form, &$form_state) {
    $this->step->validateStep($form, $form_state);
  }

  public function next(&$form_state) {
    $this->step->submitStep($form_state['complete form'], $form_state);
  }
  public function finish(&$form_state) {
    $this->step->submitStep($form_state['complete form'], $form_state);
  }
  public function ret(&$form_state) {
    $this->step->submitStep($form_state['complete form'], $form_state);
  }
  public function cancel(&$form_state) {
  }
}
