<?php

class S360_RatePlan extends S360 {
  public $id, $displayName, $ratePlanType, $maxThumbnailsPerVideo, $setupCost, $monthlyCost, $allowedStreams, $basePlan, $dateLastModified, $dateRetrieved, $streamingOverageAllowed, $storageOverageAllowed, $allowedStreamingMBytes, $allowedStorageMBytes, $allowedSourceMediaTypes, $allowedOutputMediaTypes, $annualCost, $monthlyCostWithAnnualCommitment, $sorensonSku;
  
  public $formatConstraints = array();
  
  public static function fromJSON($data) {
    $rate_plan = new S360_RatePlan;
    $rate_plan->init($data);
    return $rate_plan;
  }
  
  private function init($data) {
    $this->id                              = $data['id'];
    $this->displayName                     = $data['displayName'];
    $this->ratePlanType                    = $data['ratePlanType'];
    $this->maxThumbnailsPerVideo           = $data['maxThumbnailsPerVideo'];
    $this->setupCost                       = $data['setupCost'];
    $this->monthlyCost                     = $data['monthlyCost'];
    if (array_key_exists('monthlyCostWithAnnualCommitment', $data)) {
      $this->monthlyCostWithAnnualCommitment = $data['monthlyCostWithAnnualCommitment'];
    }
    if (array_key_exists('annualCost', $data)) {
      $this->annualCost                      = $data['annualCost'];
    }
    $this->allowedStreams                  = $data['allowedStreams'];
    $this->basePlan                        = $data['basePlan'];
    $this->dateLastModified                = $data['dateLastModified'];
    $this->dateRetrieved                   = $data['dateRetrieved'];
    $this->streamingOverageAllowed         = ($data['streamingOverageAllowed'] == '1');
    $this->storageOverageAllowed    = ($data['storageOverageAllowed'] == '1');
    $this->allowedStreamingMBytes   = $data['allowedStreamingMBytes'];
    $this->allowedStorageMBytes     = $data['allowedStorageMBytes'];
    $this->allowedSourceMediaTypes  = $data['allowedSourceMediaTypes']; //multi param
    $this->allowedOutputMediaTypes  = $data['allowedOutputMediaTypes']; //multi param
    if (array_key_exists('sorensonSku', $data)) {
      $this->sorensonSku              = $data['sorensonSku'];
    }
    if ($data['formatConstraints']) {
      $this->initFormatConstraints($data['formatConstraints']);
    }
  }
  
  private function initFormatConstraints($data) {
    foreach ($data as $fc) {
      $this->formatConstraints[] = S360_FormatConstraint::fromJSON($this, $fc);
    }
  }

}

?>
