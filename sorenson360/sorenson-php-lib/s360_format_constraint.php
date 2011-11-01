<?php

class S360_FormatConstraint extends S360 {
  
  public $id, $name, $displayName, $defaultVideoDuration, $maxVideoDuration, $defaultFrameRate, $maxFrameRate, $maxWidth, $defaultWidth, $ratePlanId, $maxHeight, $defaultHeight, $defaultAudioDataRate, $maxAudioDataRate, $dateRetrieved, $defaultVideoDataRate, $maxVideoDataRate, $thumbnailGenerationMethod, $defaultAudioCodec, $defaultVideoCodec, $audioBitRateMode, $videoBitRateMode, $mediaType;
  
  public static function fromJSON($ratePlan, $data) {
    $format_constraint = new S360_FormatConstraint;
    $format_constraint->init($ratePlan, $data);
    return $format_constraint;
  }
  
  private function init($ratePlan, $data) {
    $this->ratePlan                  = $ratePlan;
    $this->id                        = $data['id'];
    $this->name                      = $data['name'];
    $this->displayName               = $data['displayName'];
    $this->defaultVideoDuration      = $data['defaultVideoDuration'];
    $this->maxVideoDuration          = $data['maxVideoDuration'];
    $this->defaultFrameRate          = $data['defaultFrameRate'];
    $this->maxFrameRate              = $data['maxFrameRate'];
    $this->maxWidth                  = $data['maxWidth'];
    $this->defaultWidth              = $data['defaultWidth'];
    $this->ratePlanId                = $data['ratePlanId'];
    $this->maxHeight                 = $data['maxHeight'];
    $this->defaultHeight             = $data['defaultHeight'];
    $this->defaultAudioDataRate      = $data['defaultAudioDataRate'];
    $this->maxAudioDataRate          = $data['maxAudioDataRate'];
    $this->dateRetrieved             = $data['dateRetrieved'];
    $this->defaultVideoDataRate      = $data['defaultVideoDataRate'];
    $this->maxVideoDataRate          = $data['maxVideoDataRate'];
    $this->thumbnailGenerationMethod = $data['thumbnailGenerationMethod'];
    $this->defaultAudioCodec         = $data['defaultAudioCodec'];
    $this->defaultVideoCodec         = $data['defaultVideoCodec'];
    $this->audioBitRateMode          = $data['audioBitRateMode'];
    $this->videoBitRateMode          = $data['videoBitRateMode'];
    $this->mediaType                 = $data['mediaType'];

  }
}

?>