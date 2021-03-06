<?php

namespace Drupal\oowizard;

/**
 * One step in a multi-step form.
 */
abstract class WizardStep {
  protected $title = 'Wizard Step';
  protected $wizard;
  public function __construct($wizard) {
    $this->wizard = $wizard;
    $this->loadIncludes();
  }

  protected function loadIncludes() {}

  public function pageCallback() {
    $build = array();
    $build[] = $this->wizard->wizardForm();
    return $build;
  }

  public function checkDependencies() {
    return TRUE;
  }

  public function validateStep($form, &$form_state) {
  }

  public function submitStep($form, &$form_state) {
  }

  public function getTitle() {
    return t($this->title);
  }
}
