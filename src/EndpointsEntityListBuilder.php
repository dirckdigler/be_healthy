<?php

namespace Drupal\be_healthy_api;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Endpoints entity entities.
 */
class EndpointsEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['service_name'] = $this->t('Service name');
    $header['method'] = $this->t('Method');
    $header['endpoint'] = $this->t('Endpoint');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['service_name'] = $entity->label();
    $row['method'] = $entity->get('method');
    $row['endpoint'] = $entity->get('endpoint');
    return $row + parent::buildRow($entity);
  }

}
