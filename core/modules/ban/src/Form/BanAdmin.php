<?php

/**
 * @file
 * Contains \Drupal\ban\Form\BanAdmin.
 */

namespace Drupal\ban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\ban\BanIpManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays banned IP addresses.
 */
class BanAdmin extends FormBase {

  /**
   * @var \Drupal\ban\BanIpManagerInterface
   */
  protected $ipManager;

  /**
   * Constructs a new BanAdmin object.
   *
   * @param \Drupal\ban\BanIpManagerInterface $ip_manager
   */
  public function __construct(BanIpManagerInterface $ip_manager) {
    $this->ipManager = $ip_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ban.ip_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ban_ip_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param string $default_ip
   *   (optional) IP address to be passed on to
   *   \Drupal::formBuilder()->getForm() for use as the default value of the IP
   *   address form field.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $default_ip = '') {
    $rows = array();
    $header = array($this->t('banned IP addresses'), $this->t('Operations'));
    $result = $this->ipManager->findAll();
    foreach ($result as $ip) {
      $row = array();
      $row[] = $ip->ip;
      $links = array();
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'ban.delete',
        'route_parameters' => array('ban_id' => $ip->iid),
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }

    $form['ip'] = array(
      '#title' => $this->t('IP address'),
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 40,
      '#default_value' => $default_ip,
      '#description' => $this->t('Enter a valid IP address.'),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    );

    $form['ban_ip_banning_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No blocked IP addresses available.'),
      '#weight' => 120,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip'));
    if ($this->ipManager->isBanned($ip)) {
      $form_state->setErrorByName('ip', $this->t('This IP address is already banned.'));
    }
    elseif ($ip == $this->getRequest()->getClientIP()) {
      $form_state->setErrorByName('ip', $this->t('You may not ban your own IP address.'));
    }
    elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
      $form_state->setErrorByName('ip', $this->t('Enter a valid IP address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip'));
    $this->ipManager->banIp($ip);
    drupal_set_message($this->t('The IP address %ip has been banned.', array('%ip' => $ip)));
    $form_state->setRedirect('ban.admin_page');
  }

}
