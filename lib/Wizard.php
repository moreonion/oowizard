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
  protected $buttonType;

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
    $form['#validate'] = array('oowizard_form_validate');
    // We need our own submit handler to be called befor ctools_wizard_submit.
    $form['#submit'] = array('oowizard_form_submit');
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
    if (isset($form_state['clicked_button']['#wizard type'])) {
      $this->buttonType = $form_state['clicked_button']['#wizard type'];
    }
    $this->step->validateStep($form, $form_state, $this->buttonType);
  }

  public function submit($form, &$form_state) {
    $this->step->submitStep($form, $form_state, $this->buttonType);
  }
}
