<?php

/*
 *
 */

namespace NewsList\Source;

use \WebPal;

class Newslist
{
  static function generateRSS($node_id)
  {
    $node = self::retrieveNewslist($node_id);
    
    if (is_null($node)) {
      return null;
    }
    
    // TODO: Need to find out how to retrieve link to the page with the news list
    $channel = 'INSERT NEWS LIST TITLE HERE';
    
    $items = array(
      self::createItem('item title', 'item link', 'item description', 'item pub date', 'item link')
    );
    
    
    return array(
      'channel' => $channel,
      'link' => 'INSERT LINK TO NEWS PAGE HERE',
      'description' => 'INSERT NEWS LIST DESCRIPTION HERE',
      'language' => 'en-us', // TODO: Find some way to find language
      'pubDate' => 'TODO: Look up last publish date of the news list',
      'docs' => 'http://blogs.law.harvard.edu/tech/rss', // Link to the RSS 2.0 spec
      'generator' => 'WebPal 3',
      'managingEditor' => 'TODO: Look up news list editor',
      'webMaster' => 'TODO: Look up webmaster',
      'items' => $items
    );
    return "news rss for ${node_id}";
  }
  
  static function createItem($title, $link, $description, $pubDate, $guid)
  {
    return array(
      "title" => $title,
      "link" => $link,
      "description" => $description,
      "pubDate" => $pubDate,
      "guid" => $guid
    );
  }
  
  /* Returns the DOMDocument with this node's content */
  static function retrieveNewslist($node_id)
  {
    $node = WebPal::webContent("//pages//*[@id='{$node_id}']", array(
      'raw' => true
    ));
    
    // Only return the resulting DOMDocument if the node exists
    if ($node->childNodes->length < 1) {
      return null;
    } else {
      return $node;
    }
  }
}
