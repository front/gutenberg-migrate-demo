<?php

declare(strict_types = 1);

namespace Drupal\gutenberg_migrate_example\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\gutenberg_migrate_example\GutenbergMarkupGenerator;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a html_to_gutenberg plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: html_to_gutenberg
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(id = "html_to_gutenberg")
 */
final class HtmlToGutenbergProcessor extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Service that parses.
   *
   * @var \Drupal\gutenberg_migrate_example\GutenbergMarkupGenerator
   */
  protected GutenbergMarkupGenerator $gutenbergMarkupGenerator;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GutenbergMarkupGenerator $htmlToGutenberg) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->gutenbergMarkupGenerator = $htmlToGutenberg;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('gutenberg_migrate_example.gutenberg_markup_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    $new_value = [
      'format' => 'gutenberg',
    ];
    $new_value['value'] = $this->gutenbergMarkupGenerator->getGutenbergHtmlFromHtml($value['value']);
    return $new_value;
  }

}
