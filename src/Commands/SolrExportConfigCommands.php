<?php

declare(strict_types=1);

namespace Drupal\solr_export_config\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Symfony\Component\Process\Process;
use ZipArchive;

class SolrExportConfigCommands extends DrushCommands {

  /**
   * React to the config:export, export solr configs post config:export
   *
   * @hook post-command config:export
   */
  public function postConfigExport($result, CommandData $commandData) {
    $config = \Drupal::config('solr_export_config.settings');
    $ids = [];

    // Get all the search API servers
    $command = ['drush', 'sapi-sl', '--format=json'];
    $process = new Process($command);
    $process->run();
    $output = $process->getOutput();
    $sapi_servers = json_decode($output, true);

    foreach ($sapi_servers as $server) {
      $ids[] = $server['id'];
    }

    // Export server config for each server that we found
    foreach ($ids as $id) {
      $command = ['drush', 'search-api-solr:get-server-config', $id, $id . '.zip'];
      $process = new Process($command);
      $process->run();
      if ($process->isSuccessful()) {
        \Drupal::logger('solr_export_config')->notice('Dumping solr config for ' . $id);
        $zip = new ZipArchive;
        $res = $zip->open($id . '.zip');
        if ($res === TRUE) {
          $dir = rtrim($config->get('solr_conf_dir'), '/') . '/' . $id;
          if (!is_dir($dir)) {
           \Drupal::logger('solr_export_config')->notice('Creating Directory' . $dir);
            mkdir($dir, 0755, true);
          }
          \Drupal::logger('solr_export_config')->notice('Extracting solr conf to ' . $dir);
          $zip->extractTo($dir);
          $zip->close();
        }
        unlink($id . '.zip');
      } else {
        error_log($process->getErrorOutput());
      }
    }
  }
}
