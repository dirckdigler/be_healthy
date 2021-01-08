<?php

namespace Drupal\be_healthy_api\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Class EndpointsEntityForm.
 */
class EndpointsEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $endpoints_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $endpoints_entity->label(),
      '#description' => $this->t("Label for the Endpoints entity."),
      '#required' => TRUE,
    ];

    $form['endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#maxlength' => 250,
      '#default_value' => $endpoints_entity->get('endpoint'),
      '#required' => TRUE,
    );

    $form['method'] = array(
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#options' => array('GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'DELETE' => 'DELETE'),
      '#default_value' => $endpoints_entity->get('method'),
      '#required' => TRUE,
    );

    $form['timeout'] = array(
      '#type' => 'number',
      '#title' => $this->t('Timeout expiration'),
      '#default_value' => $endpoints_entity->get('timeout'),
      '#description' => $this->t('Maximum number of seconds to waiting a response.'),
      '#field_suffix' => $this->t('Seconds'),
      '#min' => 1,
      '#max' => 60,
      '#required' => TRUE,
    );

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $endpoints_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\be_healthy_api\Entity\EndpointsEntity::load',
      ],
      '#disabled' => !$endpoints_entity->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $endpoints_entity = $this->entity;

    $status = $endpoints_entity->save();
    //  kint($endpoints_entity->get('endpoint'), 'entro');die();


    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Endpoints entity.', [
          '%label' => $endpoints_entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Endpoints entity.', [
          '%label' => $endpoints_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($endpoints_entity->toUrl('collection'));
  }

}
