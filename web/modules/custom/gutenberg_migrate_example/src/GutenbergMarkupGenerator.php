<?php

declare(strict_types = 1);

namespace Drupal\gutenberg_migrate_example;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Service to get gutenberg compatibe things from HTML.
 */
final class GutenbergMarkupGenerator {

  /**
   * ETM.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * File URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Constructs a HtmlToGutenberg object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * Parse HTML, get gutenberg.
   *
   * Or PHGG for short.
   */
  public function getGutenbergHtmlFromHtml(string $regular_html) : string {
    $gutenberg_html = '';
    // Parse the HTML and put it into the blocks we know of.
    $unique_id = uniqid('temp-wrapper');
    $crawler = new Crawler(sprintf('<div class="%s">%s</div>', $unique_id, $regular_html));
    $crawler->filter((string) sprintf('.%s', $unique_id))->children()->each(function (Crawler $item_crawler) use (&$gutenberg_html) {

      switch ($item_crawler->nodeName()) {
        case 'p':
          // Try to blindly insert it as a gutenberg paragraph.
          $gutenberg_html .= sprintf('
<!-- wp:paragraph -->
%s
<!-- /wp:paragraph -->', $item_crawler->outerHtml());
          break;

        case 'img':
          // Maybe we have an uuid in the attributes?
          $uuid_value = $item_crawler->attr('data-entity-uuid');
          if (empty($uuid_value)) {
            break;
          }
          $entity_type = $item_crawler->attr('data-entity-type');
          if (empty($entity_type)) {
            break;
          }
          $entities = $this->entityTypeManager->getStorage($entity_type)->loadByProperties(['uuid' => $uuid_value]);
          if (empty($entities)) {
            break;
          }
          $entity = reset($entities);
          if (!$entity instanceof FileInterface) {
            break;
          }
          $gutenberg_html .= $this->generateGutenbergHtmlFromImageFile($entity);
          break;

        default:
          break;
      }
    });
    return $gutenberg_html;
  }

  /**
   * Helper to get the gutenberg HTML from an image file.
   */
  public function generateGutenbergHtmlFromImageFile(FileInterface $file) : string {
    $path = $this->fileUrlGenerator->generateString($file->getFileUri());
    return sprintf('
<!-- wp:image {"id":%d,"sizeSlug":"full","linkDestination":"none","mediaAttrs":{"data-entity-type":"%s","data-entity-uuid":"%s","data-image-style":"original"}} -->
<figure class="wp-block-image size-full"><img src="%s" alt="" class="wp-image-%d" data-entity-type="%s" data-entity-uuid="%s" data-image-style="original"/></figure>
<!-- /wp:image -->
', $file->id(), $file->getEntityTypeId(), $file->uuid(), $path, $file->id(), $file->getEntityTypeId(), $file->uuid());
  }

}
