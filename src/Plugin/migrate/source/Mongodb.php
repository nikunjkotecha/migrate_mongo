<?php

namespace Drupal\migrate_mongo\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\mongodb\DatabaseFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extract posts from Mongo database.
 *
 * @MigrateSource(
 *   id = "mongodb"
 * )
 */
class Mongodb extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var string
   */
  protected $sourceDbAlias;

  /**
   * @var string
   */
  protected $sourceCollection;

  /**
   * @var array
   */
  protected $sourceFields;

  /**
   * @var \Drupal\mongodb\DatabaseFactory
   */
  protected $mongoDb;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('mongodb.database_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, DatabaseFactory $db) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->sourceDbAlias = $configuration['alias'];
    $this->sourceCollection = $configuration['collection'];
    $this->sourceFields = $configuration['fields'];
    $this->mongoDb = $db;
  }

  /**
   * Return a string giving summary of configuration.
   *
   * @return string
   *   Configuration summary.
   */
  public function __toString() {
    $summary = [];
    $summary[] = 'Alias: ' . $this->sourceDbAlias;
    $summary[] = 'Collection: ' . $this->sourceCollection;
    return implode(', ', $summary);
  }

  /**
   * Creates and returns a filtered Iterator over the documents.
   *
   * @return \Iterator
   *   An iterator over the documents providing source rows that match the
   *   configured item_selector.
   */
  protected function initializeIterator() {
    $source_data = $this->getSourceData();
    return new \ArrayIterator($source_data);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceData() {
    // Query mongo database
    $rows = $this->getDatabase()
      ->selectCollection($this->sourceCollection)
      ->find([])
      ->toArray();

    $result = [];

    /** @var \MongoDB\Model\BSONDocument $row */
    foreach ($rows as $row) {
      $city = $row->getArrayCopy();
      $city['loc'] = $city['loc']->getArrayCopy();
      $result[] = $city;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->sourceFields;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleMigrationRequired() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $source_key = [
      '_id' => [
        'type' => 'string',
        'length' => 24,
        'not null' => TRUE,
        'description' => 'MongoDB ID field.',
      ],
    ];

    return $source_key;
  }


  /**
   * {@inheritdoc}
   */
  public function getDatabase() {
    return $this->mongoDb->get($this->sourceDbAlias);
  }

}
