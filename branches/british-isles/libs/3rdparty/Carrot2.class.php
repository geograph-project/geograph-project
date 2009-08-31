<?php

if (!function_exists('curl_init')) {
  throw new Exception("Curl is required for this class.");
}

class Cluster
{
  public $label;
  public $score;
  public $document_ids = array();
  public $subclusters  = array();

  public function __construct($label, $score)
  {
    $this->label = $label;
    $this->score = $score;
  }

  public function addDocumentId($id)
  {
    $this->document_ids[] = $id;
  }

  public function addSubcluster($cluster)
  {
    $this->subclusters[] = $cluster;
  }

  public function __toString()
  {
    return $this->label . ' (' . count($this->document_ids) . ')';
  }
}

class Carrot2 
{
  private $baseurl;
  private $documents = array();

  public function __construct($baseurl)
  {
    $this->baseurl = $baseurl;
  }

  public static function createDefault()
  {
    $carrot = new self('http://localhost:8080/dcs/rest');
    return $carrot;
  }

  public function addDocument($url='', $title='', $snippet='')
  {
    $this->documents[] = array($url, $title, $snippet);
  }

  public function clusterQuery($query_hint='')
  {
    $curl   = curl_init($this->baseurl);
    $fields = array(
      'dcs.output.format' => 'XML',
      'dcs.clusters.only'  => 'true',
      'dcs.c2stream'           => $this->generateXml($query_hint)
    );
    curl_setopt_array($curl,
      array(
        CURLOPT_POST           => true,
        CURLOPT_HEADER         => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => $fields
      )
    );
    curl_setopt($curl, CURLOPT_VERBOSE, 1);
    $response = curl_exec($curl);

    return $this->extractClusters($response);
  }

  private function generateXml($query_hint)
  {
    $dom     = new DOMDocument('1.0', 'UTF-8');
    $results = $dom->createElement('searchresult');
    $dom->appendChild($results);
    if ($query_hint) {
      $this->appendTextField($dom, $results, 'query', $query_hint);
    }
    for ($i=0, $c=count($this->documents); $i<$c; $i++) {
      $document = $dom->createElement('document');
      $document->setAttribute('id', $i);
      $this->appendTextField($dom, $document, 'title', $this->documents[$i][1]);
      $this->appendTextField($dom, $document, 'snippet', $this->documents[$i][2]);
      $this->appendTextField($dom, $document, 'url', $this->documents[$i][0]);
      $results->appendChild($document);
    }
    return $dom->saveXML();
  }

  private function appendTextField($dom, $elem, $name, $value)
  {
    $text = $dom->createElement($name);
    $text->appendChild($dom->createTextNode($value));
    $elem->appendChild($text);
  }

  private function extractClusters($xml)
  {
  
    if (!($xml instanceof SimpleXMLElement)) {
      $xml = new SimpleXMLElement($xml);
    }
    $clusters = array();
    foreach ($xml->xpath('//group') as $group) {
      $cluster = new Cluster(
        (string)$group->title->phrase,
        (string)$group['score']
      );
      foreach ($group->xpath('document') as $document) {
        $cluster->addDocumentId((string)$document['refid']);
      }
      $clusters[] = $cluster;
    }
    return $clusters;
  }
} ?>