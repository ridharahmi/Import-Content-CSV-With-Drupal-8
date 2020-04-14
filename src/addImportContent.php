<?php

namespace Drupal\import_csv;

use Drupal\node\Entity\Node;
use GuzzleHttp\Exception\RequestException;
use Drupal\file\Entity\File;

class addImportContent
{
    public static function addImportContentItem($item, &$context)
    {
        $context['sandbox']['current_item'] = $item;
        $message = 'Creating ' . $item['title'];
        $results = array();
        $nid = isItemExiste($item['id']);
        $id = reset($nid);
        if ($id) {
            update_node($item, $id);
        } else {
            create_node($item);
        }
        $context['message'] = $message;
        $context['results'][] = $item;
    }

    public static function addImportContentItemCallback($success, $results, $operations)
    {
        if ($success) {
            $message = \Drupal::translation()->formatPlural(
                count($results),
                'One item processed.', '@count items processed.'
            );
        } else {
            $message = t('Finished with an error.');
        }
        drupal_set_message($message);
    }


}

/**
 * {@inheritdoc}
 */
function create_node($item)
{
    $uri = $item['image'];
    $file = prepareImageObj($uri);
    $node = Node::create([
        'type' => 'article',
        'field_index' => $item['id'],
        'title' => $item['title'],
        'field_categorie' =>  $item['categorie'],
        'body' => [
            'value' => $item['content'],
        ],
        'field_image' => [
            'target_id' => $file->id(),
            'alt' => $item['title'],
        ],
        'moderation_state' => [
            'target_id' => 'published',
        ],
        'uid' => 1,
        'langcode' => 'en',
        'status' => 1,
    ]);
    $node->save();
}

/**
 * {@inheritdoc}
 */
function isItemExiste($id)
{
    $nids = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->condition('field_index', $id)
        ->execute();
    return $nids;
}

/**
 * {@inheritdoc}
 */
function prepareImageObj($url)
{
    $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $url]);
    $file = reset($files);

    // if not create a file
    if (!$file) {
        $file = File::create([
            'uri' => $url,
        ]);
        $file->save();
    }
    return $file;
}
/**
 * {@inheritdoc}
 */
 function update_node($item, $id)
{
    $uri = $item['image'];
    $file = prepareImageObj($uri);
    $node = Node::load($id);
    $node->title = $item['title'];
    $node->field_categorie = $item['categorie'];
    $node->field_image = [
        'target_id' => $file->id(),
        'alt' => $item['title'],
    ];
    $node->body = [
        'value' => $item['content'],
    ];
    $node->save();
}
