<?php

namespace Drupal\rsvplist\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an RSVP Email form.
 */
class RSVPForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rsvplist_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = \Drupal::routeMatch()->getParameter('node');

    $nid = "";
    if (isset($node)) {
      $nid = $node->id();
    }

    $form['email'] = [
      '#title' => t('Email Address'),
      '#type' => 'textfield',
      '#size' => 25,
      '#description' => t("We'll send updates to the email address your provid"),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('RSVP'),
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('email');
    if ($value == !\Drupal::service('email.validator')->isValid($value)) {
      $form_state->setErrorByName('email', t('The email address %mail is not valid.', ['%mail' => $value]));
      return;
    }

    $node = \Drupal::routeMatch()->getParameter('node');
    $select = Database::getConnection()->select('rsvplist', 'r');
    $select->fields('r', ['nid']);
    $select->condition('nid', $node->id());
    $select->condition('mail', $value);
    $result = $select->execute();
    if (!empty($result->fetchCol())) {
      $form_state->setErrorByName('email', t('The address %mail is already subscribed to this list', ['%mail' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = User::load(\Drupal::currentUser()->id());
    \Drupal::database()->insert('rsvplist')->fields([
      'mail' => $form_state->getValue('email'),
      'nid' => $form_state->getValue('nid'),
      'uid' => $user->id(),
      'created' => time(),
    ])->execute();
    \Drupal::messenger()->addMessage(t('Thank for your RSVP, you are on the list gor the event.'));
  }

}
