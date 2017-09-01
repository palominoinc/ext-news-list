<?php

/*
 *
 */

namespace NewsList\Source;

use \DateTime;
use \DateTimeZone;
use \DateInterval;
use \DOMElement;
use Log;
use \WebPal;

class Newslist
{
  static $dateFormat = 'D, j M Y';
  
  static function generateRSS($node_id)
  {
    $doc = self::retrieveNewslist($node_id);
    
    if (is_null($doc) || !$doc->hasChildNodes()) {
      return null;
    }
    
    return array(
      'channel' => self::getTitle($doc),
      'link' => self::createLink($doc),
      'description' => self::getDescription($doc),
      'language' => WebPal::language(),
      'pubDate' => self::getLastPublishedDate($doc),
      'managingEditor' => self::getEditor($doc),
      'webMaster' => self::getWebMaster($doc),
      'items' => self::getItems($doc)
    );
  }
  
  // Returns the text content of the node with nodeName as an immediate child node of the news-list in the supplied doc
  // Returns empty string if none found
  static function getTextContent($doc, $nodeName)
  {
    $newslist = $doc->firstChild;
    
    if(!is_null($newslist)) {
      for ($child = $newslist->firstChild; $child != null; $child = $child->nextSibling) {
        if ($child instanceof DOMElement && $child->tagName == $nodeName) {
          return $child->textContent;
        }
      }
    }
    
    return '';
  }
  
  static function getTitle($doc)
  {
    return self::getTextContent($doc, 'title');
    
  }
  
  // Create a link to the page containing the news-list
  static function createLink($doc)
  {
    $id = $doc->childNodes->item(0)->getAttribute('id');
    $path = self::retrievePathToNewslistPage($id);
    
    $protocol = "https://";
    $host = $_SERVER['SERVER_NAME'];
    $url = $protocol . $host . $path;
    return $url;
  }
  
  static function getDescription($doc)
  {
    return self::getTextContent($doc, 'description');
  }
  
  // TODO: Find the last published date
  static function getLastPublishedDate($doc)
  {
    $dates = [];
    
    $newslist = $doc->firstChild;
    
    foreach ($newslist->getElementsByTagName('news') as $newsitem) {
      $dateAttr = $newsitem->getAttribute('date');
      $date = new DateTime($dateAttr, new DateTimeZone('EST'));
      
      $dates[] = $date;
    }
    
    if (sizeof($dates) >= 1) {
      // Sort dates in descending order
      usort($dates, function($a, $b) {
        if ($a == $b) {
          return 0;
        }
        return $a < $b ? 1 : -1;
      
      });
      return $dates[0]->format(self::$dateFormat);
    }
    
    return '';
  }
  
  static function getEditor($doc)
  {
    return self::getTextContent($doc, 'editor');
  }
  
  static function getWebMaster($doc)
  {
    return self::getTextContent($doc, 'webmaster'); 
  }
  
  // Returns an array of news items, which are arrays with information of the news item
  // Returns an empty array if no news items exist
  static function getItems($doc)
  {
    $items = array();
    $baseLink = self::createLink($doc);
    
    $newslist = $doc->firstChild;
    
    if (!is_null($newslist)) {
      foreach ($newslist->getElementsByTagName('news') as $newsitem) {
        $title = '';
        $link = $baseLink . '/?node=' . $newsitem->getAttribute('name');
        $desc = '';
        $pubdate = '';
        
        for ($child = $newsitem->firstChild; !is_null($child); $child = $child->nextSibling) {
          if ($child instanceof DOMElement) {
            switch ($child->tagName) {
              case 'title':
                $title = $child->textContent;
                break;
              case 'synopsis': // use synopsis as description
                $desc = $child->textContent;
              default:
                break;
            }
          }
        }
        
        //Retrieve pubdate
        $dateAttr = $newsitem->getAttribute('date');
        $date = new DateTime($dateAttr, new DateTimeZone('EST'));
        $pubdate = $date->format(self::$dateFormat);
        
        // TODO: Create link to the item
        
        $items[] = array(
          'item' => self::createItem($title, $link, $desc, $pubdate, $link),
          'date' => $date
        );
      }
    }
    
    if (sizeof($items) > 0) {
      // sort items in descending order
      usort($items, function($a, $b) {
        if ($a['date'] == $b['date']) {
          return 0;
        }
      
        return $a['date'] < $b['date'] ? 1 : -1;
      });
    
      // TODO: If necessary, trim the resulting array of items here
      
      return array_map(function($el) {
        return $el['item'];
      }, $items);
    } else {
      return $items;
    }
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
  
  // Get the website path to the page containing the newslist node
  static function retrievePathToNewslistPage($node_id)
  {
    $path = '';
    $ancestors = WebPal::webContent("//pages//*[@id='{$node_id}']/ancestor::page", array(
      'raw' => true
    ));
    
    foreach ($ancestors->childNodes as $ancestor) {
      Log::info('ancestor:', [$ancestor->getAttribute('name')]);
      $path .= '/' . $ancestor->getAttribute('name');
    }
    return $path;
  }
}
