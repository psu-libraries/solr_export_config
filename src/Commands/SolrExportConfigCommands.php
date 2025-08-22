<?php

declare(strict_types=1);

namespace Drupal\solr_export_config\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;
use Symfony\Component\Process\Process;

/**
 * Adding drush command to export solr configset after config:export runs.
 */
class SolrExportConfigCommands extends DrushCommands {

  /**
   * Constructs a new SolrExportConfigCommands object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * React to the config:export, export solr configs post config:export.
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'config:export')]
  public function postConfigExport($result, CommandData $commandData): void {
    $config = $this->configFactory->get('solr_export_config.settings');
    $ids = [];

    // Get all the search API servers.
    $command = ['drush', 'sapi-sl', '--format=json'];
    $process = new Process($command);
    $process->run();
    $output = $process->getOutput();
    $sapi_servers = json_decode($output, TRUE);

    foreach ($sapi_servers as $server) {
      $ids[] = $server['id'];
    }

    // Export server config for each server that we found.
    foreach ($ids as $id) {
      $command = ['drush', 'search-api-solr:get-server-config', $id, $id . '.zip'];
      $process = new Process($command);
      $process->run();
      if ($process->isSuccessful()) {
        $this->logger()->notice('Dumping solr config for ' . $id);
        $zip = new \ZipArchive();
        $res = $zip->open($id . '.zip');
        if ($res === TRUE) {
          $dir = rtrim($config->get('solr_conf_dir'), '/') . '/' . $id;
          if (!is_dir($dir)) {
            $this->logger()->notice('Creating Directory' . $dir);
            mkdir($dir, 0755, TRUE);
          }
          $this->logger()->notice('Extracting solr conf to ' . $dir);
          $zip->extractTo($dir);
          $zip->close();
        }
        unlink($id . '.zip');
      }
      else {
        error_log($process->getErrorOutput());
      }
    }
  }

}
