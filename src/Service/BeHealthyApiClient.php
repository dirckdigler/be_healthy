<?php

namespace Drupal\be_healthy_api\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\enroll_now\Service\ManageSessionData;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Class BeHealthyApiClient.
 */
class BeHealthyApiClient {

  /**
   * Guzzle\Client instance.
   *
   * @var \Guzzle\Client
   */
  protected $httpClient;

  /**
   * @var \Drupal\enroll_now\Service\ManageSessionData
   */
  protected $ManageSessionData;

  /**
   * Entity endpoints.
   */
  protected $endpoints;

  /**
   * Config data from be_healthy_api.settings.
   */
  protected $config;

  /**
   * Constructs a \Drupal\be_healthy_api\Service\BeHealthyApiClient.
   * 
   * @param \Drupal\enroll_now\Service\ManageSessionData $manage_session_data
   * @param \GuzzleHttp\Client $http_client
   *
   */
  public function __construct(Client $http_client, ManageSessionData $manage_session_data) {
    $this->httpClient = $http_client;
    $this->ManageSessionData = $manage_session_data;
    $this->endpoints = $this->getEndPoints();
    $this->config = \Drupal::config('be_healthy_api.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('enroll_now.manage_session_data')
    );
  }

  /**
   * Based on the service, call the Endpoint Entity and 
   * his properties to pass it through the send function parameters.
   * 
   * @param string $service
   * @param array $params
   * @param string $message
   * @return array|mixed|string
   */
  public function consumption(string $service, 
                              $params = [], 
                              string $message) {
    $response = FALSE;
    // Validate if bundle Endpoint entity exist.
    if(isset($this->endpoints[$service])) {
      $method = $this->endpoints[$service]['method'];
      $base_url = $this->endpoints[$service]['base_url'];
      $response = $this->send($method, $base_url, $params, $message);
    }
    else {
      \Drupal::logger('be_healthy_api')->error(t('The @service service is not registered.', 
        ['@service' => $service]));
    }
    return $response;

  }

  /**
   *  Returns the configured entity endpoints.
   */
  private function getEndPoints() {
    $query = \Drupal::entityQuery('endpoints_entity');
    $nids = $query->execute();
    $endpoints_entities = \Drupal::entityTypeManager()->getStorage('endpoints_entity')
      ->loadMultiple($nids);;
    $endpoints = [];

    foreach ($endpoints_entities as $entity) {
      $service_name = trim($entity->get('label'));
      $endpoint['base_url'] = trim($entity->get('endpoint'));
      $endpoint['method'] = trim($entity->get('method'));
      $endpoint['timeout'] = (int) $entity->get('timeout');
      $endpoints[$service_name] = $endpoint;
      unset($endpoint);
    }
    return $endpoints;

  }

  /**
   * Send HTTP requests using the Guzzle client.
   * It's a transversal function that integrates with all the web services.
   * Also this use a try/catch technic to get all the errors 
   * and then convert them in a drupal logs.
   * 
   * @param string $method
   * @param string $base_url
   * @param array $params
   * @param string $success_message
   * @return array|mixed|string
   */
  protected function send(string $method, 
                          string $base_url, 
                          array $params, 
                          string $success_message = NULL
                        ) {
    $response = FALSE;
    try {
      // Here i'ts the magic, where Guzzle client makes his work,
      // making the HTTP request, depending the parameters.
      $request = $this->httpClient->request(
        $method, $base_url, $params
      )->getBody();
      $response = Json::decode($request);
      if (isset($response['token'])) {
        \Drupal::logger('be_healthy_api')->notice($success_message);
      }
      else {
        \Drupal::logger('be_healthy_api')
          ->notice($success_message . print_r($response, 1));
      }
    }
    catch(ConnectException $e) {
      $binds = [
        '@url' => $base_url,
        '@err' => $e->getMessage()
      ];
      $error_message = t('Error connecting to ') . '@url - Error: @err';
      \Drupal::logger('be_healthy_api')->error($error_message, $binds);
      return $response = [
        'networking_error' => TRUE
      ];
    }
    catch(RequestException $e) {
      if ($e->hasResponse()) {
        $exception = $e->getResponse()->getBody();
        $exception = json_decode($exception);
        $binds = [
          '@url' => $base_url,
          '@err' => $e->getMessage()
        ];
        $error_message = t('Error connecting to ') . '@url - Error: @err';
        \Drupal::logger('be_healthy_api')->error($error_message, $binds);
        $status_code = $e->getResponse()->getStatusCode();
        
        switch ($status_code) {
          case 400:
            // Validate if there is match with the respond service 
            // to print logs depending error.
            if (strpos($exception->message, 'The email address is already in use') !== false) {
              $error_message = t('Email is already taken ') . '@url - Error: @err';
              \Drupal::logger('be_healthy_api')->error($error_message, $binds);
              return $response = [
                'email_in_use' => TRUE
              ];
            }
            break;
          case 404:
            if (strpos($exception->message, 'customerNotFound') !== false) {
              $error_message = t('Customer not found ') . '@url - Error: @err';
              \Drupal::logger('be_healthy_api')->error($error_message, $binds);

              return $response = [
                'customer_not_found' => TRUE
              ];
            } 
            break;
          case 409:
            // Validate if there is match with the respond service 
            // to print logs depending error.
            if (strpos($exception->message, 'credentialsDoNotMatch') !== false) {
              $error_message = t('Credentials don’t match ') . '@url - Error: @err';
              \Drupal::logger('be_healthy_api')->error($error_message, $binds);
              return $response = [
                'credentials_error' => TRUE
              ];
            } 
            if (strpos($exception->message, 'userAlreadyEnrolled') !== false) {
              $error_message = t('User already enrolled ') . '@url - Error: @err';
              \Drupal::logger('be_healthy_api')->error($error_message, $binds);
              return $response = [
                'already_enroll_error' => TRUE
              ];
            } 
            break;
        }
      }
    }

    return $response;
  }

