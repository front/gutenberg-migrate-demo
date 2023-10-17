<?php declare(strict_types = 1);

namespace Drupal\gutenberg_migrate_example\Plugin\migrate\process;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\gutenberg_migrate_example\GutenbergMarkupGenerator;
use Drupal\layout_builder\Section;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a layout_builder_to_gutenberg plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: layout_builder_to_gutenberg
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "layout_builder_to_gutenberg",
 *   handle_multiples=TRUE
 * )
 */
final class LayoutBuilderToGutenberg extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * ETM.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * HTML to gutenberg service.
   *
   * @var \Drupal\gutenberg_migrate_example\GutenbergMarkupGenerator
   */
  protected GutenbergMarkupGenerator $gutenbergMarkupGenerator;

  /**
   * Constructor for the class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, GutenbergMarkupGenerator $htmlToGutenberg) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('entity_type.manager'),
      $container->get('gutenberg_migrate_example.gutenberg_markup_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    $return = [
      'format' => 'gutenberg',
    ];
    $storage = $this->entityTypeManager->getStorage('block_content');
    $gutenberg_html = '';
    foreach ($value as $item) {
      // Every item here is a section. So we need to loop through the components
      // in them. This example only deals with simple sections like a one column
      // one.
      if (empty($item["section"]) || !$item["section"] instanceof Section) {
        continue;
      }
      $section = $item["section"];
      if ($section->getLayoutId() !== 'layout_onecol') {
        continue;
      }
      $components = $section->getComponents();
      foreach ($components as $component) {
        $block_config = $component->get('configuration');
        switch ($component->getPluginId()) {
          case 'inline_block:basic':
            // This is markup. Use the markup helper over there. But first, load
            // the block from its revision I guess.
            $blocks = $storage->loadByProperties(['revision_id' => $block_config["block_revision_id"]]);
            if (empty($blocks)) {
              break;
            }
            $block = reset($blocks);
            if (!$block instanceof BlockContentInterface) {
              break;
            }
            if ($block->hasField('body') && !$block->get('body')->isEmpty()) {
              $gutenberg_html .= $this->gutenbergMarkupGenerator->getGutenbergHtmlFromHtml($block->get('body')->first()->getValue()['value']);
            }
            break;

          case 'inline_block:image':
            $blocks = $storage->loadByProperties(['revision_id' => $block_config["block_revision_id"]]);
            if (empty($blocks)) {
              break;
            }
            $block = reset($blocks);
            if (!$block instanceof BlockContentInterface) {
              break;
            }
            if ($block->hasField('field_image') && !$block->get('field_image')->isEmpty()) {
              $image_field_entity = $block->get('field_image')->entity;
              if (!$image_field_entity instanceof FileInterface) {
                break;
              }
              $gutenberg_html .= $this->gutenbergMarkupGenerator->generateGutenbergHtmlFromImageFile($image_field_entity);
            }
            break;

        }
      }
    }
    $return['value'] = $gutenberg_html;
    return [$return];
  }

}
