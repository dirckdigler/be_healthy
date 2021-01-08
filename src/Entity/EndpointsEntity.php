<?php

namespace Drupal\be_healthy_api\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Endpoints entity entity.
 *
 * @ConfigEntityType(
 *   id = "endpoints_entity",
 *   label = @Translation("Endpoints entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\be_healthy_api\EndpointsEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\be_healthy_api\Form\EndpointsEntityForm",
 *       "edit" = "Drupal\be_healthy_api\Form\EndpointsEntityForm",
 *       "delete" = "Drupal\be_healthy_api\Form\EndpointsEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\be_healthy_api\EndpointsEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "endpoints_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "endpoint",
 *     "method",
 *     "timeout",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/endpoints_entity/{endpoints_entity}",
 *     "add-form" = "/admin/structure/endpoints_entity/add",
 *     "edit-form" = "/admin/structure/endpoints_entity/{endpoints_entity}/edit",
 *     "delete-form" = "/admin/structure/endpoints_entity/{endpoints_entity}/delete",
 *     "collection" = "/admin/structure/endpoints_entity"
 *   }
 * )
 */
class EndpointsEntity extends ConfigEntityBase implements EndpointsEntityInterface {

  /**
   * The Endpoints entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Endpoints entity label.
   *
   * @var string
   */
  protected $label;

}