  /**
   * Consume Admin Public API. 
   * Elegibility service to validate if member 
   * is active on LD extras program.
   * 
   * @param array $data
   * @return array|mixed|string
   */
  public function ckeckMemberEligibilityGet($data = []) {
    $response = FALSE;
    // Get access token. 
    $authenticate = $this->authenticateAdminPublicPost();
    if (isset($authenticate['token'])) {
      $method_name = __FUNCTION__;
      $config = $this->getConfigFormSerivice();
      if (!empty($config) && isset($config)) {
        $data['community-id'] = $config['community_id'];
        $time_out = $this->endpoints[$method_name]['timeout'];
        $params = [
          'headers' => [
            'Cache-Control' => 'no-cache',
            'Authorization' => 'Bearer ' . $authenticate['token'],
            'Content-Type' => 'application/json',
            'X-App-Community' => $config['community'],
            'X-App-Version' => $config['app_version'],
          ],
          'query' => $data,
          'timeout' => $time_out,
        ];
        // Main execution to call HTTP request.
        $response = $this->consumption(
          $method_name, 
          $params, 
          $config['success_message_elegibility']
        );
      }
      else {
        \Drupal::logger('be_healthy_api')->error(
          'Error with the Config data service' . $method_name . ', tryng to get values form'
        );
      }
    }
    elseif (isset($authenticate['networking_error']) && 
      $authenticate['networking_error']) {
      return $authenticate;
    }

    return $response;

  }

  /**
   *  
   * Consume Admin Public API, 
   * Account service to create user account on Admin Console.
   *  
   * @return array
   * 
   */
  public function createAccountAdminPublicPost() {
    $response = FALSE;
    // Get access token. 
    $authenticate = $this->authenticateAdminPublicPost();
    if (isset($authenticate['token'])) {
      $manage_session_data = $this->manageServiceSessionData();
      if ($manage_session_data) {
        // Retrieve config data store in the form configuration Service. 
        $config = $this->getConfigFormSerivice();
        if (!empty($config) && isset($config)) {
          $body = base64_decode($manage_session_data);
          // Get method name.
          $method_name = __FUNCTION__;
          $time_out = $this->endpoints[$method_name]['timeout'];
          $params = [
            'headers' => [
              'Cache-Control' => 'no-cache',
              'Authorization' => 'Bearer ' . $authenticate['token'],
              'Content-Type' => 'application/json',
              'X-App-Community' => $config['community'],
              'X-App-Version' => $config['app_version'],
            ],
            'body' => $body,
            'timeout' => $time_out,
          ];
          // Main execution to call HTTP request.
          $response = $this->consumption(
            $method_name, 
            $params, 
            $config['success_message_account']
          );
        }
        else {
          \Drupal::logger('be_healthy_api')
            ->error('Error with the Config data service, tryng to get values form');
        }
      }
      else {
        \Drupal::logger('be_healthy_api')
          ->error(t('User data incomplete to consume the account service.'));
      }
    }
    elseif (isset($authenticate['networking_error']) && 
      $authenticate['networking_error']) {
      return $authenticate;
    }
    else {
      \Drupal::logger('be_healthy_api')
        ->error(t('Error getting access token.'));
    }

    return $response;
  }

