<?php

class S360_Asset extends S360 {
  public $encodeDate, $frameRate, $height, $dateLastModified, $videoBitrateMode, $mediaType, $id, $accountId, $numberOfViews, $presetXml, $application, $audioCodec, $permalinkLocation, $status, $description, $videoDuration, $abstractFileId, $versionId, $dateRetrieved, $audioDataRate, $audioBitrateMode, $videoCodec, $displayName, $name, $videoDataRate, $authorId, $width, $fileSize, $defaultEmbed;
  
  public $filters = array();
  public $embedList = array();
  
  public static function fromJSON($account, $data) {
    $asset = new S360_Asset;
    $asset->init($account, $data);
    return $asset;
  }
  
  public static function find($account, $offset, $numToRetrieve) {
    
    $data = parent::do_post("/api/getMediaList?offset=" . $offset . "&quantity=" . $numToRetrieve . "&accountId=" . $account->customerId . "&sessionId=" . $account->sessionId . "&status=Live&sort=uploadDate");
    $assets = array();
    
    foreach($data['mediaList'] as $entry) {
      $assets[] = S360_Asset::fromJSON($account, $entry);
    }
    
    return $assets;
  }
  
  
  private function init($account, $data) {
    $this->account                = $account;
    $this->presetXml              = $data['presetXml'];
    $this->encodeDate             = $data['encodeDate'];
    $this->frameRate              = $data['frameRate'];
    $this->height                 = $data['height'];
    $this->dateLastModified       = $data['dateLastModified'];
    $this->videoBitrateMode       = $data['videoBitrateMode'];
    $this->mediaType              = $data['mediaType'];
    $this->id                     = $data['id'];
    $this->accountId              = $data['accountId'];
    $this->numberOfViews          = $data['numberOfViews'];
    $this->application            = $data['application'];
    $this->audioCodec             = $data['audioCodec'];
    $this->permalinkLocation      = $data['permalinkLocation'];
    $this->status                 = $data['status'];
    $this->description            = $data['description'];
    $this->videoDuration          = $data['videoDuration'];
    $this->abstractFileId         = $data['abstractFileId'];
    $this->versionId              = $data['versionId'];
    $this->dateRetrieved          = $data['dateRetrieved'];
    $this->audioDataRate          = $data['audioDataRate'];
    $this->audioBitrateMode       = $data['audioBitrateMode'];
    $this->videoCodec             = $data['videoCodec'];
    $this->displayName            = $data['displayName'];
    $this->name                   = $data['name'];
    $this->videoDataRate          = $data['videoDataRate'];
    $this->authorId               = $data['authorId'];
    $this->width                  = $data['width'];
    $this->fileSize               = $data['fileSize'];
    $this->thumbnailImageUrl      = $data['thumbnail']['httpLocation'];

    foreach($data['filters'] as $filter) {
      $this->filter[] = $filter['filterDescription'];
    }
    
    $this->get_embed_codes();
  }
  
  
  private function get_embed_codes() {
    $data = parent::do_post("/api/getAllEmbedcodes?vguid=" . $this->id . "&sessionId=" . $this->account->sessionId);
    foreach($data['embedList'] as $embed) {
      $pattern = '/width="(\d+)"/';
      preg_match($pattern, $embed, $matches);
      $width = $matches[1];
      $pattern = '/height="(\d+)"/';
      preg_match($pattern, $embed, $matches);
      $height = $matches[1];

      $ratio = round($width/$height,2);      

      if ($ratio <= 1.33) {
          $aspect = "FullScreen";
      } else {
          $aspect = "Widescreen";
      }

      $this->embedList[$width . 'x' . $height . ' - ' . $aspect] = $embed;
    }
    ksort($this->embedList);
    $this->defaultEmbed = $data['defaultEmbed'];
   }
  
}

?>