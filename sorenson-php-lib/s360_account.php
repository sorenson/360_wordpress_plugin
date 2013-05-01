<?php

class S360_Account extends S360
{
  
  public $username, $status, $customerId, $token, $goto360URL, $ratePlanExpirationDate, $dateLastModified, $sorensonId, $lastLoginTime, $dateRetrieved, $sessionId, $ratePlan, $id;
  public $encoded = array();
  
  public $totalAssetCount = 0; 
  
  public static function fromJSON($data) {
    $account = new S360_Account;
    $account->init($data);
    return $account;
  }
  
  private static function _do_login($url) {
    $data = parent::do_post($url);

    if (!$data || array_key_exists('errorCode', $data) && $data['errorCode']) {
      return $data;
    } else {
      return S360_Account::fromJSON($data);
    }
  }
  
  public static function login($username, $password) {
    return S360_Account::_do_login('/api/loginApi?username=' . urlencode($username) . '&password=' . urlencode($password));
  }
  
  public static function loginWithSessionId($username, $session_id) {
    return S360_Account::_do_login('/api/loginApi?username=' . urlencode($username) . '&sessionId=' . $session_id);
  }
  
  private function init($data) {
    $account                      = $data['account'];

    $this->sessionId              = $data['sessionId'];
    $this->token                  = $data['token'];
    if (array_key_exists('goto360URL', $data)) {
      $this->goto360URL             = $data['goto360URL'];
    }
    
    // depricated
    $this->gotoJuiceURL           = $data['gotoJuiceURL'];

    // account specific data
    $this->username               = $account['username'];
    $this->status                 = $account['status'];
    $this->customerId             = $account['id'];
    $this->id                     = $account['id'];
    $this->ratePlanExpirationDate = $account['ratePlanExpirationDate'];
    $this->dateLastModified       = $account['dateLastModified'];
    $this->sorensonId             = $account['sorensonId'];
    $this->lastLoginTime          = $account['lastLoginTime'];
    $this->dateRetrieved          = $account['dateRetrieved'];
    $this->totalAssetCount        = $this->getAssetCount();
    
    $this->ratePlan              = S360_RatePlan::fromJSON($account['ratePlan']);
    
  }
  
  public function getAssets($offset = null, $numToRetrieve = null) {
    return S360_Asset::find($this, $offset, $numToRetrieve);
  }

  
  public function getAssetCount() {
    $data = parent::do_post("/api/getMediaListSummary?accountId=" . $this->id . "&sessionId=" . $this->sessionId);
    return intval($data['totalMediaCount']);
  }
}

?>