  /**
   * Consume Admin Public API.
   * Retrieve access token and refresh token.
   *  
   * @return array|mixed|string
   * 
   */
  public function authenticateAdminPublicPost() {
    $response = FALSE;
    // Retrieve config data store in the form configuration Service.
    $config = $this->getConfigFormSerivice();
   
    if (!empty($config) && isset($config)) {
      // Get credentials email and password. 
      // If these were added from the form, get the config data.
      // Usually credentials will be  stored as enviroment variables.
      $auth_email = $config['email'];
      if (empty($auth_email)) {
        $auth_email = getenv('API_AUTH_EMAIL');
      }
      $auth_pwd = $config['password'];
      if (empty($auth_pwd)) {
        $auth_pwd = getenv('API_AUTH_PASSWORD');
      }
      $body = [
        'email' => $auth_email,
        'password' => $auth_pwd,
      ];
      $body = json_encode($body);
      // Get method name.
      $method_name = __FUNCTION__;
      $time_out = $this->endpoints[$method_name]['timeout'];
      $params = [
        'headers' => [
          'Cache-Control' => 'no-cache',
          'Content-Type' => 'application/json',
          'X-App-Community' => $config['community'],
          'X-App-Version' => $config['app_version'],
        ],
        'body' => $body,
        'timeout' => $time_out,
      ];
      // Main execution to call HTTP request.
      $response = $this->consumption(
        $method_name, 
        $params, 
        $config['success_message_authenticate']
      );
    }
    else {
      \Drupal::logger('be_healthy_api')
        ->error('Error with the Config data service, tryng to get values form');
    }
    
    return $response;
  }

  /**
   * Retrieve config data store in the form configuration Service. 
   * This data it's important to pass through the services parameters
   * URL from Routing: be_healthy_api.admin_settings.
   * 
   * @return array
   * 
   */
  private function getConfigFormSerivice() {
    // Array data contains the keys field of the 
    // be_healthy_api_admin_settings form.
    $data = ['app_version', 'community', 'email', 'password', 
      'success_message_authenticate', 'success_message_account',
      'success_message_elegibility', 'community_id'
    ];
    $response = [];

    foreach($data as $item) {
      $response[$item] = NULL !== $this->config->get($item) ? 
      $this->config->get($item) : '';
    }

    return $response;
  }

  /**
   * Retrieve session data stored in the Private Tempstore.
   * If not exist field value it will convert in NULL.
   * Hardcoded diferentes variables, necessary to pass them 
   * through the parameters services.
   * Finally the array will convert in base64_encode
   * to encrypt the data.
   * 
   * @return string
   */
  private function manageServiceSessionData() {
    $data = FALSE;
    $session_fields = $this->ManageSessionData->retrieveSessionFields();
    
    $insurance_member_id = isset($session_fields['member_id']) ? 
      $session_fields['member_id'] : NULL;
    $community_id = 1;
    $first_name = isset($session_fields['name']) ? 
      $session_fields['name'] : NULL;
    $last_name = isset($session_fields['lastname']) ? 
      $session_fields['lastname'] : NULL;
    $email = isset($session_fields['email_original']) ? 
      $session_fields['email_original'] : NULL;
    $birth_date = isset($session_fields['international_format_birthdate']) ? 
      $session_fields['international_format_birthdate'] : NULL;
    $sex = isset($session_fields['sex']) ? 
      $session_fields['sex'] : NULL;
    $installments = NULL !== $this->config->get('installments') ? 
      $this->config->get('installments') : NULL;
    $refund_currency = NULL !== $this->config->get('refund_currency') ? 
      $this->config->get('refund_currency') : NULL;
    $refund_value = NULL !== $this->config->get('refund_value') ? 
      $this->config->get('refund_value') : NULL;
    $enabled = NULL !== $this->config->get('enable') ? 
      $this->config->get('enable') : NULL;
    $country = isset($session_fields['country']) ? 
      $session_fields['country'] : NULL;
    $city = isset($session_fields['city']) ? 
      $session_fields['city'] : NULL;
    $time_zone = NULL !== $this->config->get('timezone') ? 
      $this->config->get('timezone') : '';
    $phone_number = isset($session_fields['phone_number']) ? 
      $session_fields['phone_number'] : NULL;

      if ($insurance_member_id !== NULL
      && $community_id !== NULL
      && $first_name !== NULL
      && $last_name !== NULL
      && $email !== NULL
      && $session_fields !== NULL
      && $email !== NULL
      && $birth_date !== NULL 
      && $sex !== NULL
      && $installments !== NULL
      && $refund_currency !== NULL
      && $refund_value !== NULL
      && $enabled !== NULL
      && $country !== NULL
      && $city !== NULL
      && $phone_number !== NULL
    ) {
      $data = [
        'member-id' => $insurance_member_id,
        'community-id' => $community_id,
        'first-name' => $first_name,
        'last-name' => $last_name,
        'email' => $email,
        'birth-date' => $birth_date,
        'sex' => $sex,
        'installments' => $installments,
        'refund-currency' => $refund_currency,
        'refund-value' => $refund_value,
        'enabled' => $enabled,
        'country' => $country,
        'city' => $city,
        'time-zone' => $time_zone,
        'phone-number' => $phone_number,
        'new-watch' => false
      ];

      $data = base64_encode(json_encode($data));     
      return $data;

    }
    
    return $data;
  }

}
